<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\BettingType;
use App\Models\LotterySchedule;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;
use App\Support\Region;
use App\Services\BetPricingService;
use App\Services\BettingRateResolver;

class BettingMessageParser
{
    /** @var array<string,string> alias -> canonical betting type code */
    private array $typeAliasMap = [];

    /** @var array<string,string> station alias -> canonical station key */
    private array $stationAliasMap = [];

    protected BetPricingService $pricing;
    protected LotteryScheduleService $scheduleService;

    public function __construct(BetPricingService $pricing, LotteryScheduleService $scheduleService)
    {
        $this->pricing = $pricing;
        $this->scheduleService = $scheduleService;
        
        // Fallback luôn có; DB override/thêm vào
        $this->typeAliasMap = $this->defaultTypeAliases();
        $dbMap = BettingType::aliasMap();
        foreach ($dbMap as $alias => $code) {
            $this->typeAliasMap[$alias] = $code;
        }
        $this->stationAliasMap = $this->defaultStationAliases();
    }

     /**
     * Parse betting message to structured bets.
     *
     * @param  string $message
     * @param  array  $context  // có thể chứa: ['customer_id'=>..., 'region'=>...]
     * @return array
     */
    public function parseMessage(string $message, array|string|int $context = []): array
    {
        $errors = [];
    
        // ---------- helpers ----------
        $addEvent = function(array &$events, string $kind, array $extra = []) {
            $events[] = array_merge(['kind' => $kind], $extra);
        };
    
        $stripAccents = function(string $s): string {
            $s = mb_strtolower($s, 'UTF-8');
            $replacements = [
                'à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ' => 'a',
                'è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ'             => 'e',
                'ì|í|ị|ỉ|ĩ'                         => 'i',
                'ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ' => 'o',
                'ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ'             => 'u',
                'ỳ|ý|ỵ|ỷ|ỹ'                         => 'y',
                'đ'                                  => 'd',
            ];
            foreach ($replacements as $re => $to) {
                $s = preg_replace("/$re/u", $to, $s) ?? $s;
            }
            $s = str_replace(['t,pho','tphố','tpho','tp.hcm','tphcm','tp ho chi minh'], 'hcm', $s);
            $s = str_replace(['t,ninh','t ninh','tninh'], 'tn', $s);
            // Hỗ trợ viết tắt có dấu phẩy: T,Giang → tg, K,Giang → kg
            $s = str_replace(['t,giang','t giang','tgiang','tien giang'], 'tg', $s);
            $s = str_replace(['k,giang','k giang','kgiang','kien giang'], 'kg', $s);
            $s = preg_replace('/[,]+/u',' ', $s) ?? $s;
            return trim($s);
        };
    
        $splitTokens = function(string $s): array {
            $s = preg_replace('/[^\w\.]/u', ' ', $s);
            $s = preg_replace('/\s+/',' ', $s);

            // Bảo vệ số thập phân trong amount token (3.5n, 7.5k) bằng placeholder
            $s = preg_replace('/(\d+)\.(\d+)([nk])/i', '$1___DECIMAL___$2$3', $s);

            // Tách combo dính (hỗ trợ decimal: 952xc3.5n, 92lo7.5k)
            $s = preg_replace('/(\d{2,4})(xc)(\d+(?:___DECIMAL___\d+)?)(n|k)/', '$1 $2 $3$4', $s);
            $s = preg_replace('/(\d{2,4})(lo)(\d+(?:___DECIMAL___\d+)?)(n|k)/', '$1 $2 $3$4', $s);
            $s = preg_replace('/(\d{2,4})(dd)(\d+(?:___DECIMAL___\d+)?)(n|k)/', '$1 $2 $3$4', $s);
            $s = preg_replace('/(\d{2,4})(d)(\d+(?:___DECIMAL___\d+)?)(n|k)/',  '$1 $2 $3$4', $s);

            // Tách dấu chấm còn lại (không phải trong số thập phân)
            $s = str_replace('.', ' . ', $s);

            // Restore lại dấu chấm thập phân
            $s = str_replace('___DECIMAL___', '.', $s);

            $s = preg_replace('/\s+/', ' ', $s);
            return array_values(array_filter(explode(' ', trim($s))));
        };
    
        $joinStations = function(array $stations): ?string {
            if (!count($stations)) return null;
            if (count($stations) === 1) return $stations[0];
            return implode(' + ', $stations);
        };
    
        $resetGroup = function(array &$ctx) {
            $ctx['numbers_group'] = [];
            $ctx['current_type']  = null;
            $ctx['amount']        = null;
            $ctx['meta']          = [];
            $ctx['pair_d_dau']    = [];
            // xỉu chủ
            $ctx['xc_d_list']     = [];
            $ctx['xc_dd_amount']  = null;
            // kéo
            $ctx['meta']['keo_start'] = null;
        };
    
        $emitBet = function(array &$outBets, array &$ctx, array $bet) {
            // Nếu chưa set station trong $bet → xét theo context
            if (empty($bet['station'])) {
                if (!empty($ctx['stations'])) {
                    // Nếu có nhiều đài trong context → NHÂN BẢN mỗi đài một vé
                    if (count($ctx['stations']) > 1) {
                        foreach ($ctx['stations'] as $st) {
                            $clone = $bet;
                            $clone['station'] = $st;
                            $clone['meta']    = $clone['meta'] ?? [];
                            // Nếu trước đó có đặt N đài (Ndai) nhưng chưa chỉ rõ danh sách,
                            // thì khi đã có stations cụ thể, không cần giữ dai_count nữa.
                            unset($clone['meta']['dai_count']);
                            $outBets[] = $clone;
                        }
                        return;
                    } else {
                        // 1 đài duy nhất trong context
                        $bet['station'] = $ctx['stations'][0];
                    }
                } else {
                    // Không có đài cụ thể → nếu có đặt Ndai thì giữ meta để layer sau expand,
                    // còn không thì để null, phần cuối hàm sẽ fallback default nếu cần.
                    $bet['meta'] = $bet['meta'] ?? [];
                    if (!empty($ctx['dai_count'])) {
                        $bet['meta']['dai_count'] = (int)$ctx['dai_count'];
                    }

                    // QUAN TRỌNG: Snapshot ctx['stations'] tại thời điểm emit
                    // Tránh contamination từ các group sau
                    $bet['meta']['_ctx_stations_snapshot'] = $ctx['stations'] ?? [];
                }
            }

            $bet['meta'] = $bet['meta'] ?? [];
            $outBets[] = $bet;
        };
    
        $emitBaoLoOneByOne = function(array &$outBets, array &$ctx) use ($emitBet) {
            $numbers = array_values(array_unique($ctx['numbers_group']));
            if (!count($numbers) || !(int)($ctx['amount'] ?? 0)) return;
            foreach ($numbers as $n) {
                $digits = strlen($n);
                if ($digits < 2 || $digits > 4) continue;
                
                // Tạo meta với digits, sẽ merge với dai_count trong emitBet nếu cần
                $betMeta = ['digits' => $digits];
                
                $emitBet($outBets, $ctx, [
                    'numbers' => [$n],
                    'type'    => 'bao_lo',
                    'amount'  => (int)$ctx['amount'],
                    'meta'    => $betMeta,
                ]);
            }
        };
    
        $expandKeoNumbers = function(string $start, string $end): array {
            $len = strlen($start);
            if ($len !== strlen($end)) return [];
            if ($len === 2) {
                [$a1,$a2] = str_split($start); [$b1,$b2] = str_split($end);
                if ($a1 === $b1) {
                    $from = (int)$a2; $to = (int)$b2; if ($from > $to) [$from,$to]=[$to,$from];
                    $out=[]; for($i=$from;$i<=$to;$i++) $out[]=$a1.(string)$i;
                    return array_map(fn($s)=>str_pad($s,2,'0',STR_PAD_LEFT),$out);
                }
                if ($a2 === $b2) {
                    $from = (int)$a1; $to = (int)$b1; if ($from > $to) [$from,$to]=[$to,$from];
                    $out=[]; for($i=$from;$i<=$to;$i++) $out[]=(string)$i.$a2;
                    return array_map(fn($s)=>str_pad($s,2,'0',STR_PAD_LEFT),$out);
                }
                return [];
            }
            if ($len === 3) {
                $a = str_split($start); $b = str_split($end);
                $diffIdx=null; $same=0;
                for($i=0;$i<3;$i++){ if($a[$i]===$b[$i]) $same++; else $diffIdx=$i; }
                if ($same!==2 || $diffIdx===null) return [];
                $from=(int)$a[$diffIdx]; $to=(int)$b[$diffIdx]; if($from>$to) [$from,$to]=[$to,$from];
                $out=[]; for($x=$from;$x<=$to;$x++){ $tmp=$a; $tmp[$diffIdx]=(string)$x; $out[]=implode('',$tmp); }
                return array_map(fn($s)=>str_pad($s,3,'0',STR_PAD_LEFT),$out);
            }
            return [];
        };
    
        $isGroupPending = function(array $ctx): bool {
            $type = $ctx['current_type'] ?? null;
            if (!$type) return false;
            if ($type === 'keo_hang_don_vi') return false;
            $hasNumbers = !empty($ctx['numbers_group']);
            return match ($type) {
                'bao_lo'    => $hasNumbers && (int)($ctx['amount'] ?? 0) > 0,
                'dau'       => $hasNumbers && ( !empty($ctx['pair_d_dau']) || (int)($ctx['amount'] ?? 0) > 0 ),
                'duoi'      => $hasNumbers && (int)($ctx['amount'] ?? 0) > 0,
                'dau_duoi'  => $hasNumbers && (int)($ctx['amount'] ?? 0) > 0,
                'xiu_chu'   => $hasNumbers && ( (int)($ctx['amount'] ?? 0) > 0 || !empty($ctx['xc_d_list']) || !empty($ctx['xc_dd_amount']) ),
                'xien'      => (int)($ctx['amount'] ?? 0) > 0 && $hasNumbers && count($ctx['numbers_group']) >= (int)($ctx['meta']['xien_size'] ?? 0),
                'da_xien'   => $hasNumbers && (int)($ctx['amount'] ?? 0) > 0,
                'da_thang'  => $hasNumbers && (int)($ctx['amount'] ?? 0) > 0,
                default     => false,
            };
        };
    
        $flushGroup = function(array &$outBets, array &$ctx, array &$events, ?string $reason=null) use ($emitBet, $emitBaoLoOneByOne, $addEvent) {
            if ($reason) $addEvent($events, $reason);

            $numbers = array_values(array_unique($ctx['numbers_group'] ?? []));
            $type    = $ctx['current_type'] ?? null;
            $amount  = (int)($ctx['amount'] ?? 0);
            $region  = $ctx['region'] ?? 'nam';

            // LƯU last_numbers CHỈ KHI flush vì hoàn chỉnh (combo token, amount complete)
            // KHÔNG lưu khi flush vì chuyển type (type_switch_flush)
            // → Tránh kế thừa số của type cũ cho type mới
            if (!empty($numbers) && $reason !== 'type_switch_flush') {
                $ctx['last_numbers'] = $numbers;
            }

            // KẾ THỪA KIỂU CƯỢC: Nếu không có type nhưng có last_type → dùng last_type
            // Quy tắc: "nếu k có kiểu cược thì lấy kiểu trước đó"
            if (!$type && !empty($ctx['last_type']) && !empty($numbers) && $amount > 0) {
                $type = $ctx['last_type'];
                $ctx['current_type'] = $type;
                $addEvent($events, 'inherit_type', ['type' => $type, 'from_last_type' => true]);
            }

            if (!$type) { $ctx['numbers_group']=[]; $ctx['amount']=null; $ctx['meta']=[]; return; }
            if (!count($numbers) && !in_array($type, ['xiu_chu'], true)) {
                $ctx['numbers_group']=[]; $ctx['amount']=null; $ctx['meta']=[]; $ctx['current_type']=null;
                return;
            }

            // LƯU KIỂU CƯỢC trước khi emit để có thể kế thừa cho group tiếp theo
            // Quy tắc: "nếu k có kiểu cược thì lấy kiểu trước đó"
            $ctx['last_type'] = $type;

            if ($type === 'bao_lo') {
                $emitBaoLoOneByOne($outBets, $ctx);
                $ctx['numbers_group']=[]; $ctx['amount']=null; $ctx['meta']=[]; $ctx['current_type']=null;
                return;
            }
    
            if ($type === 'xiu_chu') {
                if (count($numbers)) {
                    if (!empty($ctx['xc_dd_amount'])) {
                        foreach ($numbers as $n) {
                            $emitBet($outBets, $ctx, ['numbers'=>[$n],'type'=>'xiu_chu_dau','amount'=>(int)$ctx['xc_dd_amount']]);
                            $emitBet($outBets, $ctx, ['numbers'=>[$n],'type'=>'xiu_chu_duoi','amount'=>(int)$ctx['xc_dd_amount']]);
                        }
                        $addEvent($events, 'emit_xc_head_tail', ['mode'=>'dd','amount'=>$ctx['xc_dd_amount'],'numbers'=>$numbers]);
                    } elseif (!empty($ctx['xc_d_list'])) {
                        $dauAmt  = $ctx['xc_d_list'][0] ?? null;
                        $duoiAmt = $ctx['xc_d_list'][1] ?? null;
                        foreach ($numbers as $n) {
                            if ($dauAmt !== null)  $emitBet($outBets, $ctx, ['numbers'=>[$n],'type'=>'xiu_chu_dau','amount'=>(int)$dauAmt]);
                            if ($duoiAmt !== null) $emitBet($outBets, $ctx, ['numbers'=>[$n],'type'=>'xiu_chu_duoi','amount'=>(int)$duoiAmt]);
                        }
                        $addEvent($events, 'emit_xc_head_tail', ['mode'=>'d_sequence','dau'=>$dauAmt,'duoi'=>$duoiAmt,'numbers'=>$numbers]);
                    } else {
                        foreach ($numbers as $n) {
                            $emitBet($outBets, $ctx, ['numbers'=>[$n],'type'=>'xiu_chu_dau','amount'=>$amount]);
                            $emitBet($outBets, $ctx, ['numbers'=>[$n],'type'=>'xiu_chu_duoi','amount'=>$amount]);
                        }
                        $addEvent($events, 'emit_xc_split_per_number_default', ['amount'=>$amount,'numbers'=>$numbers,'note'=>'Default split to dau+duoi']);
                    }
                }
                $ctx['numbers_group'] = [];
                $ctx['amount']        = null;
                $ctx['meta']          = [];
                $ctx['xc_d_list']     = [];
                $ctx['xc_dd_amount']  = null;
                // KHÔNG clear current_type để cho phép tiếp tục nhận số xc tiếp theo
                // $ctx['current_type']  = null;
                return;
            }
    
            if ($type === 'xien') {
                $x = (int)($ctx['meta']['xien_size'] ?? 0);
                if ($region !== 'bac') {
                    $addEvent($events, 'block_emit_xien_wrong_region', ['region'=>$region]);
                    $ctx['numbers_group']=[]; $ctx['amount']=null; $ctx['meta']=[]; $ctx['current_type']=null;
                    return;
                }
                if ($x >= 2 && $x <= 4 && count($numbers) >= $x) {
                    $emitBet($outBets, $ctx, [
                        'numbers' => $numbers,
                        'type'    => 'xien',
                        'amount'  => $amount,
                        'meta'    => ['xien_size' => $x],
                    ]);
                    $addEvent($events, 'emit_xien', ['xien_size'=>$x,'numbers'=>$numbers,'amount'=>$amount]);
                } else {
                    $addEvent($events, 'error_xien_numbers_not_enough', ['need'=>$x,'have'=>count($numbers)]);
                }
                $ctx['numbers_group']=[]; $ctx['amount']=null; $ctx['meta']=[]; $ctx['current_type']=null;
                return;
            }
    
            if ($type === 'dau_duoi') {
                foreach ($numbers as $n) {
                    $emitBet($outBets, $ctx, ['numbers'=>[$n],'type'=>'dau','amount'=>$amount]);
                    $emitBet($outBets, $ctx, ['numbers'=>[$n],'type'=>'duoi','amount'=>$amount]);
                }
                $ctx['numbers_group']=[]; $ctx['amount']=null; $ctx['meta']=[]; $ctx['current_type']=null;
                return;
            }
    
            if ($type === 'dau') {
                if (!empty($ctx['pair_d_dau'])) {
                    $dauAmt  = $ctx['pair_d_dau'][0] ?? 0;
                    $duoiAmt = $ctx['pair_d_dau'][1] ?? null;
                    foreach ($numbers as $n) {
                        if ($dauAmt)  $emitBet($outBets, $ctx, ['numbers'=>[$n],'type'=>'dau','amount'=>(int)$dauAmt]);
                        if ($duoiAmt) $emitBet($outBets, $ctx, ['numbers'=>[$n],'type'=>'duoi','amount'=>(int)$duoiAmt]);
                    }
                    $addEvent($events,'emit_pair_d',['numbers'=>$numbers,'dau'=>$dauAmt,'duoi'=>$duoiAmt]);
                } else {
                    foreach ($numbers as $n) {
                        $emitBet($outBets, $ctx, ['numbers'=>[$n],'type'=>'dau','amount'=>$amount]);
                    }
                }
                $ctx['numbers_group']=[]; $ctx['amount']=null; $ctx['meta']=[]; $ctx['pair_d_dau']=[]; $ctx['current_type']=null;
                return;
            }
    
            if ($type === 'duoi') {
                foreach ($numbers as $n) {
                    $emitBet($outBets, $ctx, ['numbers'=>[$n],'type'=>'duoi','amount'=>$amount]);
                }
                $ctx['numbers_group']=[]; $ctx['amount']=null; $ctx['meta']=[]; $ctx['current_type']=null;
                return;
            }

            // Đá thẳng: 1 đài, ghép cặp 2-2 theo thứ tự
            if ($type === 'da_thang') {
                $stationCount = count($ctx['stations'] ?? []);

                // Auto-resolve station nếu chưa có (Rule 2, Rule 5)
                if ($stationCount === 0) {
                    // Lấy đài chính từ lịch (theo date + region)
                    try {
                        $mainStation = $this->scheduleService->getNStations(1, $ctx['betting_date'], $region);
                        if (!empty($mainStation)) {
                            $ctx['stations'] = [$mainStation[0]];
                            $stationCount = 1;
                            $addEvent($events, 'da_thang_auto_resolve_station', [
                                'region' => $region,
                                'date' => $ctx['betting_date'],
                                'resolved_station' => $mainStation[0],
                            ]);
                        }
                    } catch (\Exception $e) {
                        // Ignore error, validation sẽ reject
                    }
                }

                // Validate: bắt buộc 1 đài (sau khi đã auto-resolve)
                if ($stationCount !== 1) {
                    $addEvent($events, 'error_da_thang_wrong_station_count', [
                        'expected' => 1,
                        'got' => $stationCount,
                        'message' => 'Đá thẳng yêu cầu đúng 1 đài'
                    ]);
                    $ctx['numbers_group']=[]; $ctx['amount']=null; $ctx['meta']=[]; $ctx['current_type']=null;
                    return;
                }

                // Ghép cặp 2-2
                $pairs = [];
                for ($i = 0; $i < count($numbers) - 1; $i += 2) {
                    $pairs[] = [$numbers[$i], $numbers[$i + 1]];
                }

                // Nếu lẻ số → log warning
                if (count($numbers) % 2 !== 0) {
                    $addEvent($events, 'warning_da_thang_odd_numbers', [
                        'total' => count($numbers),
                        'dropped' => $numbers[count($numbers) - 1],
                        'message' => 'Số lẻ, bỏ số cuối: ' . $numbers[count($numbers) - 1]
                    ]);
                }

                // Emit mỗi cặp là 1 vé
                foreach ($pairs as $pair) {
                    $emitBet($outBets, $ctx, [
                        'numbers' => $pair,
                        'type'    => 'da_thang',
                        'amount'  => $amount,
                        'meta'    => $ctx['meta'] ?? [],
                    ]);
                }

                $addEvent($events, 'emit_da_thang', ['pairs' => $pairs, 'station' => $ctx['stations'][0]]);
                $ctx['numbers_group']=[]; $ctx['amount']=null; $ctx['meta']=[]; $ctx['current_type']=null;
                return;
            }

            // Đá xiên: ≥2 đài, sinh C(n,2) combinations
            if ($type === 'da_xien') {
                $stationCount = count($ctx['stations'] ?? []);
                $daiCount = $ctx['dai_count'] ?? null;  // Lấy từ $ctx, không phải $ctx['meta']

                // Auto-resolve stations nếu có dai_count nhưng chưa có đủ đài
                if ($daiCount >= 2 && $stationCount === 0) {
                    // Lấy N đài từ lịch (theo date + region)
                    try {
                        $autoStations = $this->scheduleService->getNStations(
                            $daiCount,
                            $ctx['betting_date'],
                            $region
                        );
                        if (!empty($autoStations)) {
                            $ctx['stations'] = $autoStations;
                            $stationCount = count($autoStations);
                            $addEvent($events, 'da_xien_auto_resolve_stations', [
                                'region' => $region,
                                'date' => $ctx['betting_date'],
                                'dai_count' => $daiCount,
                                'resolved_stations' => $autoStations,
                            ]);
                        }
                    } catch (\Exception $e) {
                        // Ignore error, validation sẽ reject
                    }
                }

                $stations = $ctx['stations'] ?? [];
                $stationCount = count($stations);

                // Validate: tối thiểu 2 đài (sau khi đã auto-resolve)
                if ($stationCount < 2) {
                    $addEvent($events, 'error_da_xien_min_stations', [
                        'expected' => '>=2',
                        'got' => $stationCount,
                        'message' => 'Đá xiên yêu cầu tối thiểu 2 đài'
                    ]);
                    $ctx['numbers_group']=[]; $ctx['amount']=null; $ctx['meta']=[]; $ctx['current_type']=null;
                    return;
                }

                // Sinh C(n,2) cặp số
                $numberPairs = [];
                for ($i = 0; $i < count($numbers); $i++) {
                    for ($j = $i + 1; $j < count($numbers); $j++) {
                        $numberPairs[] = [$numbers[$i], $numbers[$j]];
                    }
                }

                // Sinh C(m,2) cặp đài
                $stationPairs = [];
                for ($i = 0; $i < $stationCount; $i++) {
                    for ($j = $i + 1; $j < $stationCount; $j++) {
                        $stationPairs[] = [$stations[$i], $stations[$j]];
                    }
                }

                // Join stations thành string "station1 + station2"
                $stationString = count($stations) === 1 ? $stations[0] : implode(' + ', $stations);

                // Emit mỗi cặp số là 1 vé
                // Đá xiên: KHÔNG nhân bản theo đài, chỉ 1 vé với tất cả cặp số
                foreach ($numberPairs as $pair) {
                    $outBets[] = [
                        'numbers' => $pair,
                        'type'    => 'da_xien',
                        'amount'  => $amount,
                        'meta'    => [
                            'station_mode' => 'across',
                            'station_pairs' => $stationPairs,
                            'dai_count' => $stationCount,
                        ],
                        'station' => $stationString,
                    ];
                }

                $addEvent($events, 'emit_da_xien', [
                    'number_pairs' => $numberPairs,
                    'station_pairs' => $stationPairs,
                    'stations' => $stations
                ]);
                $ctx['numbers_group']=[]; $ctx['amount']=null; $ctx['meta']=[]; $ctx['current_type']=null;
                return;
            }

            // fallback
            $emitBet($outBets, $ctx, [
                'numbers' => $numbers,
                'type'    => $type,
                'amount'  => $amount,
                'meta'    => $ctx['meta'] ?? [],
            ]);
            $ctx['numbers_group']=[]; $ctx['amount']=null; $ctx['meta']=[]; $ctx['current_type']=null;
        };
    
        // ---------- 1) normalize ----------
        $normalized = $stripAccents($message);
        $tokens     = $splitTokens($normalized);
    
        // region chuẩn
        $region = $context['region'] ?? session('global_region', 'nam'); // bac|trung|nam
        $region = match (strtolower((string)$region)) {
            'bac','mb'   => 'bac',
            'trung','mt' => 'trung',
            default      => 'nam',
        };

        // Lấy date để resolve đài chính
        $bettingDate = $context['date'] ?? session('global_date', now()->format('Y-m-d'));

        // defaultStations: Auto-resolve đài chính theo lịch (date + region)
        $defaultStations = [];
        try {
            $mainStation = $this->scheduleService->getNStations(1, $bettingDate, $region);
            if (!empty($mainStation)) {
                $defaultStations = $mainStation;
            } else {
                // Fallback nếu không có đài trong lịch
                $defaultStations = match ($region) {
                    'bac'  => ['mien bac'],
                    'trung'=> ['da nang'],
                    default=> ['tp.hcm'],
                };
            }
        } catch (\Exception $e) {
            // Fallback nếu có lỗi
            $defaultStations = match ($region) {
                'bac'  => ['mien bac'],
                'trung'=> ['da nang'],
                default=> ['tp.hcm'],
            };
        }

        $events = [];
        $addEvent($events, 'stations_default', [
            'list' => $defaultStations,
            'date' => $bettingDate,
            'region' => $region
        ]);
    
        // ---------- 2) state ----------
        $ctx = [
            'region'              => $region,
            'betting_date'        => $bettingDate,
            'stations'            => [],
            'numbers_group'       => [],
            'current_type'        => null,
            'amount'              => null,
            'meta'                => [],
            'pair_d_dau'          => [],
            'xc_d_list'           => [],
            'xc_dd_amount'        => null,
            'last_numbers'        => [],
            'last_type'           => null,  // track kiểu cược trước để kế thừa
            'just_saw_station'    => false,
            'last_token_type'     => null,  // track loại token trước: 'number', 'd', 'dd', etc.

            // NEW: chế độ bắt N đài (2d/3d/4d/2dai/3dai/4dai)
            'dai_count'           => null,  // 2|3|4
            'dai_capture_remaining'=> 0,    // còn bao nhiêu đài cần bắt sau token Ndai
        ];
    
        $outBets = [];
    
        // ---------- 3) dicts ----------
        $stationAliases = [
            // Miền Nam
            'hcm'=>'tp.hcm', 'sg'=>'tp.hcm', 'tp'=>'tp.hcm',
            'tn'=>'tay ninh', 'ag'=>'an giang', 'tg'=>'tien giang', 'bt'=>'ben tre',
            'vl'=>'vinh long', 'tv'=>'tra vinh', 'kg'=>'kien giang', 'dl'=>'da lat',
            'cm'=>'ca mau', 'ct'=>'can tho', 'dn'=>'dong nai',
            'dthap'=>'dong thap', // không dùng 'dt' vì conflict với đá thẳng
            'st'=>'soc trang', 'vt'=>'vung tau', 'la'=>'long an', 'bp'=>'binh phuoc',
            'hg'=>'hau giang', 'bd'=>'binh duong', 'db'=>'binh duong', 'sb'=>'binh duong',
            'bl'=>'bac lieu', 'bth'=>'binh thuan',
            // Miền Bắc
            'hn'=>'mien bac', 'mb'=>'mien bac',
            // Miền Trung
            'dna'=>'da nang', 'kh'=>'khanh hoa', 'py'=>'phu yen', 'qna'=>'quang nam',
            'qng'=>'quang ngai', 'bdi'=>'binh dinh', 'tth'=>'thua thien hue',
        ];
        $typeAliases = [
            'lo'=>'bao_lo','blo'=>'bao_lo','bao'=>'bao_lo','baolo'=>'bao_lo','b2s'=>'bao_lo','b2'=>'bao_lo','b3s'=>'bao_lo','b3'=>'bao_lo','b4s'=>'bao_lo','b4'=>'bao_lo','bd'=>'bao_lo_dao','baodao'=>'bao_lo_dao','bdao'=>'bao_lo_dao','bld'=>'bao_lo_dao','bldao'=>'bao_lo_dao','daolo'=>'bao_lo_dao',
            'dau'=>'dau','đầu'=>'dau','d'=>'dau',
            'duoi'=>'duoi','đuôi'=>'duoi','dui'=>'duoi',
            'dauduoi'=>'dau_duoi','đầuđuôi'=>'dau_duoi','dd'=>'dau_duoi',
            'xc'=>'xiu_chu','x'=>'xiu_chu','xiu'=>'xiu_chu','tl'=>'xiu_chu','dxc'=>'xiu_chu_dao','xcd'=>'xiu_chu_dao','xd'=>'xiu_chu_dao','daoxc'=>'xiu_chu_dao','xcdao'=>'xiu_chu_dao','xcdau'=>'xiu_chu_dau','dauxc'=>'xiu_chu_dau','xdau'=>'xiu_chu_dau','xcduoi'=>'xiu_chu_duoi','duoixc'=>'xiu_chu_duoi','xcdui'=>'xiu_chu_duoi','xduoi'=>'xiu_chu_duoi','xdui'=>'xiu_chu_duoi',
            'keo'=>'keo_hang_don_vi','dt'=>'da_thang','dth'=>'da_thang','dathang'=>'da_thang','dat'=>'da_thang','dx'=>'da_xien','xien'=>'da_xien','daxien'=>'da_xien','da xien'=>'da_xien','dacheo'=>'da_xien','da cheo'=>'da_xien','cheo'=>'da_xien','dax'=>'da_xien','đax'=>'da_xien',
            'xien2'=>'xien_2','xh'=>'xien_2','xhai'=>'xien_2','xienhai'=>'xien_2',
            'xien3'=>'xien_3','xienba'=>'xien_3','xba'=>'xien_3','x3'=>'xien_3','xi3'=>'xien_3','xn3'=>'xien_3',
            'xien4'=>'xien_4','xienbon'=>'xien_4','xbon'=>'xien_4','x4'=>'xien_4','xi4'=>'xien_4','xn4'=>'xien_4',  
            'kng'=>'keo_hang_ngan','keongan'=>'keo_hang_ngan','keohangngan'=>'keo_hang_ngan','kngan'=>'keo_hang_ngan',
            'ktr'=>'keo_hang_tram','keotram'=>'keo_hang_tram','keohangtram'=>'keo_hang_tram','ktram'=>'keo_hang_tram',
            'kch'=>'keo_hang_chuc','keochuc'=>'keo_hang_chuc','keohangchuc'=>'keo_hang_chuc','kchuc'=>'keo_hang_chuc',
            'Sc'=>'xiu_chu','xc'=>'xiu_chu','x'=>'xiu_chu','xiu'=>'xiu_chu','tl'=>'xiu_chu',
            'xccap'=>'xiu_chu_cap','xiuchucap'=>'xiu_chu_cap','xchucap'=>'xiu_chu_cap',
            'dedaudacbiet'=>'de_dau_dac_biet','dedaugiai1'=>'de_dau_giai_1','degi1'=>'de_giai_1',
            'giap'=>'giap_all','kepbang'=>'kep_bang','keplech'=>'kep_lech',
            'tongto'=>'tong_to','tongbe'=>'tong_be','tongchan'=>'tong_chan','tongle'=>'tong_le',
            'baylo'=>'bay_lo','baylodao'=>'bay_lo_dao','daobaylo'=>'bay_lo_dao',
            'tamlo'=>'tam_lo','tamlodao'=>'tam_lo_dao','daotamlo'=>'tam_lo_dao',
            'xcddau'=>'xiu_chu_dao_dau','xcdaud'=>'xiu_chu_dao_dau',
            'xcdduoi'=>'xiu_chu_dao_duoi','xcduoid'=>'xiu_chu_dao_duoi',
        ];
    
        // ---------- 4) scan ----------
        foreach ($tokens as $tok) {
    
            // Ndai / Nd
            if (preg_match('/^([234])d(ai)?$/', $tok, $m)) {
                $count = (int)$m[1];
                // nếu đang có group pending → flush trước khi chuyển ngữ cảnh
                if ($isGroupPending($ctx)) {
                    $flushGroup($outBets, $ctx, $events, 'dai_token_switch_flush');
                }
                // bật chế độ bắt N đài
                $ctx['dai_count']            = $count;
                $ctx['dai_capture_remaining']= $count;
                $ctx['stations']             = []; // reset list để bắt mới
                $ctx['last_numbers']         = []; // reset để không kế thừa số từ group cũ
                $addEvent($events, 'dai_count_set', ['count'=>$count,'token'=>$tok]);
                continue;
            }
    
            // số 2-4 chữ số (giữ leading zero)
            if (preg_match('/^\d{2,4}$/', $tok)) {
                // QUAN TRỌNG: Nếu đã có AMOUNT, group đã hoàn chỉnh (type + amount)
                // → flush trước khi thêm số mới (số mới thuộc group tiếp theo)
                if (!empty($ctx['amount']) && $isGroupPending($ctx)) {
                    $flushGroup($outBets, $ctx, $events, 'amount_complete_auto_flush');
                    // Reset dai_count after auto flush (similar to combo token)
                    $ctx['dai_count'] = null;
                    $ctx['dai_capture_remaining'] = 0;
                }

                // Nếu token trước là 'd' và đang có group pending với pair_d_dau
                // → flush group cũ trước khi bắt đầu nhóm số mới
                if ($ctx['last_token_type'] === 'd' && !empty($ctx['pair_d_dau']) && $isGroupPending($ctx)) {
                    $flushGroup($outBets, $ctx, $events, 'd_then_number_flush');
                    // Reset dai_count after flush
                    $ctx['dai_count'] = null;
                    $ctx['dai_capture_remaining'] = 0;
                }

                // nếu đang ở 'kéo' và có start → expand
                if (($ctx['current_type'] ?? null) === 'keo_hang_don_vi' && !empty($ctx['meta']['keo_start'])) {
                    $start = (string)$ctx['meta']['keo_start'];
                    $end   = (string)$tok;
                    $expanded = $expandKeoNumbers($start, $end);
                    if (!empty($expanded)) {
                        // QUAN TRỌNG: THÊM vào numbers_group thay vì replace
                        // Để hỗ trợ multiple keo: "00 keo 09. 20 keo 29" → [00-09] + [20-29]
                        $ctx['numbers_group'] = array_merge($ctx['numbers_group'], $expanded);
                        $ctx['numbers_group'] = array_values(array_unique($ctx['numbers_group'])); // Remove duplicates
                        $ctx['last_numbers']  = $ctx['numbers_group'];
                        $addEvent($events, 'keo_expand', ['start'=>$start,'end'=>$end,'expanded'=>$expanded,'total_numbers'=>count($ctx['numbers_group'])]);
                    } else {
                        $ctx['numbers_group'][] = $tok;
                        $ctx['last_numbers']    = $ctx['numbers_group'];
                        $addEvent($events, 'number', ['value'=>$tok,'note'=>'keo_expand_failed']);
                    }
                    $ctx['just_saw_station'] = false;
                    $ctx['last_token_type'] = 'number';
                    continue;
                }
                $ctx['numbers_group'][] = $tok;
                $ctx['last_numbers']    = $ctx['numbers_group'];
                $ctx['just_saw_station']= false;
                $ctx['last_token_type'] = 'number';
                $addEvent($events, 'number', ['value'=>$tok]);
                continue;
            }
    
            if ($tok === '.') {
                if ($isGroupPending($ctx)) $flushGroup($outBets, $ctx, $events, 'dot_flush_or_hold');
                else $addEvent($events, 'dot_flush_or_hold');

                // CRITICAL: Reset dai_count, stations, last_numbers, and last_type after period (end of betting slip)
                // Quy tắc: Sau khi kết thúc phiếu cược gặp đài mới phải flush hết để nhận đài mới
                // last_numbers và last_type cũng phải reset để tránh kế thừa từ phiếu cược trước sang phiếu mới
                $ctx['dai_count'] = null;
                $ctx['dai_capture_remaining'] = 0;
                $ctx['stations'] = []; // Reset stations to accept new station after period
                $ctx['last_numbers'] = []; // Reset last_numbers to prevent inheritance across betting slips
                $ctx['last_type'] = null; // Reset last_type to prevent type inheritance across betting slips

                $ctx['just_saw_station'] = false;
                continue;
            }
    
            // Hỗ trợ số nguyên và số thập phân: 5n, 3.5n, 7.5n
            if (preg_match('/^(\d+(?:\.\d+)?)(n|k)$/', $tok, $m)) {
                $newAmount = (int)round((float)$m[1] * 1000);

                // QUAN TRỌNG: Flush nếu đã có amount cũ và group pending
                // Để tránh amount mới ghi đè amount cũ trước khi flush
                // VD: "xc 816 150n 633 40n" → flush [633] với 40n trước khi gặp amount mới
                if (!empty($ctx['amount']) && $isGroupPending($ctx)) {
                    $flushGroup($outBets, $ctx, $events, 'amount_token_auto_flush');
                }

                $ctx['amount'] = $newAmount;
                $addEvent($events, 'amount_loose', [
                    'token'=>$tok, 'type'=>$ctx['current_type'] ?? null, 'amount'=>$ctx['amount']
                ]);
                $ctx['just_saw_station'] = false;
                continue;
            }
    
            // Combo token với số thập phân: lo5n, dd3.5n, d7.5n
            if (preg_match('/^(d|dd|lo)(\d+(?:\.\d+)?)(n|k)$/', $tok, $m)) {
                $code = $m[1]; $amt = (int)round((float)$m[2] * 1000);

                if (($ctx['current_type'] ?? null) === 'xiu_chu') {
                    if ($code === 'dd') { $ctx['xc_dd_amount'] = $amt; $addEvent($events,'xc_pair_dd',['token'=>$tok,'amount'=>$amt]); }
                    elseif ($code === 'd') { $ctx['xc_d_list'][] = $amt; $addEvent($events,'xc_pair_d',['token'=>$tok,'amount'=>$amt,'index'=>count($ctx['xc_d_list'])]); }
                    else { $ctx['amount'] = $amt; $addEvent($events,'xc_amount_through_lo',['token'=>$tok,'amount'=>$amt]); }
                    $ctx['just_saw_station'] = false;
                    continue;
                }

                $targetType = ($code==='lo') ? 'bao_lo' : (($code==='dd') ? 'dau_duoi' : 'dau');
                if (($ctx['current_type'] ?? null) !== null && $targetType !== $ctx['current_type'] && $isGroupPending($ctx)) {
                    $flushGroup($outBets, $ctx, $events, 'type_switch_flush');
                }

                if (empty($ctx['numbers_group']) && !empty($ctx['last_numbers'])) {
                    $ctx['numbers_group'] = $ctx['last_numbers'];
                    $addEvent($events,'inherit_numbers_for_amount',['numbers'=>$ctx['numbers_group']]);
                }

                if ($targetType==='bao_lo') {
                    $ctx['current_type']='bao_lo';
                    $ctx['amount']=$amt;
                    $ctx['last_token_type'] = 'lo';
                    $addEvent($events,'pair_combo',['token'=>$tok,'type'=>'bao_lo','amount'=>$amt]);
                }
                elseif ($targetType==='dau_duoi') {
                    $ctx['current_type']='dau_duoi';
                    $ctx['amount']=$amt;
                    $ctx['last_token_type'] = 'dd';
                    $addEvent($events,'pair_combo',['token'=>$tok,'type'=>'dau_duoi','amount'=>$amt]);
                }
                else {
                    // Đặc biệt cho d...n: KHÔNG flush ngay để cho phép nhận cả 2 d tokens (đầu + đuôi)
                    $ctx['current_type']='dau';
                    $ctx['pair_d_dau'][]=$amt;
                    $ctx['last_token_type'] = 'd';
                    $addEvent($events,'pair_combo',['token'=>$tok,'type'=>'dau','amount'=>$amt,'pair_d_count'=>count($ctx['pair_d_dau'])]);
                }

                // QUAN TRỌNG: Flush ngay sau combo token để không kéo số tiếp theo vào cùng group
                // NGOẠI LỆ: Với d...n token, KHÔNG flush ngay để cho phép thu thập cả đầu và đuôi
                if ($isGroupPending($ctx) && $targetType !== 'dau') {
                    $flushGroup($outBets, $ctx, $events, 'combo_token_auto_flush');
                    // Clear last_numbers sau combo token để tránh kế thừa sang type khác
                    $ctx['last_numbers'] = [];
                    // CRITICAL: Reset dai_count after combo token flush
                    // Nếu có 2dai, sau khi flush combo token (vd: "2dai 28 dau 125n")
                    // thì reset dai_count để không apply cho group tiếp theo
                    $ctx['dai_count'] = null;
                    $ctx['dai_capture_remaining'] = 0;
                }

                $ctx['just_saw_station'] = false;
                continue;
            }
    
            // xiên MB
            if (preg_match('/^(xi(?:en)?([234]))$/', $tok, $m)) {
                $size = (int)$m[2];
                if ($region !== 'bac') {
                    $addEvent($events, 'skip_xien_wrong_region', [
                        'token'=>$tok,'region'=>$region,
                        'message'=>'Loại cược xiên ('.$tok.') chỉ áp dụng cho Miền Bắc. Khu vực hiện tại: '.$region.'.'
                    ]);
                    $errors[] = 'Xiên chỉ áp dụng cho Miền Bắc. Token: '.$tok;
                    $ctx['just_saw_station'] = false;
                    continue;
                }
                if (($ctx['current_type'] ?? null) !== null && 'xien' !== $ctx['current_type'] && $isGroupPending($ctx)) {
                    $flushGroup($outBets, $ctx, $events, 'type_switch_flush');
                }
                // KẾ THỪA SỐ CHỈ KHI không vừa mới thấy station
                if (empty($ctx['numbers_group']) && !empty($ctx['last_numbers']) && !($ctx['just_saw_station'] ?? false)) {
                    $ctx['numbers_group'] = $ctx['last_numbers'];
                    $addEvent($events,'inherit_numbers_for_type',['type'=>'xien','numbers'=>$ctx['numbers_group']]);
                }
                $ctx['current_type']='xien';
                $ctx['meta']['xien_size']=$size;
                $ctx['just_saw_station']=false;
                $addEvent($events, 'type_loose', ['token'=>$tok,'type'=>'xien','xien_size'=>$size]);
                continue;
            }
    
            // type rời (kéo/lo/dau/duoi/xc…)
            if (isset($typeAliases[$tok])) {
                $newType = $typeAliases[$tok];
                
                // Special handling for 'keo': pop số cuối TRƯỚC KHI flush
                if ($newType === 'keo_hang_don_vi') {
                    $start = null;
                    if (!empty($ctx['numbers_group'])) {
                        // POP số cuối ra khỏi numbers_group TRƯỚC
                        $start = array_pop($ctx['numbers_group']);
                        $addEvent($events, 'keo_pop_start', ['start' => $start, 'remaining' => $ctx['numbers_group']]);
                    } elseif (!empty($ctx['last_numbers'])) {
                        $start = end($ctx['last_numbers']);
                    }
                    
                    // BÂY GIỜ mới flush group (không có số keo_start nữa)
                    if (($ctx['current_type'] ?? null) !== null && $newType !== $ctx['current_type'] && $isGroupPending($ctx)) {
                        $flushGroup($outBets, $ctx, $events, 'type_switch_flush');
                    }
                    
                    $ctx['current_type'] = 'keo_hang_don_vi';
                    $ctx['meta']['keo_start'] = $start;
                    // KHÔNG reset stations - kéo kế thừa station từ group trước
                    $ctx['just_saw_station'] = false;
                    $addEvent($events, 'type_loose', ['token' => $tok, 'type' => 'keo_hang_don_vi', 'keo_start' => $start]);
                    continue;
                }
                
                // Normal type switch
                if (($ctx['current_type'] ?? null) !== null && $newType !== $ctx['current_type'] && $isGroupPending($ctx)) {
                    $flushGroup($outBets, $ctx, $events, 'type_switch_flush');
                }

                // KẾ THỪA SỐ CHỈ KHI:
                // - numbers_group đang rỗng
                // - có last_numbers
                // - token trước KHÔNG phải là station (just_saw_station = false)
                // Quy tắc: "Khi xuất hiện kiểu cược, xét phía trước kiểu cược là đài hay tiền cược"
                // - Nếu trước là đài → KHÔNG kế thừa số
                // - Nếu trước là tiền/số → kế thừa số
                if (empty($ctx['numbers_group']) && !empty($ctx['last_numbers']) && !($ctx['just_saw_station'] ?? false)) {
                    $ctx['numbers_group']=$ctx['last_numbers'];
                    $addEvent($events,'inherit_numbers_for_type',['type'=>$newType,'numbers'=>$ctx['numbers_group']]);
                }
                $ctx['current_type']=$newType;
                $ctx['just_saw_station']=false;
                $addEvent($events, 'type_loose', ['token'=>$tok,'type'=>$ctx['current_type']]);
                continue;
            }
    
            // station
            if (isset($stationAliases[$tok])) {
                $name = $stationAliases[$tok];
            
                // 1) Đang ở chế độ "bắt N đài" (Ndai)
                if ($ctx['dai_capture_remaining'] > 0) {
                    // Nếu đã có group pending (số, loại, tiền) → flush group đó trước
                    if ($isGroupPending($ctx)) {
                        // LƯU dai_count trước khi flush để bets có thể dùng
                        $savedDaiCount = $ctx['dai_count'];
                        $flushGroup($outBets, $ctx, $events, 'ndai_group_complete_flush');
                        // Khôi phục dai_count để các bets vừa emit có thể tìm thấy nó
                        // Không, bets đã có dai_count trong meta rồi trong emitBet
                        // Reset chế độ Ndai
                        $ctx['dai_count'] = null;
                        $ctx['dai_capture_remaining'] = 0;
                        $ctx['stations'] = []; // Clear stations list
                        // Bắt đầu group mới với station này
                        $ctx['stations'] = [$name];
                        $ctx['just_saw_station'] = true; // SET FLAG
                        $addEvent($events, 'ndai_reset_new_station', ['new_station' => $name]);
                        continue;
                    }
                    
                    // Chưa có group pending → thu thập đài cho đủ
                    if (!in_array($name, $ctx['stations'], true)) {
                        $ctx['stations'][] = $name;
                        $ctx['dai_capture_remaining']--;
                        $addEvent($events, 'dai_capture_station', [
                            'captured' => $name,
                            'remain'   => $ctx['dai_capture_remaining'],
                            'stations' => $ctx['stations'],
                        ]);
                    }
                    if ($ctx['dai_capture_remaining'] === 0) {
                        $addEvent($events, 'dai_capture_done', ['stations' => $ctx['stations']]);
                    }
                    continue;
                }
            
                // 2) Không ở chế độ Ndai:
                // - Nếu đang có group pending → flush rồi bắt bộ đài mới (trường hợp đài xuất hiện sau khi đã có số/kiểu/tiền)
                if ($isGroupPending($ctx)) {
                    $flushGroup($outBets, $ctx, $events, 'station_switch_flush');
                    $ctx['stations'] = [$name];
                    $ctx['just_saw_station'] = true; // SET FLAG
                    $addEvent($events, 'stations', ['set' => array_values($ctx['stations'])]);
                    continue;
                }

                // - Nếu CHƯA có kiểu cược → coi là phase "khai báo đài", cộng dồn nhiều mã đài liên tiếp
                //   (cho phép "14 27 72 tn bt dx" → 2 đài cho đá xiên)
                if ($ctx['current_type'] === null) {
                    if (!in_array($name, $ctx['stations'], true)) {
                        $ctx['stations'][] = $name;
                    }
                    $ctx['just_saw_station'] = true; // SET FLAG
                    $addEvent($events, 'stations', ['set' => array_values($ctx['stations'])]);
                    continue;
                }

                // - Còn lại: đã có kiểu cược → bắt đầu bộ đài mới (không cộng dồn)
                $ctx['stations'] = [$name];
                $ctx['just_saw_station'] = true; // SET FLAG
                $addEvent($events, 'stations', ['set' => array_values($ctx['stations'])]);
                continue;
            }
    
            // skip
            $addEvent($events, 'skip', ['token'=>$tok]);
        }
    
        // ---------- 5) final flush ----------
        $flushGroup($outBets, $ctx, $events, 'final_flush');
    
        // Áp station cuối:
        // - Nếu đã chỉ rõ stations → lưu tạm vào meta
        // - Nếu CÓ Ndai nhưng KHÔNG chỉ rõ stations → Auto resolve theo lịch
        // - Nếu KHÔNG có cả hai → fallback defaultStations
        
        // Lấy date từ context để resolve đài
        $bettingDate = $context['date'] ?? session('global_date', now()->format('Y-m-d'));
        
        foreach ($outBets as &$b) {
            $hasStation = !empty($b['station']);
            $hasNdaiMeta = !empty($b['meta']['dai_count']);
    
            if (!$hasStation) {
                if ($hasNdaiMeta) {
                    // Case 2: Có Ndai → Auto resolve theo lịch (ưu tiên trước ctx['stations'])
                    // Vì ctx['stations'] có thể bị contaminate từ group sau
                    $daiCount = (int)$b['meta']['dai_count'];
                    
                    // Chỉ auto resolve cho miền Nam và Trung (theo yêu cầu DOC_FUNC.md)
                    if (in_array($region, ['nam', 'trung'], true) && $daiCount >= 2 && $daiCount <= 4) {
                        try {
                            $autoStations = $this->scheduleService->getNStations($daiCount, $bettingDate, $region);
                            
                            if (!empty($autoStations)) {
                                // Lưu list stations để expand sau
                                $b['meta']['_stations_to_expand'] = $autoStations;
                                $addEvent($events, 'station_auto_resolved', [
                                    'dai_count' => $daiCount,
                                    'region' => $region,
                                    'date' => $bettingDate,
                                    'resolved_stations' => $autoStations,
                                    'will_expand' => true
                                ]);
                            } else {
                                // Không có đài trong lịch → fallback single station
                                $b['station'] = $joinStations($defaultStations);
                                $addEvent($events, 'station_auto_resolve_failed_fallback', [
                                    'dai_count' => $daiCount,
                                    'fallback' => $defaultStations
                                ]);
                            }
                        } catch (\Exception $e) {
                            // Có lỗi → fallback single station
                            $b['station'] = $joinStations($defaultStations);
                            $addEvent($events, 'station_auto_resolve_error', [
                                'error' => $e->getMessage(),
                                'fallback' => $defaultStations
                            ]);
                        }
                    } else {
                        // Miền Bắc hoặc số đài không hợp lệ → giữ null
                        $b['station'] = null;
                        $addEvent($events, 'station_ndai_keep_null', [
                            'region' => $region,
                            'dai_count' => $daiCount,
                            'reason' => 'Miền Bắc hoặc dai_count không hợp lệ'
                        ]);
                    }
                } elseif (!empty($b['meta']['_ctx_stations_snapshot'])) {
                    // Case 1: User đã chỉ định đài cụ thể (vd: 2dai tn ag)
                    // Lưu list stations vào meta để expand sau
                    // Dùng snapshot tại thời điểm emit, KHÔNG dùng ctx['stations'] global
                    // để tránh contamination từ các group sau
                    $stations = $b['meta']['_ctx_stations_snapshot'];
                    unset($b['meta']['_ctx_stations_snapshot']); // Clean up

                    if (!empty($stations)) {
                        $b['meta']['_stations_to_expand'] = $stations;
                        $addEvent($events, 'station_from_explicit', [
                            'stations' => $stations,
                            'will_expand' => true
                        ]);
                    }
                } else {
                    // Case 3: Không có gì → Auto resolve đài chính từ lịch (theo date + region)
                    // Theo DOC_FUNC.md: cần check thứ và miền để lấy đài chính cho đúng
                    if (in_array($region, ['nam', 'trung'], true)) {
                        try {
                            // Resolve đài chính (1 station)
                            $mainStation = $this->scheduleService->getNStations(1, $bettingDate, $region);
                            
                            if (!empty($mainStation)) {
                                $b['station'] = $mainStation[0];
                                $addEvent($events, 'station_auto_resolved_main', [
                                    'region' => $region,
                                    'date' => $bettingDate,
                                    'resolved_station' => $mainStation[0],
                                ]);
                            } else {
                                // Không có đài trong lịch → fallback defaultStations
                                $b['station'] = $joinStations($defaultStations);
                                $addEvent($events, 'station_auto_resolve_failed_fallback_default', [
                                    'region' => $region,
                                    'date' => $bettingDate,
                                    'fallback' => $defaultStations
                                ]);
                            }
                        } catch (\Exception $e) {
                            // Có lỗi → fallback defaultStations
                            $b['station'] = $joinStations($defaultStations);
                            $addEvent($events, 'station_auto_resolve_error_fallback', [
                                'error' => $e->getMessage(),
                                'region' => $region,
                                'date' => $bettingDate,
                                'fallback' => $defaultStations
                            ]);
                        }
                    } else {
                        // Miền Bắc hoặc region không hợp lệ → dùng defaultStations
                        $b['station'] = $joinStations($defaultStations);
                        $addEvent($events, 'station_from_default', [
                            'region' => $region,
                            'stations' => $defaultStations
                        ]);
                    }
                }
            }
            $b['meta'] = $b['meta'] ?? [];
        }
        unset($b);
        
        // === EXPAND BETS với nhiều đài ===
        // Nếu bet có _stations_to_expand → tạo nhiều bets riêng (1 cho mỗi đài)
        $expandedBets = [];
        foreach ($outBets as $bet) {
            if (!empty($bet['meta']['_stations_to_expand'])) {
                $stations = $bet['meta']['_stations_to_expand'];
                unset($bet['meta']['_stations_to_expand']);
                unset($bet['meta']['dai_count']); // Không cần dai_count nữa sau khi expand
                
                foreach ($stations as $station) {
                    $clone = $bet;
                    $clone['station'] = $station;
                    $expandedBets[] = $clone;
                }
                
                $addEvent($events, 'bet_expanded_to_multiple_stations', [
                    'original_bet_type' => $bet['type'],
                    'stations' => $stations,
                    'expanded_count' => count($stations)
                ]);
            } else {
                // Bet không cần expand
                $expandedBets[] = $bet;
            }
        }

        $outBets = $expandedBets;

        // Build highlighted message with skipped tokens marked in red
        $highlightedMessage = $this->buildHighlightedMessage($message, $tokens, $events);

        if (empty($outBets)) {
            return [
                'is_valid'        => false,
                'multiple_bets'   => [],
                'errors'          => $errors,
                'normalized'      => $normalized,
                'parsed_message'  => $normalized,
                'highlighted_message' => $highlightedMessage,
                'tokens'          => $tokens,
                'debug'           => [
                    'stations_default' => $defaultStations,
                    'events'           => $events,
                ],
            ];
        }

        return [
            'is_valid'        => true,
            'multiple_bets'   => $outBets,
            'errors'          => $errors,
            'normalized'      => $normalized,
            'parsed_message'  => $normalized,
            'highlighted_message' => $highlightedMessage,
            'tokens'          => $tokens,
            'debug'           => [
                    'stations_default' => $defaultStations,
                    'events'           => $events,
            ],
        ];
    }

    
    /**
     * Build highlighted message with problematic tokens marked with appropriate colors and tooltips.
     *
     * @param string $originalMessage Original user input
     * @param array $tokens Parsed tokens
     * @param array $events Parse events
     * @return string HTML string with highlighted problematic tokens
     */
    private function buildHighlightedMessage(string $originalMessage, array $tokens, array $events): string
    {
        // Collect problematic tokens from events with their severity and reasons
        $highlights = [];

        foreach ($events as $event) {
            $kind = $event['kind'] ?? '';
            $token = $event['token'] ?? null;

            // Skip tokens (không nhận dạng)
            if ($kind === 'skip' && $token !== null) {
                $highlights[] = [
                    'token' => $token,
                    'severity' => 'error',
                    'reason' => 'Token không được nhận dạng',
                    'detail' => 'Từ khóa này không thuộc bất kỳ loại cược, đài, hoặc cú pháp nào hợp lệ.'
                ];
            }

            // Error events
            if (str_starts_with($kind, 'error_')) {
                $detail = $this->buildErrorDetail($kind, $event);

                // Try to extract token from event data
                $errorToken = $this->extractTokenFromEvent($event, $tokens);

                if ($errorToken !== null) {
                    $highlights[] = [
                        'token' => $errorToken,
                        'severity' => 'error',
                        'reason' => $this->formatEventKind($kind),
                        'detail' => $detail
                    ];
                }
            }

            // Warning events
            if (str_starts_with($kind, 'warning_')) {
                $detail = $this->buildWarningDetail($kind, $event);
                $warningToken = $this->extractTokenFromEvent($event, $tokens);

                if ($warningToken !== null) {
                    $highlights[] = [
                        'token' => $warningToken,
                        'severity' => 'warning',
                        'reason' => $this->formatEventKind($kind),
                        'detail' => $detail
                    ];
                }
            }

            // Block events (operations blocked for a reason)
            if (str_starts_with($kind, 'block_')) {
                $detail = $this->buildBlockDetail($kind, $event);
                $blockToken = $this->extractTokenFromEvent($event, $tokens);

                if ($blockToken !== null) {
                    $highlights[] = [
                        'token' => $blockToken,
                        'severity' => 'block',
                        'reason' => $this->formatEventKind($kind),
                        'detail' => $detail
                    ];
                }
            }
        }

        if (empty($highlights)) {
            // No issues, return original message as-is (HTML escaped)
            return htmlspecialchars($originalMessage, ENT_QUOTES, 'UTF-8');
        }

        // Normalize message for matching
        $normalizedOriginal = $this->normalizeForMatching($originalMessage);

        // Find all positions of highlighted tokens in original message
        $positions = [];
        foreach ($highlights as $highlight) {
            $normalizedToken = $this->normalizeForMatching($highlight['token']);

            // Find all occurrences of token in message
            $offset = 0;
            while (($pos = mb_strpos($normalizedOriginal, $normalizedToken, $offset)) !== false) {
                $positions[] = [
                    'start' => $pos,
                    'end' => $pos + mb_strlen($highlight['token']),
                    'token' => mb_substr($originalMessage, $pos, mb_strlen($highlight['token'])),
                    'severity' => $highlight['severity'],
                    'reason' => $highlight['reason'],
                    'detail' => $highlight['detail']
                ];
                $offset = $pos + 1; // Move past this match
            }
        }

        if (empty($positions)) {
            return htmlspecialchars($originalMessage, ENT_QUOTES, 'UTF-8');
        }

        // Sort positions by start index
        usort($positions, fn($a, $b) => $a['start'] <=> $b['start']);

        // Remove overlapping positions (keep first occurrence with highest severity)
        $filteredPositions = [];
        $lastEnd = -1;
        foreach ($positions as $pos) {
            if ($pos['start'] >= $lastEnd) {
                $filteredPositions[] = $pos;
                $lastEnd = $pos['end'];
            } elseif ($pos['severity'] === 'error' && end($filteredPositions)['severity'] !== 'error') {
                // Replace last position if current is error and last is not
                array_pop($filteredPositions);
                $filteredPositions[] = $pos;
                $lastEnd = $pos['end'];
            }
        }

        // Build highlighted message
        $highlightedMessage = '';
        $lastIndex = 0;

        foreach ($filteredPositions as $pos) {
            // Add text before this token
            $before = mb_substr($originalMessage, $lastIndex, $pos['start'] - $lastIndex);
            $highlightedMessage .= htmlspecialchars($before, ENT_QUOTES, 'UTF-8');

            // Add highlighted token with appropriate color and tooltip
            $cssClass = match ($pos['severity']) {
                'error' => 'text-red-600 font-semibold',
                'warning' => 'text-yellow-600 font-semibold',
                'block' => 'text-orange-600 font-semibold',
                default => 'text-gray-600 font-semibold'
            };

            $title = htmlspecialchars($pos['reason'] . ': ' . $pos['detail'], ENT_QUOTES, 'UTF-8');

            $highlightedMessage .= sprintf(
                '<span class="%s" title="%s">%s</span>',
                $cssClass,
                $title,
                htmlspecialchars($pos['token'], ENT_QUOTES, 'UTF-8')
            );

            $lastIndex = $pos['end'];
        }

        // Add remaining text after last token
        if ($lastIndex < mb_strlen($originalMessage)) {
            $after = mb_substr($originalMessage, $lastIndex);
            $highlightedMessage .= htmlspecialchars($after, ENT_QUOTES, 'UTF-8');
        }

        return $highlightedMessage;
    }

    /**
     * Normalize string for token matching (lowercase + remove accents)
     */
    private function normalizeForMatching(string $s): string
    {
        $s = mb_strtolower($s, 'UTF-8');
        $replacements = [
            'à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ' => 'a',
            'è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ'             => 'e',
            'ì|í|ị|ỉ|ĩ'                         => 'i',
            'ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ' => 'o',
            'ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ'             => 'u',
            'ỳ|ý|ỵ|ỷ|ỹ'                         => 'y',
            'đ'                                 => 'd',
        ];
        foreach ($replacements as $pattern => $replacement) {
            $s = preg_replace('/(' . $pattern . ')/u', $replacement, $s);
        }
        return $s;
    }

    /**
     * Build detailed error message from event
     */
    private function buildErrorDetail(string $kind, array $event): string
    {
        return match ($kind) {
            'error_xien_numbers_not_enough' => sprintf(
                'Xiên %d yêu cầu tối thiểu %d số, nhưng chỉ có %d số.',
                $event['need'] ?? 0,
                $event['need'] ?? 0,
                $event['have'] ?? 0
            ),
            'error_da_thang_wrong_station_count' => sprintf(
                'Đá thẳng yêu cầu đúng 1 đài, nhưng có %d đài.',
                $event['got'] ?? 0
            ),
            'error_da_xien_min_stations' => sprintf(
                'Đá xiên yêu cầu tối thiểu 2 đài, nhưng chỉ có %d đài.',
                $event['got'] ?? 0
            ),
            default => $event['message'] ?? 'Có lỗi xảy ra trong quá trình xử lý.'
        };
    }

    /**
     * Build detailed warning message from event
     */
    private function buildWarningDetail(string $kind, array $event): string
    {
        return match ($kind) {
            'warning_da_thang_odd_numbers' => sprintf(
                'Đá thẳng ghép cặp 2-2, số lẻ "%s" bị bỏ qua.',
                $event['dropped'] ?? ''
            ),
            default => $event['message'] ?? 'Cảnh báo về cú pháp hoặc dữ liệu.'
        };
    }

    /**
     * Build detailed block message from event
     */
    private function buildBlockDetail(string $kind, array $event): string
    {
        return match ($kind) {
            'block_emit_xien_wrong_region' => sprintf(
                'Xiên chỉ áp dụng cho Miền Bắc. Khu vực hiện tại: %s.',
                $event['region'] ?? 'không xác định'
            ),
            default => $event['message'] ?? 'Thao tác bị chặn.'
        };
    }

    /**
     * Format event kind to human-readable text
     */
    private function formatEventKind(string $kind): string
    {
        // Remove prefix
        $kind = preg_replace('/^(error_|warning_|block_)/', '', $kind);

        // Convert snake_case to Title Case
        $kind = str_replace('_', ' ', $kind);
        $kind = ucwords($kind);

        return $kind;
    }

    /**
     * Extract relevant token from event data
     */
    private function extractTokenFromEvent(array $event, array $allTokens): ?string
    {
        // Check if event has explicit token field
        if (isset($event['token']) && is_string($event['token'])) {
            return $event['token'];
        }

        // For xien errors, look for xien tokens
        if (isset($event['xien_size'])) {
            foreach ($allTokens as $tok) {
                if (preg_match('/^xi(?:en)?([234])$/', $tok)) {
                    return $tok;
                }
            }
        }

        // For station errors, return null (station context is complex)
        // We don't want to highlight specific stations for multi-station errors

        return null;
    }

    /* ========================= Normalize & Tokenize ========================= */

    private function normalize(string $s): string
    {
        $s = Str::lower($s);
        $s = Str::ascii($s);
        $s = str_replace(['đ','Đ'], ['d','d'], $s);

        // 2,5n -> 2.5n
        $s = preg_replace('/(?<=\d),(?=\d)/', '.', $s);

        // NEW: "t,pho" -> "tpho" (ghép dấu phẩy giữa các chữ cái)
        $s = preg_replace('/(?<=[a-z])\s*,\s*(?=[a-z])/', '', $s);

        // "56.65.12" -> "56 65 12", "56.65" -> "56 65"
        $s = preg_replace('/(\d+)\.(\d+)\.(\d+)/', '$1 $2 $3', $s);
        $s = preg_replace('/(\d+)\.(\d+)/', '$1 $2', $s);

        return preg_replace('/\s+/', ' ', trim($s)) ?? '';
    }

    private function tokenize(string $s): array
    {
        $s = str_replace(['.', ','], [' . ', ' , '], $s);
        $parts = preg_split('/\s+/', $s) ?: [];
        return array_values(array_filter($parts, static fn($t) => $t !== ''));
    }

    /* ========================= Classifiers & Parsers ========================= */

    private function isAmountToken(string $w): bool
    {
        // Có đơn vị n/k (có thể có 'x' & phần thập phân)
        if (preg_match('/^x?\d+(?:[.,]\d+)?(?:n|k)$/', $w)) {
            return true;
        }
        // Chỉ số & >=5 chữ số -> coi là amount (vd 10000)
        if (preg_match('/^\d{5,}$/', $w)) {
            return true;
        }
        return false;
    }

    private function parseAmount(string $w): int
    {
        $w = ltrim($w, 'x');
        $mult = 1;
        if (Str::endsWith($w, ['n','k'])) {
            $mult = 1000;
            $w = substr($w, 0, -1);
        }
        $w = str_replace(',', '.', $w);
        return (int)round(((float)$w) * $mult);
    }

    private function isPureNumberToken(string $w): bool
    {
        return (bool)preg_match('/^\d{1,4}$/', $w);
    }

    private function isStationToken(string $w): bool
    {
        $key = $this->cleanAlphaToken($w);
        return $key !== '' && isset($this->stationAliasMap[$key]);
    }

    private function canonicalStation(string $w): string
    {
        $key = $this->cleanAlphaToken($w);
        return $this->stationAliasMap[$key] ?? $key;
    }

    private function isMultiStationDirective(string $w): bool
    {
        $k = $this->cleanAlphaToken($w);
        return in_array($k, ['2d','3d','2dai','3dai'], true);
    }

    private function directiveSize(string $w): int
    {
        $k = $this->cleanAlphaToken($w);
        return Str::startsWith($k, '3') ? 3 : 2;
    }

    /**
     * Tách token dạng "tn21" => ['tay ninh', '21'] nếu "tn" là alias đài.
     * @return array{0:?string,1:?string}
     */
    private function splitStationNumberToken(string $w): array
    {
        if (!preg_match('/^([a-z]+)(\d{1,4})$/i', $w, $m)) {
            return [null, null];
        }
        $alias = $this->cleanAlphaToken($m[1]);  // 'tn'
        if ($alias === '' || !isset($this->stationAliasMap[$alias])) {
            return [null, null];
        }
        $station = $this->stationAliasMap[$alias]; // 'tay ninh'
        return [$station, $m[2]];                  // '21'
    }

    /**
     * Tạo dãy kéo: giữ nguyên các chữ số giống nhau giữa start & end,
     * tăng dần chữ số khác nhau từ start->end (bao gồm cả 2 đầu).
     * Ví dụ: "03" -> "93" => 03,13,23,33,43,53,63,73,83,93
     * Hỗ trợ cả 3 chữ số (chỉ đúng khi KHÁC đúng 1 vị trí).
     */
    private function generateKeoRange(string $start, string $end): array
    {
        $len = max(strlen($start), strlen($end));
        $s = str_pad($start, $len, '0', STR_PAD_LEFT);
        $e = str_pad($end,   $len, '0', STR_PAD_LEFT);

        // tìm vị trí khác nhau
        $diffIdx = [];
        for ($i = 0; $i < $len; $i++) {
            if ($s[$i] !== $e[$i]) $diffIdx[] = $i;
        }

        // chỉ hợp lệ khi khác đúng 1 vị trí (2 số giống nhau với 2-digit; 2 vị trí giống nhau với 3-digit)
        if (count($diffIdx) !== 1) {
            if ($s === $e) return [$s]; // kéo 1 điểm
            return [];                   // không hợp lệ
        }

        $idx  = $diffIdx[0];
        $from = (int) $s[$idx];
        $to   = (int) $e[$idx];

        // tạo range bao hàm
        $steps = ($from <= $to) ? range($from, $to) : range($from, $to); // range() PHP hỗ trợ giảm dần

        $out = [];
        foreach ($steps as $d) {
            $arr = str_split($s);
            $arr[$idx] = (string) $d;
            $num = implode('', $arr);
            $out[] = $num;
        }
        return $out;
    }

    /**
     * Tách token gộp "alias+amount": vd 'd100n', 'dd20', 'lo5n'
     * @return array{0:?string,1:?int}
     */
    private function splitTypeAmountToken(string $w): array
    {
        if (!preg_match('/^([a-z_]+)(x?\d+(?:[.,]\d+)?(?:n|k)?)$/i', $w, $m)) {
            return [null, null];
        }
        $alias = $this->cleanAlphaToken($m[1]);
        $type  = $this->typeAliasMap[$alias] ?? $this->mapFuzzyAlias($alias);
        if ($type === null) return [null, null];
        $type  = $this->canonicalizeTypeCode($type);

        $rawAmt = $m[2]; // có thể là '20' hoặc '20n'
        $amt    = $this->parseAmount($rawAmt);

        // Nếu KHÔNG có đuôi n/k thì mặc định nhân nghìn cho số <= 4 chữ số
        if (!preg_match('/[nk]$/i', $rawAmt) && preg_match('/^\d{1,4}(?:[.,]\d+)?$/', $rawAmt)) {
            $amt = (int) round($amt * 1000);
        }

        return [$type, $amt];
    }

    /**
     * Tách token kiểu "952xc13n" => [ '952', 'xiu_chu', 13000 ]
     * @return array{0:?string,1:?string,2:?int}
     */
    private function splitNumberTypeAmountToken(string $w): array
    {
        if (!preg_match('/^(\d{1,4})([a-z_]+)(x?\d+(?:[.,]\d+)?(?:n|k)?)$/i', $w, $m)) {
            return [null, null, null];
        }
        $alias = $this->cleanAlphaToken($m[2]);        // 'xc'
        $type  = $this->typeAliasMap[$alias] ?? $this->mapFuzzyAlias($alias);
        if ($type === null) return [null, null, null];
        $type  = $this->canonicalizeTypeCode($type);   // => 'xiu_chu'
        $num   = $m[1];                                // '952'
        $amt   = $this->parseAmount($m[3]);            // '13n' -> 13000
        return [$num, $type, $amt];
    }

    private function canonicalizeTypeCode(string $code): string
    {
        return match ($code) {
            'lo', 'bao', 'baolo', 'blo' => 'bao_lo',
            'd'                  => 'dau',
            'b'                  => 'duoi',
            'dd'                 => 'dau_duoi',
            'dx', 'dax', 'daxeo', 'dacheo' => 'da_xien',
            'dt' => 'da_thang',
            'xc', 'xiu', 'xiuchu', 'xiu_chu' => 'xiu_chu',
            'keo', 'keo_hang_don_vi' => 'keo_hang_don_vi',
            default              => $code,
        };
    }

    private function mapFuzzyAlias(string $alias): ?string
    {
        return match ($alias) {
            'd'  => 'dau',
            'dd' => 'dau_duoi',
            'b'  => 'duoi',
            'lo','bao','baolo','blo' => 'bao_lo',
            'de' => 'de',
            'dx','dax','daxeo','dacheo' => 'da_xien',
            'dt' => 'da_thang',
            'xc','xiu','xiuchu','xiu_chu' => 'xiu_chu',
            'keo', 'keo_hang_don_vi' => 'keo_hang_don_vi',
            default => null,
        };
    }

    private function cleanAlphaToken(string $w): string
    {
        $w = Str::lower($w);
        $w = Str::ascii($w);
        $w = str_replace(['đ','Đ'], ['d','d'], $w);
        $w = str_replace([',','.'], '', $w);
        return (string)(preg_replace('/[^a-z0-9_]/', '', $w) ?? '');
    }

    /* ========================= Schedule helpers ========================= */

    private function normalizedStationsFor(CarbonInterface $date, string $region, ?int $count): array
    {
        $row = LotterySchedule::forDateRegion($date, $region);
        if (!$row) return [];
        $list = array_merge([$row->main_station], is_array($row->sub_stations) ? $row->sub_stations : []);
        if ($count !== null && $count > 0) $list = array_slice($list, 0, $count);
        return array_values(array_map([$this, 'normalizeStationDisplayToKey'], $list));
    }

    private function normalizeStationDisplayToKey(string $name): string
    {
        $k = Str::ascii($name);
        $k = Str::lower($k);
        $k = str_replace(['đ','Đ'], ['d','d'], $k);
        return (string)(preg_replace('/\s+/', ' ', trim($k)) ?? '');
    }

    /* ========================= Fallback aliases ========================= */

    private function defaultTypeAliases(): array
    {
        return [
            'lo' => 'bao_lo', 'bao' => 'bao_lo', 'baolo' => 'bao_lo', 'blo' => 'bao_lo',
            'de' => 'de',
            'd'  => 'dau', 'dau' => 'dau',
            'b', 'đuôi','dui','duoi'  => 'duoi',
            'dd' => 'dau_duoi',
            'dx' => 'da_xien', 'dax' => 'da_xien', 'daxeo' => 'da_xien', 'dacheo' => 'da_xien',
            'xc' => 'xiu_chu', 'xiu' => 'xiu_chu', 'xiu_chu' => 'xiu_chu',
            'keo' => 'keo_hang_don_vi',
        ];
    }

    private function defaultStationAliases(): array
    {
        return [
            // Miền Nam
            'tp' => 'tp.hcm', 'hcm' => 'tp.hcm', 'tphcm' => 'tp.hcm', 'tpho' => 'tp.hcm',
            'la' => 'long an', 'lan' => 'long an', 'longan' => 'long an',
            'bp' => 'binh phuoc', 'bphuoc' => 'binh phuoc',
            'tn' => 'tay ninh', 'tninh' => 'tay ninh',
            'ag' => 'an giang', 'angiang' => 'an giang',
            'vt' => 'vung tau', 'vungtau' => 'vung tau',
            'bl' => 'bac lieu', 'baclieu' => 'bac lieu',
            'bt' => 'ben tre', 'bentre' => 'ben tre',
            'dn' => 'dong nai', 'dongnai' => 'dong nai',
            'ct' => 'can tho', 'cantho' => 'can tho',
            'st' => 'soc trang', 'soctrang' => 'soc trang',
            'hg' => 'hau giang', 'haugiang' => 'hau giang',
            'dt' => 'dong thap', 'dongthap' => 'dong thap',
            'cm' => 'ca mau', 'camau' => 'ca mau',
            'tg' => 'tien giang', 'tiengiang' => 'tien giang',
            'kg' => 'kien giang', 'kiengiang' => 'kien giang',
            'bth' => 'binh thuan', 'binhthuan' => 'binh thuan',
            'vl' => 'vinh long', 'vinhlong' => 'vinh long',
            'tv' => 'tra vinh', 'travinh' => 'tra vinh',
            // Miền Bắc
            'mb' => 'ha noi', 'hn' => 'ha noi', 'hanoi' => 'ha noi',
            'hp' => 'hai phong', 'haiphong' => 'hai phong',
            'nd' => 'nam dinh', 'namdinh' => 'nam dinh',
            'tb' => 'thai binh', 'thaibinh' => 'thai binh',
            'bn' => 'bac ninh', 'bacninh' => 'bac ninh',
            'qn' => 'quang ninh', 'quangninh' => 'quang ninh',
            // Miền Trung
            'kh' => 'khanh hoa', 'khanhhoa' => 'khanh hoa',
            'dnang' => 'da nang', 'danang' => 'da nang', // 'dn' dùng cho Đồng Nai
            'py' => 'phu yen', 'phuyen' => 'phu yen',
            'bd' => 'binh dinh', 'binhdinh' => 'binh dinh',
            'qb' => 'quang binh', 'quangbinh' => 'quang binh',
            'qt' => 'quang tri', 'quangtri' => 'quang tri',
            'gl' => 'gia lai', 'gialai' => 'gia lai',
            'nt' => 'ninh thuan', 'ninhthuan' => 'ninh thuan',
            'qng' => 'quang ngai', 'quangngai' => 'quang ngai',
            'kt' => 'kon tum', 'kontum' => 'kon tum',
            'dl' => 'da lat', 'dalat' => 'da lat',
        ];
    }
}
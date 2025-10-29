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

    public function __construct(BetPricingService $pricing)
    {
        $this->pricing = $pricing;
        
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
        // -------------------------------
        // Helpers & Context
        // -------------------------------
        $region = Region::normalizeKey($context['region'] ?? session('global_region', 'nam')); // bac|trung|nam
    
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
            // alias đài & từ khoá hay gặp
            $s = str_replace(['t,pho','t,phố','tphố','tpho','tp.hcm','tphcm','tp ho chi minh'], 'tphcm', $s);
            $s = str_replace(['t,ninh','t ninh','tninh','tn'], 'tn', $s);
            // dấu phẩy -> space
            $s = preg_replace('/[,]+/u',' ', $s) ?? $s;
            return trim($s);
        };
    
        $splitTokens = function(string $s): array {
            // Giữ dấu chấm làm token, tách combo dính (121xc25n, 084dd10n, 32lo5n, 12d10n)
            $s = preg_replace('/[^\w\.]/u', ' ', $s);
            $s = preg_replace('/\s+/', ' ', $s);
            $s = preg_replace('/(\d{2,4})(xc)(\d+)(n|k)/', '$1 $2 $3$4', $s);
            $s = preg_replace('/(\d{2,4})(lo)(\d+)(n|k)/', '$1 $2 $3$4', $s);
            $s = preg_replace('/(\d{2,4})(dd)(\d+)(n|k)/', '$1 $2 $3$4', $s);
            $s = preg_replace('/(\d{2,4})(d)(\d+)(n|k)/',  '$1 $2 $3$4', $s);
            $s = str_replace('.', ' . ', $s);
            $s = preg_replace('/\s+/', ' ', $s);
    
            return array_values(array_filter(explode(' ', trim($s)), fn($t)=>$t!==''));
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
            $ctx['pair_d_dau']    = [];    // gom các token d... (dau/duoi)
            $ctx['xc_d_list']     = [];    // xc d... d...
            $ctx['xc_dd_amount']  = null;  // xc dd...
        };
    
        $emitBet = function(array &$outBets, array &$ctx, array $bet) use ($joinStations) {
            $bet['station'] = $bet['station'] ?? $joinStations($ctx['stations']);
            $bet['meta']    = $bet['meta']    ?? [];
            $outBets[] = $bet;
        };
    
        // Bao lô: MỖI SỐ → 1 vé, kèm meta['digits']
        $emitBaoLoPerNumber = function(array &$outBets, array &$ctx) use ($emitBet) {
            $nums = array_values(array_unique($ctx['numbers_group'] ?? []));
            if (!count($nums) || !$ctx['amount']) return;
            foreach ($nums as $n) {
                $emitBet($outBets, $ctx, [
                    'numbers' => [$n],
                    'type'    => 'bao_lo',
                    'amount'  => (int)$ctx['amount'],
                    'meta'    => ['digits' => strlen($n)],
                ]);
            }
        };
    
        // Flush nhóm hiện tại → sinh vé & reset group
        $flushGroup = function(array &$outBets, array &$ctx, array &$events, ?string $reason=null) use ($emitBet, $emitBaoLoPerNumber, $addEvent) {
            if ($reason) $addEvent($events, $reason);
    
            $numbers = array_values(array_unique($ctx['numbers_group'] ?? []));
            $type    = $ctx['current_type'] ?? null;
            $amount  = (int)($ctx['amount'] ?? 0);
    
            // Không có loại → bỏ nhóm
            if (!$type) { $ctx['numbers_group']=[]; $ctx['amount']=null; $ctx['meta']=[]; return; }
    
            // Bao lô: tách từng số
            if ($type === 'bao_lo') {
                $emitBaoLoPerNumber($outBets, $ctx);
                $ctx['last_numbers'] = $numbers ?: ($ctx['last_numbers'] ?? []);
                $ctx['numbers_group']=[]; $ctx['amount']=null; $ctx['meta']=[]; $ctx['current_type']=null; $ctx['pair_d_dau']=[];
                return;
            }
    
            // XỈu chủ: dd | d-seq | mặc định
            if ($type === 'xiu_chu') {
                if (count($numbers)) {
                    if (!empty($ctx['xc_dd_amount'])) {
                        foreach ($numbers as $n) {
                            // đầu
                            $emitBet($outBets, $ctx, [
                                'numbers'=>[$n],'type'=>'xiu_chu_dau','amount'=>(int)$ctx['xc_dd_amount'],'meta'=>[]
                            ]);
                            // đuôi
                            $emitBet($outBets, $ctx, [
                                'numbers'=>[$n],'type'=>'xiu_chu_duoi','amount'=>(int)$ctx['xc_dd_amount'],'meta'=>[]
                            ]);
                        }
                        $addEvent($events,'emit_xc_head_tail',['mode'=>'dd','amount'=>$ctx['xc_dd_amount'],'numbers'=>$numbers]);
                    } elseif (!empty($ctx['xc_d_list'])) {
                        $dauAmt  = $ctx['xc_d_list'][0] ?? null;
                        $duoiAmt = $ctx['xc_d_list'][1] ?? null;
                        foreach ($numbers as $n) {
                            if ($dauAmt !== null) {
                                $emitBet($outBets, $ctx, ['numbers'=>[$n],'type'=>'xiu_chu_dau','amount'=>(int)$dauAmt,'meta'=>[]]);
                            }
                            if ($duoiAmt !== null) {
                                $emitBet($outBets, $ctx, ['numbers'=>[$n],'type'=>'xiu_chu_duoi','amount'=>(int)$duoiAmt,'meta'=>[]]);
                            }
                        }
                        $addEvent($events,'emit_xc_head_tail',['mode'=>'d_sequence','numbers'=>$numbers,'dau'=>$dauAmt,'duoi'=>$duoiAmt]);
                    } else {
                        $amt = (int)$ctx['amount'];
                        foreach ($numbers as $n) {
                            $emitBet($outBets, $ctx, ['numbers'=>[$n],'type'=>'xiu_chu','amount'=>$amt,'meta'=>[]]);
                        }
                        $addEvent($events,'emit_xc_split_per_number',['amount'=>$amt,'numbers'=>$numbers]);
                    }
                }
                // reset + ghi nhớ last_numbers
                $ctx['last_numbers'] = $numbers ?: ($ctx['last_numbers'] ?? []);
                $ctx['numbers_group']=[]; $ctx['amount']=null; $ctx['meta']=[]; $ctx['xc_d_list']=[]; $ctx['xc_dd_amount']=null; $ctx['current_type']=null;
                return;
            }
    
            // D-PAIR (đầu/đuôi)
            if ($type === 'dau') {
                if (count($numbers)) {
                    $d1 = $ctx['pair_d_dau'][0] ?? ($amount ?: null);
                    $d2 = $ctx['pair_d_dau'][1] ?? null;
                    foreach ($numbers as $n) {
                        if ($d1 !== null) $emitBet($outBets,$ctx,['numbers'=>[$n],'type'=>'dau','amount'=>(int)$d1,'meta'=>[]]);
                        if ($d2 !== null) $emitBet($outBets,$ctx,['numbers'=>[$n],'type'=>'duoi','amount'=>(int)$d2,'meta'=>[]]);
                    }
                    $addEvent($events,'emit_pair_d',['numbers'=>$numbers,'dau'=>$d1,'duoi'=>$d2]);
                }
                $ctx['last_numbers'] = $numbers ?: ($ctx['last_numbers'] ?? []);
                $ctx['numbers_group']=[]; $ctx['amount']=null; $ctx['meta']=[]; $ctx['current_type']=null; $ctx['pair_d_dau']=[];
                return;
            }
    
            // Đầu_Đuôi (dd): cùng amount cho cả 2
            if ($type === 'dau_duoi') {
                if (count($numbers) && $amount>0) {
                    foreach ($numbers as $n) {
                        $emitBet($outBets,$ctx,['numbers'=>[$n],'type'=>'dau','amount'=>$amount,'meta'=>[]]);
                        $emitBet($outBets,$ctx,['numbers'=>[$n],'type'=>'duoi','amount'=>$amount,'meta'=>[]]);
                    }
                    $addEvent($events,'emit_dd',['numbers'=>$numbers,'amount'=>$amount]);
                }
                $ctx['last_numbers'] = $numbers ?: ($ctx['last_numbers'] ?? []);
                $ctx['numbers_group']=[]; $ctx['amount']=null; $ctx['meta']=[]; $ctx['current_type']=null;
                return;
            }
    
            // Xiên MB
            if ($type === 'xien') {
                $x = (int)($ctx['meta']['xien_size'] ?? 0);
                if ($ctx['region'] !== 'bac') {
                    $addEvent($events, 'skip_xien_wrong_region', [
                        'token'   => 'xi'.$x,
                        'region'  => $ctx['region'],
                        'message' => "Loại cược xiên (xi{$x}) chỉ áp dụng cho Miền Bắc. Khu vực hiện tại: {$ctx['region']}."
                    ]);
                } elseif ($x>=2 && $x<=4 && count($numbers)>= $x) {
                    $emitBet($outBets,$ctx,[
                        'numbers'=>$numbers, 'type'=>'xien', 'amount'=>$amount,
                        'meta'=>['xien_size'=>$x],
                    ]);
                    $addEvent($events,'emit_xien',['xien_size'=>$x,'numbers'=>$numbers,'amount'=>$amount]);
                }
                $ctx['last_numbers'] = $numbers ?: ($ctx['last_numbers'] ?? []);
                $ctx['numbers_group']=[]; $ctx['amount']=null; $ctx['meta']=[]; $ctx['current_type']=null;
                return;
            }
    
            // Mặc định
            if ($type && count($numbers)) {
                $emitBet($outBets, $ctx, [
                    'numbers' => $numbers,
                    'type'    => $type,
                    'amount'  => $amount,
                    'meta'    => $ctx['meta'] ?? [],
                ]);
            }
            $ctx['last_numbers'] = $numbers ?: ($ctx['last_numbers'] ?? []);
            $ctx['numbers_group']=[]; $ctx['amount']=null; $ctx['meta']=[]; $ctx['current_type']=null; $ctx['pair_d_dau']=[];
        };
    
        // NEW: Kiểm tra nhóm đang treo để quyết định flush khi đổi đài
        $isGroupPending = function(array $ctx): bool {
            $type = $ctx['current_type'] ?? null;
            if (!$type) return false;
        
            // Phần lớn loại cược cần có numbers_group
            $hasNumbers = !empty($ctx['numbers_group']);
            if (!$hasNumbers && !in_array($type, ['xiu_chu'], true)) {
                return false;
            }
        
            return match ($type) {
                'bao_lo'    => (int)($ctx['amount'] ?? 0) > 0,
                'dau'       => !empty($ctx['pair_d_dau']) || (int)($ctx['amount'] ?? 0) > 0,
                'duoi'      => (int)($ctx['amount'] ?? 0) > 0,
                'dau_duoi'  => (int)($ctx['amount'] ?? 0) > 0,
        
                // xỉu chủ: có số và (có amount hoặc đã khai d/đd)
                'xiu_chu'   => !empty($ctx['numbers_group']) && (
                                  (int)($ctx['amount'] ?? 0) > 0
                                  || !empty($ctx['xc_d_list'])
                                  || !empty($ctx['xc_dd_amount'])
                               ),
        
                // xiên (MB): phải có amount và đủ số theo size
                'xien'      => (int)($ctx['amount'] ?? 0) > 0
                               && count($ctx['numbers_group']) >= (int)($ctx['meta']['xien_size'] ?? 0),
        
                default     => false,
            };
        };
    
        // -------------------------------
        // Chuẩn hoá & tokens
        // -------------------------------
        $normalized = $stripAccents($message);
        $tokens     = $splitTokens($normalized);
    
        // Đài mặc định theo miền
        $defaultStations = match ($region) {
            'bac'   => ['mien bac'],
            'trung' => ['dak lak','khanh hoa','phu yen','quang nam','quang ngai','binh dinh','thua thien hue'],
            default => ['tp.hcm'],
        };
    
        $events = [];
        $addEvent($events, 'stations_default', ['list'=>$defaultStations]);
    
        // -------------------------------
        // State
        // -------------------------------
        $ctx = [
            'region'           => $region,
            'stations'         => [],
            'numbers_group'    => [],
            'last_numbers'     => [],
            'current_type'     => null,
            'amount'           => null,
            'meta'             => [],
            'pair_d_dau'       => [],
            'xc_d_list'        => [],
            'xc_dd_amount'     => null,
            'just_saw_station' => false, // NEW
        ];
    
        $outBets = [];
        $errors  = [];
    
        // Dicts
        $stationAliases = [
            'tphcm'=>'tp.hcm','sg'=>'tp.hcm','hcm'=>'tp.hcm',
            'tn'=>'tay ninh','ag'=>'an giang','tg'=>'tien giang','bt'=>'ben tre',
            'vl'=>'vinh long','tv'=>'tra vinh','kg'=>'kien giang','dl'=>'da lat',
            'hn'=>'mien bac','mb'=>'mien bac',
        ];
        $typeAliases = [
            'lo'=>'bao_lo','dau'=>'dau','duoi'=>'duoi','dd'=>'dau_duoi','xc'=>'xiu_chu',
            'keo'=>'keo_hang_don_vi','dt'=>'da_thang','dx'=>'da_xien',
            // xiên MB: xi2/xi3/xi4 sẽ bắt bằng regex ở dưới
        ];
    
        // -------------------------------
        // Scan tokens
        // -------------------------------
        foreach ($tokens as $tok) {
    
            // Số (2-4 chữ số)
            if (preg_match('/^\d{2,4}$/', $tok)) {
                $hasPending =
                    (!empty($ctx['numbers_group'])) && (
                        ($ctx['current_type'] === 'dau'       && ($ctx['amount'] || count($ctx['pair_d_dau'])>0)) ||
                        ($ctx['current_type'] === 'dau_duoi'  && $ctx['amount']) ||
                        ($ctx['current_type'] === 'bao_lo'    && $ctx['amount']) ||
                        ($ctx['current_type'] === 'xiu_chu'   && ($ctx['amount'] || !empty($ctx['xc_d_list']) || !empty($ctx['xc_dd_amount']))) ||
                        ($ctx['current_type'] === 'xien'      && $ctx['amount'])
                    );
                if ($hasPending) {
                    $addEvent($events,'new_number_flush',['prev_numbers'=>$ctx['numbers_group']]);
                    $flushGroup($outBets,$ctx,$events,null);
                }
    
                $ctx['numbers_group'][] = $tok;
                $ctx['just_saw_station'] = false;
                $addEvent($events,'number',['value'=>$tok]);
                continue;
            }
    
            // Dấu chấm: flush mạnh
            if ($tok === '.') {
                $addEvent($events, 'dot_flush_or_hold');
                $flushGroup($outBets, $ctx, $events, null);
                $ctx['just_saw_station'] = false;
                continue;
            }
    
            // Amount rời: 10n|10k
            if (preg_match('/^(\d+)(n|k)$/', $tok, $m)) {
                $ctx['amount'] = (int)$m[1] * 1000;
                // Nếu không có số hiện tại → kế thừa last_numbers
                if (empty($ctx['numbers_group']) && !empty($ctx['last_numbers'])) {
                    $ctx['numbers_group'] = $ctx['last_numbers'];
                    $addEvent($events,'inherit_numbers_for_amount',['numbers'=>$ctx['numbers_group']]);
                }
                $ctx['just_saw_station'] = false;
                $addEvent($events,'amount_loose',['token'=>$tok,'type'=>$ctx['current_type'] ?? null,'amount'=>$ctx['amount']]);
                continue;
            }
    
            // Cặp dính: d100n / dd20n / lo5n
            if (preg_match('/^(d|dd|lo)(\d+)(n|k)$/', $tok, $m)) {
                $code = $m[1];
                $amt  = (int)$m[2] * 1000;
            
                // Đặc thù khi đang ở XỈU CHỦ: gom d/dđ/lo thành cấu hình cho xc
                if (($ctx['current_type'] ?? null) === 'xiu_chu') {
                    if ($code === 'dd') {
                        $ctx['xc_dd_amount'] = $amt;
                        $addEvent($events,'xc_pair_dd',['token'=>$tok,'amount'=>$amt]);
                    } elseif ($code === 'd') {
                        $ctx['xc_d_list'][] = $amt;
                        $addEvent($events,'xc_pair_d',['token'=>$tok,'amount'=>$amt,'index'=>count($ctx['xc_d_list'])]);
                    } else { // hiếm: 'xc lo10n' -> coi như amount chung của xc
                        $ctx['amount'] = $amt;
                        $addEvent($events,'xc_amount_through_lo',['token'=>$tok,'amount'=>$amt]);
                    }
                    $ctx['just_saw_station'] = false;
                    continue;
                }
            
                // Target type khi KHÔNG ở xỉu chủ
                $targetType = match ($code) {
                    'lo' => 'bao_lo',
                    'dd' => 'dau_duoi',
                    default => 'dau',
                };
            
                // Nếu đang có nhóm pending và targetType khác kiểu cũ -> flush trước
                if (($ctx['current_type'] ?? null) !== null
                    && $targetType !== $ctx['current_type']
                    && $isGroupPending($ctx)) {
                    $flushGroup($outBets, $ctx, $events, 'type_switch_flush');
                }
            
                // Nếu chưa có số -> kế thừa last_numbers (nếu có)
                if (empty($ctx['numbers_group']) && !empty($ctx['last_numbers'])) {
                    $ctx['numbers_group'] = $ctx['last_numbers'];
                    $addEvent($events,'inherit_numbers_for_amount',['numbers'=>$ctx['numbers_group']]);
                }
            
                // Set kiểu + amount/pair
                if ($targetType === 'bao_lo') {
                    $ctx['current_type'] = 'bao_lo';
                    $ctx['amount']       = $amt;
                    $addEvent($events,'pair_combo',['token'=>$tok,'type'=>'bao_lo','amount'=>$amt]);
                } elseif ($targetType === 'dau_duoi') {
                    $ctx['current_type'] = 'dau_duoi';
                    $ctx['amount']       = $amt;
                    $addEvent($events,'pair_combo',['token'=>$tok,'type'=>'dau_duoi','amount'=>$amt]);
                } else { // 'dau'
                    $ctx['current_type'] = 'dau';
                    $ctx['pair_d_dau'][] = $amt;
                    $addEvent($events,'pair_combo',['token'=>$tok,'type'=>'dau','amount'=>$amt]);
                }
            
                $ctx['just_saw_station'] = false;
                continue;
            }
    
            // Xiên MB: xi2/xi3/xi4
            if (preg_match('/^(xi(?:en)?([234]))$/', $tok, $m)) {
                $size = (int)$m[2];
                $ctx['current_type'] = 'xien';
                $ctx['meta']['xien_size'] = $size;
                // nếu chưa có số → kế thừa last_numbers (nếu có)
                if (empty($ctx['numbers_group']) && !empty($ctx['last_numbers'])) {
                    $ctx['numbers_group'] = $ctx['last_numbers'];
                    $addEvent($events,'inherit_numbers_for_xien',['numbers'=>$ctx['numbers_group']]);
                }
                if ($region !== 'bac') {
                    $addEvent($events, 'skip_xien_wrong_region', [
                        'token'=>"xi{$size}", 'region'=>$region,
                        'message'=>"Loại cược xiên (xi{$size}) chỉ áp dụng cho Miền Bắc. Khu vực hiện tại: {$region}."
                    ]);
                } else {
                    $addEvent($events,'type_loose',['token'=>$tok,'type'=>'xien']);
                }
                $ctx['just_saw_station'] = false;
                continue;
            }
    
            // Nhận kiểu cược rời:
            if (isset($typeAliases[$tok])) {
                $newType = $typeAliases[$tok];

                // Nếu đang có nhóm pending và kiểu mới KHÁC kiểu cũ -> flush
                if (($ctx['current_type'] ?? null) !== null
                    && $newType !== $ctx['current_type']
                    && $isGroupPending($ctx)) {
                    $flushGroup($outBets, $ctx, $events, 'type_switch_flush');
                }

                // Nếu chưa có số trong nhóm mà vừa đổi kiểu, kế thừa last_numbers (nếu có)
                if (empty($ctx['numbers_group']) && !empty($ctx['last_numbers'])) {
                    $ctx['numbers_group'] = $ctx['last_numbers'];
                    $addEvent($events,'inherit_numbers_for_type',['type'=>$newType,'numbers'=>$ctx['numbers_group']]);
                }

                $ctx['current_type'] = $newType;
                $ctx['just_saw_station'] = false;
                $addEvent($events, 'type_loose', ['token'=>$tok,'type'=>$ctx['current_type']]);
                continue;
            }
    
            // Nhận station — FLUSH nếu đang có nhóm pending
            if (isset($stationAliases[$tok])) {
                $name = $stationAliases[$tok];
    
                if ($isGroupPending($ctx)) {
                    // flush nhóm cũ TRƯỚC khi đổi đài
                    $flushGroup($outBets, $ctx, $events, 'station_switch_flush');
                    // bắt đầu context đài mới: replace
                    $ctx['stations'] = [$name];
                } else {
                    // không pending → xét cộng dồn hay replace
                    if ($ctx['just_saw_station']) {
                        // đài LIỀN NHAU → cộng dồn (phục vụ dx đa đài)
                        if (!in_array($name, $ctx['stations'], true)) {
                            $ctx['stations'][] = $name;
                        }
                    } else {
                        // đài sau token khác → thay mới
                        $ctx['stations'] = [$name];
                    }
                }
    
                $ctx['just_saw_station'] = true;
                $addEvent($events, 'stations', ['set'=>array_values($ctx['stations'])]);
                continue;
            }
    
            // Bỏ qua rác
            $ctx['just_saw_station'] = false;
            $addEvent($events, 'skip', ['token'=>$tok]);
        }
    
        // -------------------------------
        // Flush cuối
        // -------------------------------
        $flushGroup($outBets, $ctx, $events, 'final_flush');
    
        // Gán station mặc định nếu thiếu
        foreach ($outBets as &$b) {
            if (empty($b['station'])) {
                $b['station'] = $joinStations(count($ctx['stations']) ? $ctx['stations'] : $defaultStations);
            }
            $b['meta'] = $b['meta'] ?? [];
        }
        unset($b);
    
        // -------------------------------
        // Pricing preview (nếu đã inject service)
        // -------------------------------
        $customerId = isset($context['customer_id']) && is_numeric($context['customer_id'])
            ? (int)$context['customer_id'] : null;
    
        $this->pricing->begin($customerId, $region);
        foreach ($outBets as &$b) {
            if (!isset($b['meta']['digits']) && in_array($b['type'], ['bao_lo','bao3_lo','bao4_lo'], true)) {
                $first = $b['numbers'][0] ?? null;
                if ($first) $b['meta']['digits'] = strlen((string)$first);
            }
            if ($b['type'] === 'da_xien' && empty($b['meta']['dai_count'])) {
                $stations = array_map('trim', explode('+', (string)$b['station']));
                $b['meta']['dai_count'] = count(array_filter($stations));
            }
            $b['pricing'] = $this->pricing->previewForBet($b);
        }
        unset($b);
    
        $pv = \App\Services\BetPricingService::buildBreakdown($outBets);
    
        return [
            'is_valid'        => !empty($outBets),
            'multiple_bets'   => $outBets,
            'errors'          => $errors,
            'normalized'      => $normalized,
            'parsed_message'  => $normalized,
            'tokens'          => $tokens,
            'preview'         => [
                'by_type' => $pv['breakdown'],
                'total'   => $pv['total'],
            ],
            'debug'           => [
                'stations_default' => array_values($defaultStations),
                'events'           => $events,
            ],
        ];
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
            'lo', 'bao', 'baolo' => 'bao_lo',
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
            'lo','bao','baolo' => 'bao_lo',
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
            'lo' => 'bao_lo', 'bao' => 'bao_lo', 'baolo' => 'bao_lo',
            'de' => 'de',
            'd'  => 'dau', 'dau' => 'dau',
            'b'  => 'duoi', 'duoi' => 'duoi',
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
            'btuan' => 'binh thuan', 'binhthuan' => 'binh thuan',
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

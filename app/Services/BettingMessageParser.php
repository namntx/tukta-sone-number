<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\BettingType;
use App\Models\LotterySchedule;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;
use App\Support\Region;


class BettingMessageParser
{
    /** @var array<string,string> alias -> canonical betting type code */
    private array $typeAliasMap = [];

    /** @var array<string,string> station alias -> canonical station key */
    private array $stationAliasMap = [];

    public function __construct()
    {
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
        if (!is_array($context)) {
            // nếu controller truyền customer_id dạng int/string
            $context = ['customer_id' => is_numeric($context) ? (int)$context : null];
        }
    
        // Chuẩn hoá region về bac|trung|nam
        $region = Region::normalizeKey($context['region'] ?? session('global_region', 'nam'));
        $errors = [];

        // -------------------------------
        // Helpers (cục bộ cho hàm)
        // -------------------------------
        $addEvent = function(array &$events, string $kind, array $extra = []) {
            $ev = array_merge(['kind' => $kind], $extra);
            $events[] = $ev;
        };
        

        $stripAccents = function(string $s): string {
            // Quan trọng: ép về string trước, tránh Stringable cho preg_replace
            // Có thể dùng 1 trong 2 cách:
            // $s = (string) Str::of($s)->lower();
            // hoặc:
            $s = mb_strtolower($s, 'UTF-8');
        
            // Bỏ dấu tiếng Việt
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
                // thêm ?? để phòng khi preg_replace trả null
                $s = preg_replace("/$re/u", $to, $s) ?? $s;
            }
        
            // Chuẩn alias hay gặp (sau khi bỏ dấu)
            // Lưu ý: sau khi bỏ dấu, 'tphố' -> 'tpho'
            $s = str_replace(['t,pho','t,phố','tphố','tpho','tp.hcm','tphcm','tp ho chi minh'], 'tphcm', $s);
            $s = str_replace(['t,ninh','t ninh','tninh','tn'], 'tn', $s);
        
            // Đổi dấu phẩy thành khoảng trắng
            $s = preg_replace('/[,]+/u',' ', $s) ?? $s;
        
            return trim($s);
        };

        $splitTokens = function(string $s): array {
            // Tách giữ lại dấu chấm (.), các cụm combo dính (vd: 121xc25n)
            // và số có leading zero (2/3/4 chữ số).
            $s = preg_replace('/[^\w\.]/u', ' ', $s);
            $s = preg_replace('/\s+/',' ', $s);

            // Bóc tách các combo phổ biến dính liền: (\d+)(xc|lo|d|dd)(\d+)(n|k)?
            $s = preg_replace('/(\d{2,4})(xc)(\d+)(n|k)/', '$1 $2 $3$4', $s);
            $s = preg_replace('/(\d{2,4})(lo)(\d+)(n|k)/', '$1 $2 $3$4', $s);
            $s = preg_replace('/(\d{2,4})(dd)(\d+)(n|k)/', '$1 $2 $3$4', $s);
            $s = preg_replace('/(\d{2,4})(d)(\d+)(n|k)/',  '$1 $2 $3$4', $s);

            // Chèn khoảng trắng quanh dấu chấm để xem như token
            $s = str_replace('.', ' . ', $s);
            $s = preg_replace('/\s+/', ' ', $s);

            $parts = array_values(array_filter(explode(' ', trim($s)), fn($t)=>$t!==''));
            return $parts;
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
        
            // NEW:
            $ctx['xc_d_list']     = [];
            $ctx['xc_dd_amount']  = null;
        };

        $emitBet = function(array &$outBets, array &$ctx, array $bet) use ($joinStations) {
            // Áp đài hiện tại
            $bet['station'] = $bet['station'] ?? $joinStations($ctx['stations']);
            $bet['meta']    = $bet['meta']    ?? [];
            $outBets[] = $bet;
        };

        // MỚI: tách lô theo độ dài số
        $emitBaoLoByDigits = function(array &$outBets, array &$ctx) use ($emitBet) {
            $numbers = array_values(array_unique($ctx['numbers_group']));
            if (!count($numbers) || !$ctx['amount']) return;

            $groups = [2=>[],3=>[],4=>[]];
            foreach ($numbers as $n) {
                $len = strlen($n);
                if (isset($groups[$len])) $groups[$len][] = $n;
            }
            foreach ($groups as $digits => $nums) {
                if (!count($nums)) continue;
                $emitBet($outBets, $ctx, [
                    'numbers' => $nums,
                    'type'    => 'bao_lo',
                    'amount'  => (int)$ctx['amount'],
                    'meta'    => ['digits' => $digits],
                ]);
            }
        };

        $flushGroup = function(array &$outBets, array &$ctx, array &$events, ?string $reason=null) use ($emitBet, $emitBaoLoByDigits, $addEvent, &$errors) {
            if ($reason) $addEvent($events, $reason);

            $numbers = array_values(array_unique($ctx['numbers_group'] ?? []));
            $type    = $ctx['current_type'] ?? null;
            $amount  = (int)($ctx['amount'] ?? 0);

            if (!$type) { $ctx['numbers_group']=[]; $ctx['amount']=null; $ctx['meta']=[]; return; }
            if (!count($numbers) && !in_array($type, ['xiu_chu','xiu_chu_dau','xiu_chu_duoi'], true)) {
                // những loại cần số mà không có số → bỏ
                $ctx['numbers_group']=[]; $ctx['amount']=null; $ctx['meta']=[]; return;
            }

            // TÁCH LÔ THEO ĐỘ DÀI
            if ($type === 'bao_lo') {
                $emitBaoLoByDigits($outBets, $ctx);
                $ctx['numbers_group']=[]; $ctx['amount']=null; $ctx['meta']=[]; $ctx['current_type']=null;
                return;
            }

            if ($type === 'xiu_chu') {
                $numbers = array_values(array_unique($ctx['numbers_group'] ?? []));
                $region  = $ctx['region'] ?? 'nam';
            
                if (count($numbers)) {
                    // Ưu tiên 1: 'xc dd10n' → đầu & đuôi cùng amount
                    if (!empty($ctx['xc_dd_amount'])) {
                        foreach ($numbers as $n) {
                            // Đầu
                            $emitBet($outBets, $ctx, [
                                'numbers' => [$n],
                                'type'    => ($region === 'bac') ? 'xiu_chu_dau' : 'xiu_chu_dau',
                                'amount'  => (int)$ctx['xc_dd_amount'],
                                'meta'    => [],
                            ]);
                            // Đuôi
                            $emitBet($outBets, $ctx, [
                                'numbers' => [$n],
                                'type'    => ($region === 'bac') ? 'xiu_chu_duoi' : 'xiu_chu_duoi',
                                'amount'  => (int)$ctx['xc_dd_amount'],
                                'meta'    => [],
                            ]);
                        }
                        $addEvent($events, 'emit_xc_head_tail', [
                            'mode'=>'dd',
                            'amount'=>$ctx['xc_dd_amount'],
                            'numbers'=>$numbers
                        ]);
                    }
                    // Ưu tiên 2: 'xc d10n d5n' → map d[0] = đầu, d[1] = đuôi
                    elseif (!empty($ctx['xc_d_list'])) {
                        $dauAmt  = $ctx['xc_d_list'][0] ?? null;
                        $duoiAmt = $ctx['xc_d_list'][1] ?? null;
                        foreach ($numbers as $n) {
                            if ($dauAmt !== null) {
                                $emitBet($outBets, $ctx, [
                                    'numbers' => [$n],
                                    'type'    => 'xiu_chu_dau',
                                    'amount'  => (int)$dauAmt,
                                    'meta'    => [],
                                ]);
                            }
                            if ($duoiAmt !== null) {
                                $emitBet($outBets, $ctx, [
                                    'numbers' => [$n],
                                    'type'    => 'xiu_chu_duoi',
                                    'amount'  => (int)$duoiAmt,
                                    'meta'    => [],
                                ]);
                            }
                        }
                        $addEvent($events, 'emit_xc_head_tail', [
                            'mode'=>'d_sequence',
                            'dau'=>$dauAmt,'duoi'=>$duoiAmt,'numbers'=>$numbers
                        ]);
                    }
                    // Mặc định: 'xc 10n' → mỗi số 1 vé xỉu chủ (không tách đầu/đuôi)
                    else {
                        $amt = (int)($ctx['amount'] ?? 0);
                        foreach ($numbers as $n) {
                            $emitBet($outBets, $ctx, [
                                'numbers' => [$n],
                                'type'    => 'xiu_chu',
                                'amount'  => $amt,
                                'meta'    => [],
                            ]);
                        }
                        $addEvent($events, 'emit_xc_split_per_number', [
                            'amount'=>$amt, 'numbers'=>$numbers
                        ]);
                    }
                }
            
                // reset
                $ctx['numbers_group'] = [];
                $ctx['amount']        = null;
                $ctx['meta']          = [];
                $ctx['xc_d_list']     = [];
                $ctx['xc_dd_amount']  = null;
                $ctx['current_type']  = null;
                return;
            }

            // XIÊN MB: tạo 1 vé với meta.xien_size
            if ($type === 'xien') {
                $x = (int)($ctx['meta']['xien_size'] ?? 0);
                $regionCtx = $ctx['region'] ?? 'nam';

                if ($regionCtx !== 'bac') {
                    $msg = "Loại cược xiên (xi{$x}) chỉ áp dụng cho Miền Bắc. Khu vực hiện tại: {$regionCtx}.";
                    $errors[] = $msg; // <— PHẢI có dòng này
                    $addEvent($events, 'block_emit_xien_wrong_region', [
                        'region'=>$regionCtx, 'message'=>$msg
                    ]);
                    $resetGroup($ctx);
                    return;
                }

                if ($x >= 2 && $x <= 4) {
                    if (count($numbers) >= $x) {
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
                }
                $ctx['numbers_group']=[]; $ctx['amount']=null; $ctx['meta']=[]; $ctx['current_type']=null;
                return;
            }

            // các loại khác giữ nguyên (dau/duoi/dd/xc... theo logic cũ)
            if ($type) {
                $emitBet($outBets, $ctx, [
                    'numbers' => $numbers,
                    'type'    => $type,
                    'amount'  => $amount,
                    'meta'    => $ctx['meta'] ?? [],
                ]);
            }

            $ctx['numbers_group']=[]; $ctx['amount']=null; $ctx['meta']=[]; $ctx['current_type']=null;
        };

        // -------------------------------
        // 1) Chuẩn hoá & context
        // -------------------------------
        $normalized = $stripAccents($message);
        $tokens = $splitTokens($normalized);

        // Region & stations default (đã có sẵn trong app bạn; ở đây fallback an toàn)
        $defaultStations = match ($region) {
            'bac'   => ['mien bac'],
            'trung' => ['dak lak','khanh hoa','phu yen','quang nam','quang ngai','binh dinh','thua thien hue'],
            default => ['tp.hcm'],
        };

        $events = [];
        $addEvent($events, 'stations_default', ['list'=>$defaultStations]);

        // -------------------------------
        // 2) State
        // -------------------------------
        $ctx = [
            'region'         => $region,
            'stations'       => [],
            'numbers_group'  => [],
            'current_type'   => null,
            'amount'         => null,
            'meta'           => [],
            'pair_d_dau'     => [],
        
            // NEW: gom amount khi gặp 'xc d.../dd...'
            'xc_d_list'      => [],   // ví dụ ['d10k','d5k'] => [10000, 5000]
            'xc_dd_amount'   => null, // ví dụ 'dd10k' => 10000
        ];

        $outBets = [];

        // -------------------------------
        // 3) Dicts
        // -------------------------------
        $stationAliases = [
            // miền nam
            'tphcm' => 'tp.hcm', 'sg'=>'tp.hcm', 'hcm'=>'tp.hcm',
            'tn'    => 'tay ninh', 'ag'=>'an giang', 'tg'=>'tien giang', 'bt'=>'ben tre',
            'vl'    => 'vinh long', 'tv'=>'tra vinh', 'kg'=>'kien giang', 'dl'=>'da lat',
            // miền trung/bắc
            'hn'    => 'mien bac', 'mb'=>'mien bac',
        ];

        $typeAliases = [
            'lo'   => 'bao_lo',
            'dau'  => 'dau',
            'duoi' => 'duoi',
            'dd'   => 'dau_duoi',
            'xc'   => 'xiu_chu',
            'keo'  => 'keo_hang_don_vi',
            // đá/cặp trước đây (dt/dx) vẫn giữ nếu bạn đã có
            'dt'   => 'da_thang',
            'dx'   => 'da_xien',
        ];

        // -------------------------------
        // 4) Scan tokens
        // -------------------------------
        foreach ($tokens as $tok) {
            // số thuần 2-4 chữ số (giữ leading zero)
            if (preg_match('/^\d{2,4}$/', $tok)) {
                $ctx['numbers_group'][] = $tok;
                $addEvent($events, 'number', ['value'=>$tok]);
                continue;
            }

            // Dấu chấm: flush/hard-sep
            if ($tok === '.') {
                $addEvent($events, 'dot_flush_or_hold');
                $flushGroup($outBets, $ctx, $events, null);
                continue;
            }

            // Nhận amount loose: 10n|10k
            if (preg_match('/^(\d+)(n|k)$/', $tok, $m)) {
                $ctx['amount'] = (int)$m[1] * 1000;
                $currType = $ctx['current_type'] ?? null;
                $addEvent($events, 'amount_loose', [
                    'token'=>$tok, 'type'=>$currType, 'amount'=>$ctx['amount']
                ]);
                continue;
            }

            // Nhận cặp dính: d100n / dd20n / lo5n
            if (preg_match('/^(d|dd|lo)(\d+)(n|k)$/', $tok, $m)) {
                $code = $m[1];
                $amt  = (int)$m[2] * 1000;
            
                // Nếu đang ở ngữ cảnh XỈU CHỦ → không đổi current_type,
                // mà gom amount vào list để lúc flush tách "đầu/đuôi" đúng ý.
                if (($ctx['current_type'] ?? null) === 'xiu_chu') {
                    if ($code === 'lo') {
                        // hiếm khi user gõ "xc lo10n" → coi như amount chung cho xc
                        $ctx['amount'] = $amt;
                        $addEvent($events, 'xc_amount_through_lo', ['token'=>$tok,'amount'=>$amt]);
                    } elseif ($code === 'dd') {
                        // 'xc dd10n' → đầu = đuôi = 10k
                        $ctx['xc_dd_amount'] = $amt;
                        $addEvent($events, 'xc_pair_dd', ['token'=>$tok,'amount'=>$amt]);
                    } else { // 'd'
                        // 'xc d10n d5n' → d đầu = 10k, d sau = 5k (đầu/đuôi)
                        $ctx['xc_d_list'][] = $amt;
                        $addEvent($events, 'xc_pair_d', ['token'=>$tok,'amount'=>$amt,'index'=>count($ctx['xc_d_list'])]);
                    }
                    continue;
                }
            
                // Ngữ cảnh bình thường (không phải xc)
                if ($code === 'lo') {
                    $ctx['current_type'] = 'bao_lo';
                    $ctx['amount']       = $amt;
                    $addEvent($events, 'pair_combo', ['token'=>$tok,'type'=>'bao_lo','amount'=>$amt]);
                } elseif ($code === 'dd') {
                    $ctx['current_type'] = 'dau_duoi';
                    $ctx['amount']       = $amt;
                    $addEvent($events, 'pair_combo', ['token'=>$tok,'type'=>'dau_duoi','amount'=>$amt]);
                } else { // 'd'
                    $ctx['current_type'] = 'dau';
                    $ctx['amount']       = $amt;
                    $addEvent($events, 'pair_combo', ['token'=>$tok,'type'=>'dau','amount'=>$amt]);
                }
                continue;
            }

            // Nhận combo số + xc/lo/dd/d: 121 xc 25n đã được tách ở tokenizer (ở trên)
            // -> đã xử lý ở 2 case trên (type_loose + amount_loose)

            // Nhận kiểu cược rời:
            if (isset($typeAliases[$tok])) {
                $ctx['current_type'] = $typeAliases[$tok];
                $addEvent($events, 'type_loose', ['token'=>$tok,'type'=>$ctx['current_type']]);
                continue;
            }

            // MỚI: Xiên 2/3/4 (MB only)
            if (preg_match('/^(xi(?:en)?([234]))$/', $tok, $m)) {
                $size = (int)$m[2];
            
                // Nếu KHÔNG phải MB → bỏ qua luôn, chỉ log event
                if ($region !== 'bac') {
                    $msg = "Loại cược xiên (xi{$size}) chỉ áp dụng cho Miền Bắc. Khu vực hiện tại: {$region}.";
                    $errors[] = $msg; // <— PHẢI có dòng này
                    $addEvent($events, 'skip_xien_wrong_region', [
                        'token'=>$tok, 'region'=>$region, 'message'=>$msg
                    ]);
                    continue;
                }
            
                // MB: set type & meta
                $ctx['current_type'] = 'xien';
                $ctx['meta']['xien_size'] = $size;
                $addEvent($events, 'type_loose', ['token'=>$tok, 'type'=>'xien', 'xien_size'=>$size]);
                continue;
            }

            // Nhận station
            if (isset($stationAliases[$tok])) {
                $name = $stationAliases[$tok];
                // Xoá stations cũ nếu bắt đầu cụm đài mới?
                // Ở đây, giữ quy tắc cũ: set/append
                if (!in_array($name, $ctx['stations'], true)) {
                    $ctx['stations'][] = $name;
                }
                $addEvent($events, 'stations', ['set'=>array_values($ctx['stations'])]);
                continue;
            }

            // Bỏ qua rác
            $addEvent($events, 'skip', ['token'=>$tok]);
        }

        // -------------------------------
        // 5) Flush cuối
        // -------------------------------
        $flushGroup($outBets, $ctx, $events, 'final_flush');

        // Nếu chưa có đài → áp mặc định theo miền (rule 2/4/5)
        if (empty($outBets)) {
            // không có vé => invalid, nhưng vẫn trả tokens/debug
            return [
                'is_valid'        => false,
                'multiple_bets'   => [],
                'errors'          => $errors,
                'normalized'      => $normalized,
                'parsed_message'  => $normalized,
                'tokens'          => $tokens,
                'debug'           => [
                    'stations_default' => $defaultStations,
                    'events'           => $events,
                ],
            ];
        }

        // Nếu một số bet chưa có station → gán station từ ctx hoặc default
        foreach ($outBets as &$b) {
            if (empty($b['station'])) {
                $b['station'] = $joinStations(count($ctx['stations']) ? $ctx['stations'] : $defaultStations);
            }
            $b['meta'] = $b['meta'] ?? [];
        }
        unset($b);

        return [
            'is_valid'        => !empty($outBets), // ✅ có bet mới là hợp lệ
            'multiple_bets'   => $outBets,
            'errors'          => $errors,
            'normalized'      => $normalized,
            'parsed_message'  => $normalized,
            'tokens'          => $tokens,
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

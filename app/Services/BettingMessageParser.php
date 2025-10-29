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
        // Init + helpers
        // -------------------------------
        $errors = [];
    
        $addEvent = function(array &$events, string $kind, array $extra = []) {
            $ev = array_merge(['kind' => $kind], $extra);
            $events[] = $ev;
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
    
            // Chuẩn alias quen dùng
            $s = str_replace(['t,pho','t,phố','tphố','tpho','tp.hcm','tphcm','tp ho chi minh'], 'hcm', $s);
            $s = str_replace(['t,ninh','t ninh','tninh'], 'tn', $s);
    
            // Đổi dấu phẩy thành space
            $s = preg_replace('/[,]+/u',' ', $s) ?? $s;
    
            return trim($s);
        };
    
        $splitTokens = function(string $s): array {
            // Giữ '.' là token, tách combo dính: 121xc25n, 084dd10k, 12lo3n, 54d30n
            $s = preg_replace('/[^\w\.]/u', ' ', $s);
            $s = preg_replace('/\s+/',' ', $s);
    
            // Bóc tách combos dạng số + (xc|lo|dd|d) + amount
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
            $ctx['pair_d_dau']    = [];
    
            // xỉu chủ
            $ctx['xc_d_list']     = [];
            $ctx['xc_dd_amount']  = null;
    
            // kéo
            $ctx['meta']['keo_start'] = null;
        };
    
        $emitBet = function(array &$outBets, array &$ctx, array $bet) use ($joinStations) {
            $bet['station'] = $bet['station'] ?? $joinStations($ctx['stations']);
            $bet['meta']    = $bet['meta']    ?? [];
            $outBets[] = $bet;
        };
    
        // Bao lô: phát 1 vé / 1 số (mỗi số giữ meta['digits'])
        $emitBaoLoOneByOne = function(array &$outBets, array &$ctx) use ($emitBet) {
            $numbers = array_values(array_unique($ctx['numbers_group']));
            if (!count($numbers) || !(int)($ctx['amount'] ?? 0)) return;
    
            foreach ($numbers as $n) {
                $digits = strlen($n);
                if ($digits < 2 || $digits > 4) continue;
                $emitBet($outBets, $ctx, [
                    'numbers' => [$n],
                    'type'    => 'bao_lo',
                    'amount'  => (int)$ctx['amount'],
                    'meta'    => ['digits' => $digits],
                ]);
            }
        };
    
        // Kéo: mở rộng dải số từ start -> end (2 hoặc 3 chữ số; giữ leading zero)
        $expandKeoNumbers = function(string $start, string $end): array {
            $len = strlen($start);
            if ($len !== strlen($end)) return [];
    
            if ($len === 2) {
                $a = str_split($start); $b = str_split($end);
                if ($a[0] === $b[0]) { // chung hàng chục -> chạy đơn vị
                    $fix = $a[0];
                    $from = (int)$a[1]; $to = (int)$b[1];
                    if ($from > $to) [$from, $to] = [$to, $from];
                    $out = [];
                    for ($i=$from; $i<=$to; $i++) $out[] = $fix.(string)$i;
                    return array_map(fn($s)=>str_pad($s, 2, '0', STR_PAD_LEFT), $out);
                }
                if ($a[1] === $b[1]) { // chung hàng đơn vị -> chạy hàng chục
                    $fix = $a[1];
                    $from = (int)$a[0]; $to = (int)$b[0];
                    if ($from > $to) [$from, $to] = [$to, $from];
                    $out = [];
                    for ($i=$from; $i<=$to; $i++) $out[] = (string)$i.$fix;
                    return array_map(fn($s)=>str_pad($s, 2, '0', STR_PAD_LEFT), $out);
                }
                return [];
            }
    
            if ($len === 3) {
                $a = str_split($start); $b = str_split($end);
                $same=0; $diffIdx=null;
                for ($i=0;$i<3;$i++){ if ($a[$i]===$b[$i]) $same++; }
                if ($same !== 2) return [];
                for ($i=0;$i<3;$i++){ if ($a[$i] !== $b[$i]) { $diffIdx=$i; break; } }
                if ($diffIdx===null) return [];
                $from = (int)$a[$diffIdx]; $to = (int)$b[$diffIdx];
                if ($from > $to) [$from, $to] = [$to, $from];
                $out = [];
                for ($x=$from;$x<=$to;$x++){
                    $tmp = $a; $tmp[$diffIdx]=(string)$x;
                    $out[] = implode('', $tmp);
                }
                return array_map(fn($s)=>str_pad($s, 3, '0', STR_PAD_LEFT), $out);
            }
            return [];
        };
    
        // Nhóm hiện tại có đủ điều kiện flush chưa?
        $isGroupPending = function(array $ctx): bool {
            $type = $ctx['current_type'] ?? null;
            if (!$type) return false;
    
            // 'kéo' chỉ để tạo dải số, bản thân không sinh vé -> không pending
            if ($type === 'keo_hang_don_vi') return false;
    
            $hasNumbers = !empty($ctx['numbers_group']);
            return match ($type) {
                'bao_lo'    => $hasNumbers && (int)($ctx['amount'] ?? 0) > 0,
                'dau'       => $hasNumbers && ( !empty($ctx['pair_d_dau']) || (int)($ctx['amount'] ?? 0) > 0 ),
                'duoi'      => $hasNumbers && (int)($ctx['amount'] ?? 0) > 0,
                'dau_duoi'  => $hasNumbers && (int)($ctx['amount'] ?? 0) > 0,
                'xiu_chu'   => $hasNumbers && ( (int)($ctx['amount'] ?? 0) > 0 || !empty($ctx['xc_d_list']) || !empty($ctx['xc_dd_amount']) ),
                'xien'      => (int)($ctx['amount'] ?? 0) > 0 && $hasNumbers && count($ctx['numbers_group']) >= (int)($ctx['meta']['xien_size'] ?? 0),
                default     => false,
            };
        };
    
        $flushGroup = function(array &$outBets, array &$ctx, array &$events, ?string $reason=null) use ($emitBet, $emitBaoLoOneByOne, $addEvent) {
            if ($reason) $addEvent($events, $reason);
    
            $numbers = array_values(array_unique($ctx['numbers_group'] ?? []));
            $type    = $ctx['current_type'] ?? null;
            $amount  = (int)($ctx['amount'] ?? 0);
            $region  = $ctx['region'] ?? 'nam';
    
            if (!$type) { $ctx['numbers_group']=[]; $ctx['amount']=null; $ctx['meta']=[]; return; }
            if (!count($numbers) && !in_array($type, ['xiu_chu'], true)) {
                // những loại cần số mà không có số → bỏ
                $ctx['numbers_group']=[]; $ctx['amount']=null; $ctx['meta']=[]; $ctx['current_type']=null;
                return;
            }
    
            // Bao lô: 1 vé / 1 số, kèm meta['digits']
            if ($type === 'bao_lo') {
                $emitBaoLoOneByOne($outBets, $ctx);
                $ctx['numbers_group']=[]; $ctx['amount']=null; $ctx['meta']=[]; $ctx['current_type']=null;
                return;
            }
    
            // Xỉu chủ: tách đầu/đuôi nếu có 'dd' hoặc d-sequence; mặc định: 1 vé/1 số xiu_chu
            if ($type === 'xiu_chu') {
                if (count($numbers)) {
                    if (!empty($ctx['xc_dd_amount'])) {
                        foreach ($numbers as $n) {
                            // đầu
                            $emitBet($outBets, $ctx, [
                                'numbers' => [$n],
                                'type'    => 'xiu_chu_dau',
                                'amount'  => (int)$ctx['xc_dd_amount'],
                                'meta'    => [],
                            ]);
                            // đuôi
                            $emitBet($outBets, $ctx, [
                                'numbers' => [$n],
                                'type'    => 'xiu_chu_duoi',
                                'amount'  => (int)$ctx['xc_dd_amount'],
                                'meta'    => [],
                            ]);
                        }
                        $addEvent($events, 'emit_xc_head_tail', ['mode'=>'dd','amount'=>$ctx['xc_dd_amount'],'numbers'=>$numbers]);
                    } elseif (!empty($ctx['xc_d_list'])) {
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
                        $addEvent($events, 'emit_xc_head_tail', ['mode'=>'d_sequence','dau'=>$dauAmt,'duoi'=>$duoiAmt,'numbers'=>$numbers]);
                    } else {
                        // mặc định: 1 vé xỉu chủ / 1 số
                        foreach ($numbers as $n) {
                            $emitBet($outBets, $ctx, [
                                'numbers' => [$n],
                                'type'    => 'xiu_chu',
                                'amount'  => $amount,
                                'meta'    => [],
                            ]);
                        }
                        $addEvent($events, 'emit_xc_split_per_number', ['amount'=>$amount,'numbers'=>$numbers]);
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
    
            // Xiên (MB) → 1 vé với meta.xien_size
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
    
            // Đầu / Đuôi / Đầu-Đuôi (đơn giản)
            if ($type === 'dau_duoi') {
                foreach ($numbers as $n) {
                    $emitBet($outBets, $ctx, [
                        'numbers' => [$n], 'type' => 'dau',  'amount' => $amount, 'meta' => []
                    ]);
                    $emitBet($outBets, $ctx, [
                        'numbers' => [$n], 'type' => 'duoi', 'amount' => $amount, 'meta' => []
                    ]);
                }
                $ctx['numbers_group']=[]; $ctx['amount']=null; $ctx['meta']=[]; $ctx['current_type']=null;
                return;
            }
    
            if ($type === 'dau') {
                // nếu có 2 giá trị d... trong group -> coi như [đầu, đuôi]
                if (!empty($ctx['pair_d_dau'])) {
                    $dauAmt  = $ctx['pair_d_dau'][0] ?? 0;
                    $duoiAmt = $ctx['pair_d_dau'][1] ?? null;
                    foreach ($numbers as $n) {
                        if ($dauAmt) {
                            $emitBet($outBets, $ctx, ['numbers'=>[$n],'type'=>'dau','amount'=>(int)$dauAmt,'meta'=>[]]);
                        }
                        if ($duoiAmt) {
                            $emitBet($outBets, $ctx, ['numbers'=>[$n],'type'=>'duoi','amount'=>(int)$duoiAmt,'meta'=>[]]);
                        }
                    }
                    $addEvent($events,'emit_pair_d',['numbers'=>$numbers,'dau'=>$dauAmt,'duoi'=>$duoiAmt]);
                } else {
                    foreach ($numbers as $n) {
                        $emitBet($outBets, $ctx, ['numbers'=>[$n],'type'=>'dau','amount'=>$amount,'meta'=>[]]);
                    }
                }
                $ctx['numbers_group']=[]; $ctx['amount']=null; $ctx['meta']=[]; $ctx['pair_d_dau']=[]; $ctx['current_type']=null;
                return;
            }
    
            if ($type === 'duoi') {
                foreach ($numbers as $n) {
                    $emitBet($outBets, $ctx, ['numbers'=>[$n],'type'=>'duoi','amount'=>$amount,'meta'=>[]]);
                }
                $ctx['numbers_group']=[]; $ctx['amount']=null; $ctx['meta']=[]; $ctx['current_type']=null;
                return;
            }
    
            // fallback: emit như thường
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
    
        // Region chuẩn hoá
        $region = $context['region'] ?? session('global_region', 'nam'); // bac|trung|nam
        $region = match (strtolower((string)$region)) {
            'bac','mb'   => 'bac',
            'trung','mt' => 'trung',
            default      => 'nam',
        };
    
        // Default stations theo miền
        $defaultStations = match ($region) {
            'bac'  => ['mien bac'],
            'trung'=> ['da nang','khanh hoa','phu yen','quang nam','quang ngai','binh dinh','thua thien hue'],
            default=> ['tp.hcm'],
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
            'xc_d_list'      => [],
            'xc_dd_amount'   => null,
            'last_numbers'   => [],
            'just_saw_station' => false,
        ];
    
        $outBets = [];
    
        // -------------------------------
        // 3) Dicts
        // -------------------------------
        $stationAliases = [
            // Nam
            'hcm' => 'tp.hcm', 'tphcm'=>'tp.hcm','sg'=>'tp.hcm',
            'tn'  => 'tay ninh','ag'=>'an giang','tg'=>'tien giang','bt'=>'ben tre',
            'vl'  => 'vinh long','tv'=>'tra vinh','kg'=>'kien giang','dl'=>'da lat',
            // MB
            'hn'  => 'mien bac','mb'=>'mien bac',
            // Một số tên đầy đủ đã strip accents
            'tien' => 'tien giang', // phòng user gõ 'tien'
        ];
    
        $typeAliases = [
            'lo'   => 'bao_lo',
            'dau'  => 'dau',
            'duoi' => 'duoi',
            'dd'   => 'dau_duoi',
            'xc'   => 'xiu_chu',
            'keo'  => 'keo_hang_don_vi',
            'dt'   => 'da_thang',
            'dx'   => 'da_xien',
        ];
    
        // -------------------------------
        // 4) Scan tokens
        // -------------------------------
        foreach ($tokens as $tok) {
    
            // Số 2-4 chữ số (giữ leading zero)
            if (preg_match('/^\d{2,4}$/', $tok)) {
                // Đặc thù KÉO: nếu đang ở 'keo' và đã có keo_start -> mở rộng dải
                if (($ctx['current_type'] ?? null) === 'keo_hang_don_vi' && !empty($ctx['meta']['keo_start'])) {
                    $start = (string)$ctx['meta']['keo_start'];
                    $end   = (string)$tok;
                    $expanded = $expandKeoNumbers($start, $end);
                    if (!empty($expanded)) {
                        $ctx['numbers_group'] = $expanded;
                        $ctx['last_numbers']  = $expanded;
                        $addEvent($events, 'keo_expand', ['start'=>$start,'end'=>$end,'expanded'=>$expanded]);
                    } else {
                        // fallback: nếu không expand được, coi như số bình thường
                        $ctx['numbers_group'][] = $tok;
                        $ctx['last_numbers']    = $ctx['numbers_group'];
                        $addEvent($events, 'number', ['value'=>$tok, 'note'=>'keo_expand_failed']);
                    }
                    $ctx['just_saw_station'] = false;
                    continue;
                }
    
                // Bình thường
                $ctx['numbers_group'][] = $tok;
                $ctx['last_numbers']    = $ctx['numbers_group'];
                $ctx['just_saw_station']= false;
                $addEvent($events, 'number', ['value'=>$tok]);
                continue;
            }
    
            // Dấu chấm: flush nhóm hiện tại
            if ($tok === '.') {
                if ($isGroupPending($ctx)) {
                    $flushGroup($outBets, $ctx, $events, 'dot_flush_or_hold');
                } else {
                    $addEvent($events, 'dot_flush_or_hold');
                }
                $ctx['just_saw_station'] = false;
                continue;
            }
    
            // Amount rời: 10n|10k
            if (preg_match('/^(\d+)(n|k)$/', $tok, $m)) {
                $ctx['amount'] = (int)$m[1] * 1000;
                $currType = $ctx['current_type'] ?? null;
                $addEvent($events, 'amount_loose', [
                    'token'=>$tok, 'type'=>$currType, 'amount'=>$ctx['amount']
                ]);
                $ctx['just_saw_station'] = false;
                continue;
            }
    
            // Cặp dính: d100n / dd20n / lo5n
            if (preg_match('/^(d|dd|lo)(\d+)(n|k)$/', $tok, $m)) {
                $code = $m[1];
                $amt  = (int)$m[2] * 1000;
    
                // Nếu đang ở XỈU CHỦ -> gom config cho xỉu chủ
                if (($ctx['current_type'] ?? null) === 'xiu_chu') {
                    if ($code === 'dd') {
                        $ctx['xc_dd_amount'] = $amt;
                        $addEvent($events,'xc_pair_dd',['token'=>$tok,'amount'=>$amt]);
                    } elseif ($code === 'd') {
                        $ctx['xc_d_list'][] = $amt;
                        $addEvent($events,'xc_pair_d',['token'=>$tok,'amount'=>$amt,'index'=>count($ctx['xc_d_list'])]);
                    } else { // lo
                        $ctx['amount'] = $amt;
                        $addEvent($events,'xc_amount_through_lo',['token'=>$tok,'amount'=>$amt]);
                    }
                    $ctx['just_saw_station'] = false;
                    continue;
                }
    
                // Target type khi không phải xỉu chủ
                $targetType = match ($code) {
                    'lo' => 'bao_lo',
                    'dd' => 'dau_duoi',
                    default => 'dau',
                };
    
                if (($ctx['current_type'] ?? null) !== null
                    && $targetType !== $ctx['current_type']
                    && $isGroupPending($ctx)) {
                    $flushGroup($outBets, $ctx, $events, 'type_switch_flush');
                }
    
                // Kế thừa số nếu group đang rỗng
                if (empty($ctx['numbers_group']) && !empty($ctx['last_numbers'])) {
                    $ctx['numbers_group'] = $ctx['last_numbers'];
                    $addEvent($events,'inherit_numbers_for_amount',['numbers'=>$ctx['numbers_group']]);
                }
    
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
    
            // Xiên (MB only): xi2|xi3|xi4|xien2|xien3|xien4
            if (preg_match('/^(xi(?:en)?([234]))$/', $tok, $m)) {
                $size = (int)$m[2];
                if ($region !== 'bac') {
                    $addEvent($events, 'skip_xien_wrong_region', [
                        'token'=>$tok, 'region'=>$region,
                        'message'=>'Loại cược xiên ('.$tok.') chỉ áp dụng cho Miền Bắc. Khu vực hiện tại: '.$region.'.'
                    ]);
                    $errors[] = 'Xiên chỉ áp dụng cho Miền Bắc. Token: '.$tok;
                    // KHÔNG set current_type để không flush xiên
                    $ctx['just_saw_station'] = false;
                    continue;
                }
    
                // đổi kiểu → flush nhóm cũ nếu pending
                if (($ctx['current_type'] ?? null) !== null
                    && 'xien' !== $ctx['current_type']
                    && $isGroupPending($ctx)) {
                    $flushGroup($outBets, $ctx, $events, 'type_switch_flush');
                }
    
                if (empty($ctx['numbers_group']) && !empty($ctx['last_numbers'])) {
                    $ctx['numbers_group'] = $ctx['last_numbers'];
                    $addEvent($events,'inherit_numbers_for_type',['type'=>'xien','numbers'=>$ctx['numbers_group']]);
                }
    
                $ctx['current_type'] = 'xien';
                $ctx['meta']['xien_size'] = $size;
                $ctx['just_saw_station'] = false;
                $addEvent($events, 'type_loose', ['token'=>$tok,'type'=>'xien','xien_size'=>$size]);
                continue;
            }
    
            // Kiểu cược rời (bao gồm 'keo')
            if (isset($typeAliases[$tok])) {
                $newType = $typeAliases[$tok];
    
                if (($ctx['current_type'] ?? null) !== null
                    && $newType !== $ctx['current_type']
                    && $isGroupPending($ctx)) {
                    $flushGroup($outBets, $ctx, $events, 'type_switch_flush');
                }
    
                // ĐẶC THÙ 'kéo': đánh dấu điểm bắt đầu
                if ($newType === 'keo_hang_don_vi') {
                    $start = null;
                    if (!empty($ctx['numbers_group'])) {
                        $start = end($ctx['numbers_group']);
                    } elseif (!empty($ctx['last_numbers'])) {
                        $start = end($ctx['last_numbers']);
                        $ctx['numbers_group'] = [$start];
                    }
                    $ctx['current_type'] = 'keo_hang_don_vi';
                    $ctx['meta']['keo_start'] = $start;
                    $ctx['just_saw_station']   = false;
                    $addEvent($events, 'type_loose', [
                        'token'=>$tok,'type'=>'keo_hang_don_vi','keo_start'=>$start
                    ]);
                    continue;
                }
    
                if (empty($ctx['numbers_group']) && !empty($ctx['last_numbers'])) {
                    $ctx['numbers_group'] = $ctx['last_numbers'];
                    $addEvent($events,'inherit_numbers_for_type',['type'=>$newType,'numbers'=>$ctx['numbers_group']]);
                }
    
                $ctx['current_type']   = $newType;
                $ctx['just_saw_station']= false;
                $addEvent($events, 'type_loose', ['token'=>$tok,'type'=>$ctx['current_type']]);
                continue;
            }
    
            // Station
            if (isset($stationAliases[$tok])) {
                $name = $stationAliases[$tok];
    
                // nếu có nhóm pending → flush trước khi đổi đài
                if ($isGroupPending($ctx)) {
                    $flushGroup($outBets, $ctx, $events, 'station_switch_flush');
                }
    
                // set lại danh sách đài: rule hiện tại → đài đơn
                $ctx['stations'] = [$name];
                $ctx['just_saw_station'] = true;
                $addEvent($events, 'stations', ['set'=>array_values($ctx['stations'])]);
                continue;
            }
    
            // Skip rác
            $addEvent($events, 'skip', ['token'=>$tok]);
        }
    
        // -------------------------------
        // 5) Flush cuối
        // -------------------------------
        $flushGroup($outBets, $ctx, $events, 'final_flush');
    
        // Áp station mặc định nếu thiếu
        foreach ($outBets as &$b) {
            if (empty($b['station'])) {
                $b['station'] = $joinStations(count($ctx['stations']) ? $ctx['stations'] : $defaultStations);
            }
            $b['meta'] = $b['meta'] ?? [];
        }
        unset($b);
    
        // Kết quả
        if (empty($outBets)) {
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
    
        return [
            'is_valid'        => true,
            'multiple_bets'   => $outBets,
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

<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\BettingType;
use App\Models\LotterySchedule;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;

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
     * Parse 1 tin nhắn cược -> nhiều phiếu
     *
     * @param  string            $message
     * @param  array|string|int  $context  (array: ['region'=>..,'date'=>..] | string: region | int: customer_id)
     */
    public function parseMessage(string $message, array|string|int $context = []): array
    {
        // Chuẩn hoá context
        $ctx = is_array($context)
            ? $context
            : (is_string($context) ? ['region' => $context] : (is_int($context) ? ['customer_id' => $context] : []));

        $errors     = [];
        $normalized = $this->normalize($message);
        $tokens     = $this->tokenize($normalized);

        // Region/Date (session fallback)
        $region  = Str::lower((string)($ctx['region'] ?? session('global_region', 'nam')));
        $dateStr = (string)($ctx['date']   ?? session('global_date', CarbonImmutable::now()->toDateString()));
        $date    = CarbonImmutable::parse($dateStr);

        // Rule 5: Đài mặc định -> CHỈ MAIN (1 đài)
        $defaultStations = $this->normalizedStationsFor($date, $region, 1);
        if (empty($defaultStations)) $defaultStations = ['tp.hcm'];

        $bets         = [];
        $lastNumbers  = [];                // Rule 1: reuse số gần nhất
        $lastStations = $defaultStations;  // Rule 2 + 5: reuse đài gần nhất

        // Streaming state
        $groupNumbers = [];
        $groupPairs   = [];                // [type, amount] theo thứ tự nhập
        $pendingType  = null;              // khi gặp type rời chờ amount
        $lastTypeUsed = null;              // nhớ loại cược gần nhất để reuse khi gặp amount rời

        // Giữ-dồn số qua nhiều dấu chấm cho đến khi gặp kiểu/tiền
        $heldNumbers  = [];

        // ✨ Kéo: chỉ thị tạo dãy số
        $keoPending   = false;             // đang chờ số-kết cho "kéo"
        $keoStart     = null;              // số-bắt-đầu cho "kéo"

        // Debug (tuỳ bạn bật/tắt trả ra)
        $debug = [
            'stations_default' => $defaultStations,
            'events' => [],
        ];

        // ======= FLUSH (đã hỗ trợ dt, dx theo luật mới) =======
        $flush = function () use (
            &$bets, &$groupNumbers, &$groupPairs, &$lastNumbers, &$lastStations, &$heldNumbers, &$errors
        )
        {
            if (empty($groupPairs)) {
                $groupNumbers = [];
                return;
            }

            // Rule 1: nếu nhóm không có số -> dùng số gần nhất (có thể là union trong $heldNumbers)
            $numbers = !empty($groupNumbers) ? $groupNumbers : $lastNumbers;
            if (empty($numbers)) {
                $groupPairs   = [];
                $groupNumbers = [];
                return;
            }
            $lastNumbers = $numbers;

            // 1) Xen kẽ 'd' trong CÙNG NHÓM, chỉ xét {dau, duoi, dau_duoi}
            $dirTypes = ['dau', 'duoi', 'dau_duoi'];
            $hasDuoi  = false;
            $hasDD    = false;
            $dauIdx   = [];

            foreach ($groupPairs as $i => [$t, $_]) {
                if (!in_array($t, $dirTypes, true)) continue; // bỏ qua lo, xiu_chu, da_xien, da_thang...
                if     ($t === 'duoi')      $hasDuoi = true;
                elseif ($t === 'dau_duoi')  $hasDD   = true;
                elseif ($t === 'dau')       $dauIdx[] = $i;
            }

            if (!$hasDuoi && !$hasDD && count($dauIdx) >= 2) {
                // chỉ có các cặp 'd' -> xen kẽ: đầu, đuôi, đầu, đuôi...
                $toggle = 0;
                foreach ($dauIdx as $i) {
                    $groupPairs[$i][0] = ($toggle % 2 === 0) ? 'dau' : 'duoi';
                    $toggle++;
                }
            }

            // 2) Ghi bet
            foreach ($groupPairs as [$type, $amount]) {
                // ================= DT (ĐÁ THẲNG) =================
                if ($type === 'da_thang') {
                    // Ghép cặp theo thứ tự 2–2: (n1,n2), (n3,n4), ...
                    $uniq = array_values(array_unique($numbers));
                    $pairs = [];
                    for ($i = 0; $i + 1 < count($uniq); $i += 2) {
                        $pairs[] = [$uniq[$i], $uniq[$i+1]];
                    }
                    if (empty($pairs)) {
                        // không đủ số để tạo ít nhất 1 cặp
                        continue;
                    }

                    // DT áp dụng cho 1 đài: chọn đài đầu tiên, cảnh báo nếu khác 1
                    $st = $lastStations[0] ?? null;
                    if (count($lastStations) !== 1) {
                        $errors[] = 'Đá thẳng (dt) yêu cầu đúng 1 đài, hiện có: '.count($lastStations).'. Sẽ dùng đài đầu tiên: '.($st ?? 'null');
                    }

                    foreach ($pairs as $p) {
                        $bets[] = [
                            'station' => $st,
                            'type'    => 'da_thang',
                            'numbers' => $p,             // một vé = một cặp
                            'amount'  => $amount,
                            'meta'    => [],
                        ];
                    }
                    continue;
                }

                // ================= DX (ĐÁ XIÊN ≥ 2 ĐÀI) =================
                if ($type === 'da_xien') {
                    // Phải có ≥ 2 đài
                    if (count($lastStations) < 2) {
                        $errors[] = 'Đá xiên (dx) yêu cầu tối thiểu 2 đài. Bỏ qua nhóm này.';
                        continue;
                    }
                
                    // Sinh mọi cặp số C(n,2)
                    $uniqNums = array_values(array_unique($numbers));
                    if (count($uniqNums) < 2) continue;
                
                    $numPairs = [];
                    for ($i = 0; $i < count($uniqNums) - 1; $i++) {
                        for ($j = $i + 1; $j < count($uniqNums); $j++) {
                            $numPairs[] = [$uniqNums[$i], $uniqNums[$j]];
                        }
                    }
                
                    // Cặp đài C(m,2) để ghi vào meta
                    $stationPairs = [];
                    for ($a = 0; $a < count($lastStations) - 1; $a++) {
                        for ($b = $a + 1; $b < count($lastStations); $b++) {
                            $stationPairs[] = [$lastStations[$a], $lastStations[$b]];
                        }
                    }
                    $stationLabel = implode(' + ', $lastStations);
                
                    foreach ($numPairs as $pair) {
                        $bets[] = [
                            'station' => $stationLabel,
                            'type'    => 'da_xien',
                            'numbers' => $pair, // một vé = một cặp số
                            'amount'  => $amount,
                            'meta'    => [
                                'xien_size'     => 2,
                                'station_pairs' => $stationPairs,
                            ],
                        ];
                    }
                    continue;
                }
                // ================= DD (ĐẦU & ĐUÔI) — giữ nguyên logic tách 2 vé/1 số =================
                if ($type === 'dau_duoi') {
                    foreach ($numbers as $num) {
                        foreach ($lastStations as $st) {
                            $bets[] = ['station'=>$st,'type'=>'dau','numbers'=>[$num],'amount'=>$amount,'meta'=>[]];
                            $bets[] = ['station'=>$st,'type'=>'duoi','numbers'=>[$num],'amount'=>$amount,'meta'=>[]];
                        }
                    }
                    continue;
                }

                // ================= CÁC LOẠI KHÁC (dau/duoi/bao_lo/xiu_chu/...) =================
                $meta = [];
                // (giữ nguyên meta cho da_xien cũ nếu có, nhưng giờ dx đã xử lý ở trên)

                // TÁCH PHIẾU THEO TỪNG SỐ cho: dau, duoi, bao_lo, xiu_chu (khi nhóm có >1 số)
                if (in_array($type, ['dau', 'duoi', 'bao_lo', 'xiu_chu'], true) && count($numbers) > 1) {
                    foreach ($numbers as $num) {
                        foreach ($lastStations as $st) {
                            $bets[] = ['station'=>$st,'type'=>$type,'numbers'=>[$num],'amount'=>$amount,'meta'=>$meta];
                        }
                    }
                } else {
                    // Các loại khác (hoặc chỉ 1 số) -> giữ nguyên nhóm
                    foreach ($lastStations as $st) {
                        $bets[] = ['station'=>$st,'type'=>$type,'numbers'=>array_values($numbers),'amount'=>$amount,'meta'=>$meta];
                    }
                }
            }

            // reset nhóm
            $groupPairs   = [];
            $groupNumbers = [];
            $heldNumbers  = [];   // reset buffer sau khi ghi bet
        };
        // ======= /FLUSH =======


        // ==================== STREAM ====================
        $i = 0;
        while ($i < count($tokens)) {
            $tk = $tokens[$i];

            // Kết tiểu câu
            if ($tk === '.') {
                if (empty($groupPairs) && !empty($groupNumbers)) {
                    // Chưa có kiểu/tiền -> cộng dồn số vào buffer, set lastNumbers = buffer
                    $heldNumbers  = array_values(array_unique(array_merge($heldNumbers, $groupNumbers)));
                    $lastNumbers  = $heldNumbers;
                    $groupNumbers = [];
                } else {
                    // Có cặp kiểu/tiền -> chốt nhóm bình thường
                    $flush(); // sẽ reset $heldNumbers
                }
                $pendingType = null;
                $keoPending  = false; $keoStart = null; // kết câu thì hủy kéo đang chờ
                $debug['events'][] = ['kind' => 'dot_flush_or_hold'];
                $i++; continue;
            }

            // Directive 2d/3d
            if ($this->isMultiStationDirective($tk)) {
                $n = $this->directiveSize($tk);
                $stations = $this->normalizedStationsFor($date, $region, $n); // đúng 2 hoặc 3 đài
                if (!empty($stations)) $lastStations = $stations; // Rule 4
                $debug['events'][] = ['kind' => 'directive', 'token' => $tk, 'stations' => $stations];
                $i++; continue;
            }

            // Chuỗi đài
            if ($this->isStationToken($tk)) {
                $stations = [];
                while ($i < count($tokens) && $this->isStationToken($tokens[$i])) {
                    $stations[] = $this->canonicalStation($tokens[$i]); $i++;
                }
                if (!empty($stations)) {
                    $lastStations = $stations; // Rule 2
                    $debug['events'][] = ['kind' => 'stations', 'set' => $stations];
                }
                // đổi đài thì cũng kết thúc "kéo" đang chờ (nếu có)
                $keoPending = false; $keoStart = null;
                continue;
            }

            // Combo "station+number"? vd 'tn21'
            [$stCombo, $numCombo] = $this->splitStationNumberToken($tk);
            if ($stCombo !== null) {
                // set đài
                $lastStations = [$stCombo];

                // nếu đang có nhóm đã có số + có cặp -> chốt nhóm cũ
                if (!empty($groupPairs) && !empty($groupNumbers)) {
                    $flush();
                }
                // thêm số vào nhóm hiện tại
                $groupNumbers[] = $numCombo;
                $debug['events'][] = ['kind'=>'station_number_combo', 'token'=>$tk, 'station'=>$stCombo, 'number'=>$numCombo];

                // đổi đài/xảy ra combo thì hủy "kéo" đang chờ
                $keoPending = false; $keoStart = null;

                $i++; continue;
            }

            // Combo "number+type+amount"? vd '952xc13n'
            [$numNTA, $typeNTA, $amtNTA] = $this->splitNumberTypeAmountToken($tk);
            if ($numNTA !== null) {
                // nếu đã có nhóm (có số & có cặp) -> chốt nhóm cũ trước
                if (!empty($groupPairs) && !empty($groupNumbers)) {
                    $flush();
                }
                // gộp số vào nhóm hiện tại
                $groupNumbers[] = $numNTA;
                $groupPairs[]   = [$typeNTA, $amtNTA];
                $lastTypeUsed   = $typeNTA;   // nhớ loại
                $pendingType    = null;

                // combo này không liên quan "kéo" => hủy nếu đang chờ
                $keoPending = false; $keoStart = null;

                $debug['events'][] = [
                    'kind'=>'combo_num_type_amount',
                    'token'=>$tk, 'number'=>$numNTA, 'type'=>$typeNTA, 'amount'=>$amtNTA
                ];
                $i++; continue;
            }

            // Token gộp type+amount? (vd 'd100n', 'dd20', 'lo5n', 'dx1n')
            [$comboType, $comboAmount] = $this->splitTypeAmountToken($tk);
            if ($comboType !== null && $comboAmount !== null) {
                $groupPairs[]  = [$comboType, $comboAmount];
                $lastTypeUsed  = $comboType; // nhớ loại vừa dùng
                $pendingType   = null;

                // gặp cặp kiểu+tiền thì hủy "kéo" đang chờ (nếu có)
                $keoPending = false; $keoStart = null;

                $debug['events'][] = ['kind'=>'pair_combo', 'token'=>$tk, 'type'=>$comboType, 'amount'=>$comboAmount];
                $i++; continue;
            }

            // Type rời?
            $key = $this->cleanAlphaToken($tk);
            if ($key !== '' && isset($this->typeAliasMap[$key])) {
                $cand = $this->canonicalizeTypeCode($this->typeAliasMap[$key]);

                // ✨ "kéo" là CHỈ THỊ sinh dãy số, không phải loại cược
                if ($cand === 'keo_hang_don_vi') {
                    $keoPending = true;
                    // ưu tiên lấy số cuối cùng ngay trước "kéo" trong nhóm hiện tại; nếu chưa có thì dùng lastNumbers
                    $keoStart = !empty($groupNumbers)
                        ? end($groupNumbers)
                        : (!empty($lastNumbers) ? end($lastNumbers) : null);

                    $debug['events'][] = ['kind'=>'keo_begin', 'start'=>$keoStart];
                    $i++; 
                    continue; // KHÔNG set pendingType
                }

                $pendingType = $cand;
                $debug['events'][] = ['kind'=>'type_loose', 'token'=>$tk, 'type'=>$pendingType];

                // set loại cược xong thì cũng kết thúc "kéo" đang chờ
                $keoPending = false; $keoStart = null;

                $i++; continue;
            }

            // ✨ Hoàn tất "kéo": số-kết sau "kéo" -> tạo dãy số theo mask
            if ($keoPending && $this->isPureNumberToken($tk)) {
                if ($keoStart !== null) {
                    // giữ padding theo độ dài số-bắt-đầu
                    $end   = str_pad($tk, strlen($keoStart), '0', STR_PAD_LEFT);
                    $range = $this->generateKeoRange($keoStart, $end);

                    if (!empty($range)) {
                        // thay nhóm số hiện tại bằng dãy kéo (unique & giữ thứ tự)
                        $groupNumbers = array_values(array_unique($range));
                        $lastNumbers  = $groupNumbers;
                        $debug['events'][] = ['kind'=>'keo_range', 'start'=>$keoStart, 'end'=>$end, 'numbers'=>$groupNumbers];
                    } else {
                        $debug['events'][] = ['kind'=>'keo_range_invalid', 'start'=>$keoStart, 'end'=>$tk];
                    }
                } else {
                    $debug['events'][] = ['kind'=>'keo_no_start'];
                }

                // reset trạng thái "kéo"
                $keoPending = false;
                $keoStart   = null;
                $i++; 
                continue;
            }

            // SPECIAL (refined):
            // Chỉ coi "số 1-4 chữ số" là AMOUNT nếu đang có pendingType
            // VÀ đã có số trong NGỮ CẢNH HIỆN TẠI (groupNumbers hoặc heldNumbers).
            if (
                $pendingType !== null
                && preg_match('/^\d{1,4}$/', $tk)
                && ( !empty($groupNumbers) || !empty($heldNumbers) )
            ) {
                $amt = (int) $tk * 1000;
                $groupPairs[]  = [$pendingType, $amt];
                $lastTypeUsed  = $pendingType;
                $pendingType   = null;
                $debug['events'][] = [
                    'kind'=>'amount_numeric_after_type',
                    'token'=>$tk,
                    'type'=>($groupPairs[count($groupPairs)-1][0] ?? null),
                    'amount'=>$amt
                ];
                $i++; 
                continue;
            }

            // Số? (ƯU TIÊN nhận diện số trước amount rời)
            if ($this->isPureNumberToken($tk)) {
                if (!empty($groupPairs) && !empty($groupNumbers)) {
                    $debug['events'][] = ['kind'=>'new_number_flush', 'prev_numbers'=>$groupNumbers];
                    $flush();
                }
                $groupNumbers[] = $tk;
                $debug['events'][] = ['kind'=>'number', 'value'=>$tk];
                $i++; continue;
            }

            // Amount rời? (phải có đơn vị n/k, hoặc >= 5 chữ số)
            if ($this->isAmountToken($tk)) {
                $typeToUse = $pendingType ?? $lastTypeUsed; // ưu tiên pending, không có thì lấy loại gần nhất
                if ($typeToUse !== null) {
                    $amt = $this->parseAmount($tk);

                    // nếu token không có n/k & là số <=4 chữ số -> *1000
                    if (!preg_match('/[nk]$/i', $tk) && preg_match('/^\d{1,4}$/', $tk)) {
                        $amt *= 1000;
                    }

                    $groupPairs[]  = [$typeToUse, $amt];
                    $lastTypeUsed  = $typeToUse;
                    $pendingType   = null;
                    $debug['events'][] = ['kind'=>'amount_loose','token'=>$tk,'type'=>$typeToUse,'amount'=>$amt];
                } else {
                    $debug['events'][] = ['kind'=>'amount_orphan','token'=>$tk];
                }
                $i++; continue;
            }

            // Khác -> bỏ qua
            $debug['events'][] = ['kind'=>'skip', 'token'=>$tk];
            $i++;
        }

        // flush phần cuối
        $flush();

        return [
            'is_valid'       => count($bets) > 0,
            'multiple_bets'  => $bets,
            'errors'         => $errors,
            'normalized'     => $normalized,
            'parsed_message' => $normalized,
            'tokens'         => $tokens,
            'debug'          => $debug, // bỏ nếu không muốn trả ra
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

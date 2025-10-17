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
        // Fallback luôn có + DB override (đảm bảo 'd','dd','b','lo','de','dx','xc' tồn tại)
        $this->typeAliasMap    = $this->defaultTypeAliases();
        $dbMap                 = BettingType::aliasMap();
        foreach ($dbMap as $alias => $code) {
            $this->typeAliasMap[$alias] = $code; // override/thêm từ DB
        }

        $this->stationAliasMap = $this->defaultStationAliases();
    }

    /**
     * @param  string            $message
     * @param  array|string|int  $context  (array: ['region'=>..,'date'=>..] | string: region | int: customer_id)
     */
    public function parseMessage(string $message, array|string|int $context = []): array
    {
        // Chuẩn hoá context
        $ctx = is_array($context)
            ? $context
            : (is_string($context) ? ['region'=>$context] : (is_int($context) ? ['customer_id'=>$context] : []));

        $normalized = $this->normalize($message);
        $tokens     = $this->tokenize($normalized);

        // Region/Date (session fallback)
        $region  = Str::lower((string)($ctx['region'] ?? session('global_region', 'nam')));
        $dateStr = (string)($ctx['date']   ?? session('global_date', CarbonImmutable::now()->toDateString()));
        $date    = CarbonImmutable::parse($dateStr);

        // Đài mặc định (Rule 5)
        $defaultStations = $this->normalizedStationsFor($date, $region, null);
        if (empty($defaultStations)) $defaultStations = ['tp.hcm'];

        $bets         = [];
        $lastNumbers  = [];               // Rule 1
        $lastStations = $defaultStations; // Rule 2 + 5

        // Trạng thái nhóm (streaming)
        $groupNumbers = [];
        $groupPairs   = [];               // danh sách [type, amount] theo thứ tự nhập
        $pendingType  = null;             // khi gặp type rời chờ amount

        // Debug trail (xem trong JSON)
        $debug = [
            'stations_default' => $defaultStations,
            'events' => [],
        ];

        // ======= FLUSH (xen kẽ 'd' trong nhóm & tách từng số cho dau/duoi & tách dd thành 2 vé/1 số) =======
        $flush = function () use (&$bets, &$groupNumbers, &$groupPairs, &$lastNumbers, &$lastStations)
        {
            if (empty($groupPairs)) { $groupNumbers = []; return; }

            // Rule 1: nếu nhóm không có số -> dùng số gần nhất
            $numbers = !empty($groupNumbers) ? $groupNumbers : $lastNumbers;
            if (empty($numbers)) { $groupPairs = []; $groupNumbers = []; return; }
            $lastNumbers = $numbers;

            // 1) Nếu nhóm CHỈ có 'dau' và KHÔNG có 'duoi' hay 'dau_duoi' -> xen kẽ dau/duoi 1-2-3-...
            $hasDuoi = false; $hasDD = false; $onlyDau = true;
            foreach ($groupPairs as [$t, $_]) {
                if ($t === 'duoi')      $hasDuoi = true;
                if ($t === 'dau_duoi')  $hasDD   = true;
                if ($t !== 'dau')       $onlyDau = false;
            }
            if ($onlyDau && !$hasDuoi && !$hasDD) {
                $toggle = 0;
                foreach ($groupPairs as $i => [$t, $a]) {
                    $groupPairs[$i][0] = ($toggle % 2 === 0) ? 'dau' : 'duoi';
                    $toggle++;
                }
            }

            // 2) Ghi bet
            foreach ($groupPairs as [$type, $amount]) {
                $meta = [];

                // ✨ TÁCH 'dd' THÀNH 2 VÉ (ĐẦU & ĐUÔI) CHO MỖI SỐ
                if ($type === 'dau_duoi') {
                    foreach ($numbers as $num) {
                        foreach ($lastStations as $st) {
                            $bets[] = [
                                'station' => $st,
                                'type'    => 'dau',
                                'numbers' => [$num],
                                'amount'  => $amount,
                                'meta'    => $meta,
                            ];
                            $bets[] = [
                                'station' => $st,
                                'type'    => 'duoi',
                                'numbers' => [$num],
                                'amount'  => $amount,
                                'meta'    => $meta,
                            ];
                        }
                    }
                    continue; // đã xử lý xong 'dd'
                }

                // ✨ TÁCH PHIẾU THEO TỪNG SỐ cho 'dau' / 'duoi' khi nhóm có nhiều số
                if (in_array($type, ['dau', 'duoi'], true) && count($numbers) > 1) {
                    foreach ($numbers as $num) {
                        foreach ($lastStations as $st) {
                            $bets[] = [
                                'station' => $st,
                                'type'    => $type,
                                'numbers' => [$num],
                                'amount'  => $amount,
                                'meta'    => $meta,
                            ];
                        }
                    }
                } else {
                    // Các loại khác (bao_lo, da_xien, …) hoặc khi chỉ có 1 số: giữ nguyên nhóm
                    foreach ($lastStations as $st) {
                        $bets[] = [
                            'station' => $st,
                            'type'    => $type,
                            'numbers' => array_values($numbers),
                            'amount'  => $amount,
                            'meta'    => $meta,
                        ];
                    }
                }
            }

            // reset nhóm
            $groupPairs   = [];
            $groupNumbers = [];
        };
        // ======= /FLUSH =======




        $i = 0;
        while ($i < count($tokens)) {
            $tk = $tokens[$i];

            // Kết tiểu câu
            if ($tk === '.') {
                $debug['events'][] = ['kind'=>'dot_flush'];
                $flush(); $pendingType = null; $i++; continue;
            }

            // Directive 2d/3d
            if ($this->isMultiStationDirective($tk)) {
                $n = $this->directiveSize($tk);
                $stations = $this->normalizedStationsFor($date, $region, $n);
                if (!empty($stations)) $lastStations = $stations; // Rule 4
                $debug['events'][] = ['kind'=>'directive', 'token'=>$tk, 'stations'=>$stations];
                $i++; continue;
            }

            // Chuỗi đài
            if ($this->isStationToken($tk)) {
                $stations = [];
                while ($i < count($tokens) && $this->isStationToken($tokens[$i])) {
                    $st = $this->canonicalStation($tokens[$i]);
                    $stations[] = $st; $i++;
                }
                if (!empty($stations)) {
                    $lastStations = $stations; // Rule 2
                    $debug['events'][] = ['kind'=>'stations', 'set'=>$stations];
                }
                continue;
            }

            // Token gộp type+amount? (vd 'd100n', 'dd10n', 'lo20n')
            [$comboType, $comboAmount] = $this->splitTypeAmountToken($tk);
            if ($comboType !== null && $comboAmount !== null) {
                $groupPairs[] = [$comboType, $comboAmount];
                $pendingType  = null;
                $debug['events'][] = ['kind'=>'pair_combo', 'token'=>$tk, 'type'=>$comboType, 'amount'=>$comboAmount];
                $i++; continue;
            }

            // Type rời?
            $key = $this->cleanAlphaToken($tk);
            if ($key !== '' && isset($this->typeAliasMap[$key])) {
                $pendingType = $this->typeAliasMap[$key];
                $debug['events'][] = ['kind'=>'type_loose', 'token'=>$tk, 'type'=>$pendingType];
                $i++; continue;
            }

            // Số?
            if ($this->isPureNumberToken($tk)) {
                // nếu đã có số + có ít nhất 1 cặp kiểu/tiền -> số mới = nhóm mới
                if (!empty($groupPairs) && !empty($groupNumbers)) {
                    $flush();
                }
                $groupNumbers[] = $tk;
                $i++; continue;
            }

            // Amount rời?
            if ($this->isAmountToken($tk)) {
                if ($pendingType !== null) {
                    $groupPairs[] = [$pendingType, $this->parseAmount($tk)];
                    $pendingType  = null;
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
            'errors'         => [],
            'normalized'     => $normalized,
            'parsed_message' => $normalized,
            'tokens'         => $tokens,
            'debug'          => $debug, // có thể bỏ nếu không muốn trả
        ];
    }

    /* ============ Normalize & Tokenize ============ */

    private function normalize(string $s): string
    {
        $s = Str::lower($s);
        $s = Str::ascii($s);
        $s = str_replace(['đ','Đ'], ['d','d'], $s);

        // 2,5n -> 2.5n
        $s = preg_replace('/(?<=\d),(?=\d)/', '.', $s);

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

    /* ============ Classifiers & Parsers ============ */

    private function isAmountToken(string $w): bool
    {
        // Có đơn vị n/k (có thể có 'x' và phần thập phân)
        if (preg_match('/^x?\d+(?:[.,]\d+)?(?:n|k)$/', $w)) {
            return true;
        }
        // Chỉ số, >=5 chữ số -> coi là amount
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
     * Tách token gộp "alias+amount": vd 'dx1n', 'd100n', 'lo20k', 'dd10n'
     * @return array{0:?string,1:?int}
     */
    private function splitTypeAmountToken(string $w): array
    {
        if (!preg_match('/^([a-z_]+)(x?\d+(?:[.,]\d+)?(?:n|k)?)$/i', $w, $m)) {
            return [null, null];
        }
        $alias  = $this->cleanAlphaToken($m[1]);
        $type   = $this->typeAliasMap[$alias] ?? $this->mapFuzzyAlias($alias);
        if ($type === null) return [null, null];
        return [$type, $this->parseAmount($m[2])];
    }

    private function mapFuzzyAlias(string $alias): ?string
    {
        // Phòng trường hợp DB dùng alias lạ nhưng vẫn viết 'd','dd','b','lo','de','dx','xc'
        return match ($alias) {
            'd'  => 'dau',
            'dd' => 'dau_duoi',
            'b'  => 'duoi',
            'lo','bao','baolo' => 'bao_lo',
            'de' => 'de',
            'dx','dax','daxeo','dacheo' => 'da_xien',
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

    /* ============ Schedule helpers ============ */

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

    /* ============ Fallback aliases ============ */

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
        ];
    }

    private function defaultStationAliases(): array
    {
        return [
            // Miền Nam
            'tp' => 'tp.hcm', 'hcm' => 'tp.hcm', 'tphcm' => 'tp.hcm',
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

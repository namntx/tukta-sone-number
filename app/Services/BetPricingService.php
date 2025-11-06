<?php

namespace App\Services;

use Illuminate\Support\Arr;
use App\Services\BettingRateResolver;

class BetPricingService
{
    protected ?BettingRateResolver $resolver = null;
    protected string $region = 'nam';

    public function begin(?int $customerId, string $region): void
    {
        $this->region = $region;
        $this->resolver = (new BettingRateResolver())->build($customerId, $region);
    }

    /** Nhận nhãn nhóm breakdown đẹp mắt */
    public static function breakdownLabel(string $type, array $meta): string
    {
        $digits   = (int)($meta['digits'] ?? 0);
        $xienSize = (int)($meta['xien_size'] ?? 0);

        return match ($type) {
            'bao_lo'   => ($digits === 3 ? 'Bao lô 3 số' : ($digits === 4 ? 'Bao lô 4 số' : 'Bao lô 2 số')),
            'bao3_lo'  => 'Bao lô 3 số',
            'bao4_lo'  => 'Bao lô 4 số',
            'dau'      => 'Đầu',
            'duoi'     => 'Đuôi',
            'xiu_chu'        => 'Xỉu chủ',
            'xiu_chu_dau'    => 'Xỉu chủ đầu',
            'xiu_chu_duoi'   => 'Xỉu chủ đuôi',
            'xien'     => $xienSize ? ('Xiên '.$xienSize) : 'Xiên',
            'da_thang' => 'Đá thẳng',
            'da_xien'  => 'Đá xiên',
            default    => $type,
        };
    }

    public function rateKeyFor(string $type, array $meta): string
    {
        $digits   = (int)($meta['digits'] ?? 0);
        $xienSize = (int)($meta['xien_size'] ?? 0);

        if (in_array($type, ['bao_lo','bao3_lo','bao4_lo'], true)) {
            if ($type === 'bao3_lo') return 'bao3_lo';
            if ($type === 'bao4_lo') return 'bao4_lo';
            return 'bao_lo'; // dùng digits để phân biệt 2/3/4 số
        }
        if ($type === 'xien') return 'xien'; // có thể tra kèm xien_size

        return $type;
    }

    public function previewForBet(array $bet): array
    {
        $type    = (string)($bet['type'] ?? '');
        $amount  = (int)($bet['amount'] ?? 0);
        $meta    = (array)($bet['meta'] ?? []);
        $numbers = (array)($bet['numbers'] ?? []);

        // detect modifiers
        $digits = (int)($meta['digits'] ?? 0);
        if (!$digits && in_array($type, ['bao_lo','bao3_lo','bao4_lo'], true)) {
            $first = $numbers[0] ?? null;
            if ($first) $digits = strlen((string)$first);
            $meta['digits'] = $digits;
        }
        $xienSize = (int)($meta['xien_size'] ?? 0);

        // resolve rate via cache (sẽ được override cho một số trường hợp đặc biệt)
        $typeCode = $this->rateKeyFor($type, $meta);
        $baseBuyRate = 1.0;
        $basePayout = 0.0;
        if ($this->resolver) {
            [$baseBuyRate, $basePayout] = $this->resolver->resolve($typeCode, $digits ?: null, $xienSize ?: null, (int)($meta['dai_count'] ?? 0) ?: null);
        }
        $buyRate = $baseBuyRate;
        $payout = $basePayout;

        $region = $this->region;
        $cost = 0.0; $win = 0.0;

        // Lô factors theo miền
        $loFactorMNMT = [2 => 18, 3 => 17, 4 => 16];
        $loFactorMB   = [2 => 27, 3 => 23, 4 => 20];

        switch ($type) {
            case 'bao_lo':
            case 'bao3_lo':
            case 'bao4_lo': {
                // Lô: tiền cược * factor * buy_rate
                $f = ($region === 'bac')
                    ? ($loFactorMB[$digits] ?? 27)
                    : ($loFactorMNMT[$digits] ?? 18);
                $cost = $amount * $f * $buyRate;
                $win  = $amount * $payout;
                break;
            }
            case 'dau': {
                // MB: tiền cược * 4 * buy_rate
                // MN/MT: tiền cược * 1 * buy_rate
                $coeff = ($region === 'bac') ? 4 : 1;
                $cost  = $amount * $coeff * $buyRate;
                $win   = $amount * $payout;
                break;
            }
            case 'duoi': {
                // Tất cả miền: tiền cược * 1 * buy_rate
                $cost = $amount * 1 * $buyRate;
                $win  = $amount * $payout;
                break;
            }
            case 'dau_duoi': {
                // Đầu đuôi: (tiền đầu + tiền đuôi) * buy_rate
                // Vì parser tách thành 2 vé riêng (dau + duoi), case này ít khi chạy
                // Nhưng giữ lại cho an toàn
                if ($region === 'bac') {
                    // MB: (dau*4 + duoi) * buy_rate
                    $cost = ($amount * 4 + $amount) * $buyRate;
                } else {
                    // MN/MT: (dau + duoi) * buy_rate
                    $cost = ($amount + $amount) * $buyRate;
                }
                $win = $amount * $payout;
                break;
            }
            case 'xiu_chu': {
                // Xỉu chủ chung (không tách đầu đuôi)
                // MB: tiền cược * 4 * buy_rate (chỉ tính GĐB và G6)
                // MN/MT: tiền cược * 1 * buy_rate (chỉ tính GĐB và G7)
                $coeff = ($region === 'bac') ? 4 : 1;
                $cost  = $amount * $coeff * $buyRate;
                $win   = $amount * $payout;
                break;
            }
            case 'xiu_chu_dau': {
                // Xỉu chủ đầu
                // MB: tiền cược * 3 * buy_rate (G6 là đầu) - dùng rate từ xiu_chu_dau
                // MN/MT: tiền cược * 1 * buy_rate (G7 là đầu) - dùng rate từ xiu_chu
                if ($region === 'bac') {
                    // MB: dùng rate từ xiu_chu_dau (đã resolve ở đầu, giữ nguyên)
                    // Không cần override
                } else {
                    // MN/MT: dùng rate từ xiu_chu
                    if ($this->resolver) {
                        [$buyRate, $payout] = $this->resolver->resolve('xiu_chu', 3);
                    }
                }
                $coeff = ($region === 'bac') ? 3 : 1;
                $cost  = $amount * $coeff * $buyRate;
                $win   = $amount * $payout;
                break;
            }
            case 'xiu_chu_duoi': {
                // Xỉu chủ đuôi
                // MB: dùng rate từ xiu_chu_duoi (đã resolve ở đầu, giữ nguyên)
                // MN/MT: dùng rate từ xiu_chu (GĐB là đuôi)
                if ($region !== 'bac') {
                    // MN/MT: dùng rate từ xiu_chu
                    if ($this->resolver) {
                        [$buyRate, $payout] = $this->resolver->resolve('xiu_chu', 3);
                    }
                }
                $cost = $amount * 1 * $buyRate;
                $win  = $amount * $payout;
                break;
            }
            case 'xien': {
                // Xiên (MB only): tiền cược * buy_rate
                // Ví dụ: xi2 10 20 1n -> kq có 2 số là ăn
                $cost = $amount * $buyRate;
                $win  = $amount * $payout;
                break;
            }
            case 'da_thang': {
                // Đá thẳng
                $n     = count($numbers);
                $pairs = ($n >= 2) ? ($n * ($n - 1) / 2) : 1;
                
                if ($region === 'bac') {
                    // MB: tiền cược * số cặp * 27 * buy_rate
                    $cost = $amount * $pairs * 27 * $buyRate;
                } else {
                    // MN/MT: tiền cược * 2 * 18 * buy_rate (đá thẳng)
                    $cost = $amount * 2 * 18 * $buyRate;
                }
                $win = $amount * $payout;
                break;
            }
            case 'da_xien': {
                // Đá chéo (cross package) - chỉ áp dụng cho MN/MT
                if ($region === 'bac') {
                    // MB không có đá chéo, fallback
                    $cost = $amount * $buyRate;
                    $win = $amount * $payout;
                    break;
                }
                
                // Tính số đài: ưu tiên từ meta (giống BettingSettlementService)
                $stationCount = (int)($meta['dai_count'] ?? 0);
                if (!$stationCount && !empty($meta['station_pairs']) && is_array($meta['station_pairs'])) {
                    $names = [];
                    foreach ($meta['station_pairs'] as $p) {
                        if (is_array($p) && count($p) === 2) {
                            $names[$p[0]] = true; $names[$p[1]] = true;
                        }
                    }
                    $stationCount = count($names);
                }
                
                // Resolve rate lại cho đá xiên (giống BettingSettlementService)
                // Không truyền dai_count vào resolve, chỉ truyền 2 (có thể là default)
                if ($this->resolver) {
                    [$buyRate, $payout] = $this->resolver->resolve('da_xien', null, null, 2);
                }
                
                // MN/MT: Da cheo 2 dai: tiền cược * 4 * 18 * buy_rate
                // Da cheo 3 dai: tiền cược * 4 * 3 * 18 * buy_rate
                // Da cheo 4 dai: tiền cược * 4 * 6 * 18 * buy_rate
                // Logic này phải giống hệt với BettingSettlementService
                $multiplier = match($stationCount) {
                    2 => 4,
                    3 => 4 * 3,
                    4 => 4 * 6,
                    default => 4,
                };
                $cost = $amount * $multiplier * 18 * $buyRate;
                $win  = $amount * $payout;
                break;
            }
            default: {
                $cost = $amount * $buyRate;
                $win  = $amount * $payout;
            }
        }

        return [
            'rate_key'      => $typeCode,
            'buy_rate'      => (float)$buyRate,
            'payout'        => (float)$payout,
            'cost_xac'      => (int) round($cost),
            'potential_win' => (int) round($win),
            'label'         => self::breakdownLabel($type, $meta),
            'meta'          => $meta,
        ];
    }

    /**
     * Gộp breakdown theo label (mỗi loại cược) & tổng.
     * @param array $bets  (đã được attach pricing)
     */
    public static function buildBreakdown(array $bets): array
    {
        $by = [];
        $totalCost = 0; $totalWin = 0;

        foreach ($bets as $b) {
            $pricing = $b['pricing'] ?? null;
            if (!$pricing) continue;
            $label = $pricing['label'] ?? ($b['type'] ?? 'khac');

            if (!isset($by[$label])) {
                $by[$label] = [
                    'label' => $label,
                    'cost_xac' => 0,
                    'potential_win' => 0,
                    'count' => 0,
                ];
            }

            $by[$label]['cost_xac'] += (int)($pricing['cost_xac'] ?? 0);
            $by[$label]['potential_win'] += (int)($pricing['potential_win'] ?? 0);
            $by[$label]['count']++;
            $totalCost += (int)($pricing['cost_xac'] ?? 0);
            $totalWin += (int)($pricing['potential_win'] ?? 0);
        }

        return [
            'by_label' => array_values($by),
            'total_cost_xac' => $totalCost,
            'total_potential_win' => $totalWin,
        ];
    }
}

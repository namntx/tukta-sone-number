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

        // resolve rate via cache
        $typeCode = $this->rateKeyFor($type, $meta);
        [$buyRate, $payout] = $this->resolver
            ? $this->resolver->resolve($typeCode, $digits ?: null, $xienSize ?: null, (int)($meta['dai_count'] ?? 0) ?: null)
            : [1.0, 0.0];

        $region = $this->region;
        $cost = 0.0; $win = 0.0;

        $loFactorMNMT = [2 => 18, 3 => 17, 4 => 16];
        $loFactorMB   = [2 => 27, 3 => 23, 4 => 20];

        switch ($type) {
            case 'bao_lo':
            case 'bao3_lo':
            case 'bao4_lo': {
                $f = ($region === 'bac')
                    ? ($loFactorMB[$digits] ?? 27)
                    : ($loFactorMNMT[$digits] ?? 18);
                $cost = $amount * $f * $buyRate;
                $win  = $amount * $payout;
                break;
            }
            case 'dau': {
                $coeff = ($region === 'bac') ? 4 : 1;
                $cost  = $amount * $coeff * $buyRate;
                $win   = $amount * $payout;
                break;
            }
            case 'duoi': {
                $cost = $amount * 1 * $buyRate;
                $win  = $amount * $payout;
                break;
            }
            case 'xiu_chu': {
                $coeff = ($region === 'bac') ? 4 : 1;
                $cost  = $amount * $coeff * $buyRate;
                $win   = $amount * $payout;
                break;
            }
            case 'xiu_chu_dau': {
                $coeff = ($region === 'bac') ? 3 : 1;
                $cost  = $amount * $coeff * $buyRate;
                $win   = $amount * $payout;
                break;
            }
            case 'xiu_chu_duoi': {
                $cost = $amount * 1 * $buyRate;
                $win  = $amount * $payout;
                break;
            }
            case 'xien': {
                $cost = $amount * $buyRate;
                $win  = $amount * $payout;
                break;
            }
            case 'da_thang': {
                $n     = count($numbers);
                $pairs = ($n >= 2) ? ($n * ($n - 1) / 2) : 1;
                if ($region === 'bac') {
                    $cost = $amount * $pairs * 27 * $buyRate;
                } else {
                    $cost = $amount * $pairs * 2 * 18 * $buyRate;
                }
                $win = $amount * $payout;
                break;
            }
            case 'da_xien': {
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
                $pairCount = $stationCount >= 2 ? (int) ($stationCount * ($stationCount - 1) / 2) : 0;
                $coeff = 4 * $pairCount * 18;
                $cost  = $amount * $coeff * $buyRate;
                $win   = $amount * $payout;
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
            $by[$label]['cost_xac']      += (int)$pricing['cost_xac'];
            $by[$label]['potential_win'] += (int)$pricing['potential_win'];
            $by[$label]['count']++;
            $totalCost += (int)$pricing['cost_xac'];
            $totalWin  += (int)$pricing['potential_win'];
        }

        // sắp xếp nhẹ theo label
        ksort($by);

        return [
            'breakdown' => array_values($by),
            'total'     => [
                'cost_xac_total'      => (int)$totalCost,
                'potential_win_total' => (int)$totalWin,
            ],
        ];
    }
}

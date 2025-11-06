<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\BettingTicket;
use App\Models\LotteryResult;
use App\Models\Customer;
use App\Services\BettingRateResolver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Service để tính toán thắng/thua cho phiếu cược dựa trên kết quả xổ số
 */
class BettingSettlementService
{
    protected BettingRateResolver $rateResolver;

    public function __construct(BettingRateResolver $rateResolver)
    {
        $this->rateResolver = $rateResolver;
    }

    /**
     * Xử lý quyết toán cho một phiếu cược cụ thể
     *
     * @param BettingTicket $ticket
     * @return array{settled: bool, result: string, win_amount: float, payout_amount: float, details: array}
     */
    public function settleTicket(BettingTicket $ticket): array
    {
        // Build rate resolver với customer_id và region của ticket
        $this->rateResolver->build($ticket->customer_id, $ticket->region);

        // Lấy kết quả xổ số tương ứng
        $results = $this->getLotteryResults($ticket);

        if (empty($results)) {
            return [
                'settled' => false,
                'result' => 'pending',
                'win_amount' => 0,
                'payout_amount' => 0,
                'details' => ['error' => 'Chưa có kết quả xổ số'],
            ];
        }

        $bettingData = $ticket->betting_data;
        if (!is_array($bettingData)) {
            return [
                'settled' => false,
                'result' => 'pending',
                'win_amount' => 0,
                'payout_amount' => 0,
                'details' => ['error' => 'Dữ liệu cược không hợp lệ'],
            ];
        }

        // Normalize betting_data format
        // Legacy format: {betting_type_code, numbers, meta, ...}
        // New format: [{type, numbers, amount, station, meta}, ...]
        $normalizedBets = $this->normalizeBettingData($bettingData, $ticket);

        // Tính toán thắng thua cho từng bet trong ticket
        $settlementDetails = [];
        $totalWinAmount = 0;
        $totalPayoutAmount = 0;
        $totalCostXac = 0;
        $hasWin = false;

        foreach ($normalizedBets as $bet) {
            $betResult = $this->settleSingleBet($bet, $results, $ticket);

            // Luôn cộng cost_xac vào tổng (customer trả tiền xác cho user)
            $totalCostXac += $betResult['cost_xac'] ?? 0;

            if ($betResult['is_win']) {
                $hasWin = true;
                $totalWinAmount += $betResult['win_amount'];
                $totalPayoutAmount += $betResult['payout_amount'];
            }

            $settlementDetails[] = $betResult;
        }

        // Cập nhật ticket
        $finalResult = $hasWin ? 'win' : 'lose';

        $ticket->update([
            'result' => $finalResult,
            'win_amount' => $totalWinAmount,
            'payout_amount' => $totalPayoutAmount, // Chỉ lưu tiền trả thắng
            'status' => 'completed',
        ]);
        
        // Lưu cost_xac vào betting_data để truy vấn sau
        $bettingData = $ticket->betting_data ?? [];
        $bettingData['total_cost_xac'] = $totalCostXac;
        $ticket->update(['betting_data' => $bettingData]);

        // Cập nhật thống kê khách hàng
        $this->updateCustomerStats($ticket->customer, $ticket->betting_date, $finalResult, $totalWinAmount, $totalPayoutAmount, $totalCostXac);

        return [
            'settled' => true,
            'result' => $finalResult,
            'win_amount' => $totalWinAmount,
            'payout_amount' => $totalPayoutAmount,
            'details' => $settlementDetails,
        ];
    }

    /**
     * Quyết toán cho một bet đơn lẻ trong phiếu cược
     *
     * @param array $bet
     * @param array $results Mảng các LotteryResult
     * @param BettingTicket $ticket
     * @return array
     */
    protected function settleSingleBet(array $bet, array $results, BettingTicket $ticket): array
    {
        $type = $bet['type'] ?? '';
        $numbers = $bet['numbers'] ?? [];
        $amount = (float)($bet['amount'] ?? 0);
        $meta = $bet['meta'] ?? [];

        $matchMethod = 'match' . $this->getMethodSuffix($type);

        if (!method_exists($this, $matchMethod)) {
            Log::warning("Settlement method not found for type: {$type}");
            return [
                'is_win' => false,
                'type' => $type,
                'numbers' => $numbers,
                'bet_amount' => $amount,
                'win_amount' => 0,
                'payout_amount' => 0,
                'error' => "Chưa hỗ trợ loại cược: {$type}",
            ];
        }

        return $this->$matchMethod($numbers, $results, $amount, $meta, $ticket);
    }

    /**
     * Match Bao Lô: Số trúng khi xuất hiện trong n số cuối của tất cả giải
     * - Bao lô 2 số: check 2 số cuối
     * - Bao lô 3 số: check 3 số cuối
     * - Bao lô 4 số: check 4 số cuối
     */
    protected function matchBaoLo(array $numbers, array $results, float $amount, array $meta, BettingTicket $ticket): array
    {
        $winCount = 0;
        $winDetails = [];
        $digits = (int)($meta['digits'] ?? 2);

        foreach ($numbers as $number) {
            $num = str_pad((string)$number, $digits, '0', STR_PAD_LEFT);

            foreach ($results as $result) {
                $hits = 0;
                
                // Check theo số digits
                if ($digits === 2) {
                    $hits = $result->countLo2(substr($num, -2));
                } elseif ($digits === 3) {
                    $hits = $result->countLo3(substr($num, -3));
                } elseif ($digits === 4) {
                    $hits = $result->countLo4(substr($num, -4));
                } else {
                    // Fallback: dùng 2 số cuối
                    $hits = $result->countLo2(substr($num, -2));
                }
                
                if ($hits > 0) {
                    $winCount += $hits;
                    $winDetails[] = [
                        'number' => $num,
                        'station' => $result->station,
                        'hits' => $hits,
                    ];
                }
            }
        }

        $isWin = $winCount > 0;

        // Lấy tỷ lệ từ customer rates
        $rate = $this->rateResolver->resolve('bao_lo', $digits);
        $buyRate = $rate[0] ?? 0.75; // Tỷ lệ thu
        $payout = $rate[1] ?? 80; // Tỷ lệ trả

        // Tính tiền xác theo công thức mới
        $isBac = $ticket->region === 'bac';
        if ($isBac) {
            // Miền Bắc
            $xacMultiplier = match($digits) {
                2 => 27,
                3 => 23,
                4 => 20,
                default => 27,
            };
        } else {
            // Miền Trung/Nam
            $xacMultiplier = match($digits) {
                2 => 18,
                3 => 17,
                4 => 16,
                default => 18,
            };
        }

        $costXac = $amount * $xacMultiplier * $buyRate;
        $winAmount = 0;
        $payoutAmount = 0;

        if ($isWin) {
            // Tính tiền thắng: tiền cược * số lô trúng
            // Ví dụ: cược 5n, về 2 lô → winAmount = 5n * 2 = 10n
            // Ví dụ: cược 5n, về 3 lô → winAmount = 5n * 3 = 15n
            $winAmount = $amount * $winCount;
            
            // Tính tiền trả: tiền thắng * tỷ lệ trả
            // Ví dụ: winAmount = 10n, payout = 80 → payoutAmount = 10n * 80 = 800n
            $payoutAmount = $winAmount * $payout;
        }

        return [
            'is_win' => $isWin,
            'type' => 'bao_lo',
            'numbers' => $numbers,
            'digits' => $digits,
            'bet_amount' => $amount,
            'cost_xac' => $costXac,
            'win_count' => $winCount,
            'win_amount' => $winAmount,
            'payout_amount' => $payoutAmount,
            'details' => $winDetails,
        ];
    }

    /**
     * Match Đầu: Trúng khi match 2 số đầu giải đặc biệt
     */
    protected function matchDau(array $numbers, array $results, float $amount, array $meta, BettingTicket $ticket): array
    {
        $isWin = false;
        $winDetails = [];

        foreach ($numbers as $number) {
            $num2 = str_pad((string)$number, 2, '0', STR_PAD_LEFT);

            foreach ($results as $result) {
                if ($result->matchDau($num2)) {
                    $isWin = true;
                    $winDetails[] = [
                        'number' => $num2,
                        'station' => $result->station,
                        'matched' => $result->db_first2,
                    ];
                }
            }
        }

        $rate = $this->rateResolver->resolve('dau', 2);

        $buyRate = $rate[0] ?? 0.75;
        $payout = $rate[1] ?? 85;

        // Tính tiền xác
        $isBac = $ticket->region === 'bac';
        if ($isBac) {
            // Miền Bắc: Đầu * 4 (được tính trong đầu đuôi)
            $costXac = $amount * 4 * $buyRate;
        } else {
            // Miền Trung/Nam: chỉ tính tiền đầu
            $costXac = $amount * $buyRate;
        }

        $winAmount = 0;
        $payoutAmount = 0;

        if ($isWin) {
            $winAmount = $amount * count($winDetails);
            $payoutAmount = $winAmount * $payout;
        }

        return [
            'is_win' => $isWin,
            'type' => 'dau',
            'numbers' => $numbers,
            'bet_amount' => $amount,
            'cost_xac' => $costXac,
            'win_amount' => $winAmount,
            'payout_amount' => $payoutAmount,
            'details' => $winDetails,
        ];
    }

    /**
     * Match Đuôi: Trúng khi match 2 số cuối giải đặc biệt
     */
    protected function matchDuoi(array $numbers, array $results, float $amount, array $meta, BettingTicket $ticket): array
    {
        $isWin = false;
        $winDetails = [];

        foreach ($numbers as $number) {
            $num2 = str_pad((string)$number, 2, '0', STR_PAD_LEFT);

            foreach ($results as $result) {
                if ($result->matchDuoi($num2)) {
                    $isWin = true;
                    $winDetails[] = [
                        'number' => $num2,
                        'station' => $result->station,
                        'matched' => $result->db_last2,
                    ];
                }
            }
        }

        $rate = $this->rateResolver->resolve('duoi', 2);

        $buyRate = $rate[0] ?? 0.75;
        $payout = $rate[1] ?? 85;

        // Tính tiền xác - Đuôi không nhân thêm
        $costXac = $amount * $buyRate;

        $winAmount = 0;
        $payoutAmount = 0;

        if ($isWin) {
            $winAmount = $amount * count($winDetails);
            $payoutAmount = $winAmount * $payout;
        }

        return [
            'is_win' => $isWin,
            'type' => 'duoi',
            'numbers' => $numbers,
            'bet_amount' => $amount,
            'cost_xac' => $costXac,
            'win_amount' => $winAmount,
            'payout_amount' => $payoutAmount,
            'details' => $winDetails,
        ];
    }

    /**
     * Match Đầu Đuôi: Kết hợp cả đầu và đuôi
     */
    protected function matchDauDuoi(array $numbers, array $results, float $amount, array $meta, BettingTicket $ticket): array
    {
        // Đầu đuôi tách thành 2 bet riêng
        $dauResult = $this->matchDau($numbers, $results, $amount, $meta, $ticket);
        $duoiResult = $this->matchDuoi($numbers, $results, $amount, $meta, $ticket);

        $isWin = $dauResult['is_win'] || $duoiResult['is_win'];
        $winAmount = $dauResult['win_amount'] + $duoiResult['win_amount'];
        $payoutAmount = $dauResult['payout_amount'] + $duoiResult['payout_amount'];

        // Tính tiền xác theo công thức: (đầu + đuôi) * buy_rate
        $rate = $this->rateResolver->resolve('dau_duoi', 2);
        $buyRate = $rate[0] ?? 0.75;

        $isBac = $ticket->region === 'bac';
        if ($isBac) {
            // MB: (đầu*4 + đuôi) * buy_rate
            $costXac = ($amount * 4 + $amount) * $buyRate;
        } else {
            // MT/MN: (đầu + đuôi) * buy_rate
            $costXac = ($amount + $amount) * $buyRate;
        }

        return [
            'is_win' => $isWin,
            'type' => 'dau_duoi',
            'numbers' => $numbers,
            'bet_amount' => $amount * 2, // Đánh cả 2
            'cost_xac' => $costXac,
            'win_amount' => $winAmount,
            'payout_amount' => $payoutAmount,
            'details' => [
                'dau' => $dauResult['details'],
                'duoi' => $duoiResult['details'],
            ],
        ];
    }

    /**
     * Match Xỉu Chủ: Match 3 số cuối giải đặc biệt (MB: GĐB+G6, MT/MN: GĐB+G7)
     */
    protected function matchXiuChu(array $numbers, array $results, float $amount, array $meta, BettingTicket $ticket): array
    {
        $isWin = false;
        $winDetails = [];
        $isBac = $ticket->region === 'bac';

        foreach ($numbers as $number) {
            $num3 = str_pad((string)$number, 3, '0', STR_PAD_LEFT);

            foreach ($results as $result) {
                // Chỉ check GĐB và G6 (MB) hoặc G7 (MT/MN)
                if ($result->matchXiuChuLast3($num3, false)) {
                    $isWin = true;
                    $winDetails[] = [
                        'number' => $num3,
                        'station' => $result->station,
                        'matched' => $result->db_last3,
                        'prize' => 'GDB',
                    ];
                }
                // TODO: Cần check thêm G6 (MB) hoặc G7 (MT/MN) nếu có trong model
            }
        }

        $rate = $this->rateResolver->resolve('xiu_chu', 3);

        $buyRate = $rate[0] ?? 0.75;
        $payout = $rate[1] ?? 500;

        // Tính tiền xác
        if ($isBac) {
            // MB: tiền cược * 4 * buy_rate
            $costXac = $amount * 4 * $buyRate;
        } else {
            // MT/MN: tiền cược * buy_rate (đơn giản)
            $costXac = $amount * $buyRate;
        }

        $winAmount = 0;
        $payoutAmount = 0;

        if ($isWin) {
            $winAmount = $amount * count($winDetails);
            $payoutAmount = $winAmount * $payout;
        }

        return [
            'is_win' => $isWin,
            'type' => 'xiu_chu',
            'numbers' => $numbers,
            'bet_amount' => $amount,
            'cost_xac' => $costXac,
            'win_amount' => $winAmount,
            'payout_amount' => $payoutAmount,
            'details' => $winDetails,
        ];
    }

    /**
     * Match Xỉu Chủ Đầu: Match 2 số đầu của 3 số cuối GĐB (MB: G6, MT/MN: G7)
     */
    protected function matchXiuChuDau(array $numbers, array $results, float $amount, array $meta, BettingTicket $ticket): array
    {
        $isWin = false;
        $winDetails = [];
        $isBac = $ticket->region === 'bac';

        foreach ($numbers as $number) {
            $num2 = str_pad((string)$number, 2, '0', STR_PAD_LEFT);

            foreach ($results as $result) {
                // TODO: Check G6 cho MB hoặc G7 cho MT/MN
                if ($result->db_last3) {
                    $firstTwo = substr($result->db_last3, 0, 2);
                    if ($firstTwo === $num2) {
                        $isWin = true;
                        $winDetails[] = [
                            'number' => $num2,
                            'station' => $result->station,
                            'matched' => $firstTwo,
                            'full' => $result->db_last3,
                            'prize' => $isBac ? 'G6' : 'G7',
                        ];
                    }
                }
            }
        }

        // MN/MT: dùng rate từ xiu_chu, MB: dùng rate từ xiu_chu_dau
        if ($isBac) {
            $rate = $this->rateResolver->resolve('xiu_chu_dau', 2);
        } else {
            // MN/MT: lấy rate từ xiu_chu
            $rate = $this->rateResolver->resolve('xiu_chu', 3);
        }

        $buyRate = $rate[0] ?? 0.75;
        $payout = $rate[1] ?? 90;

        // Tính tiền xác
        if ($isBac) {
            // MB: đầu * 3 * buy_rate
            $costXac = $amount * 3 * $buyRate;
        } else {
            // MT/MN: đầu * buy_rate
            $costXac = $amount * $buyRate;
        }

        $winAmount = 0;
        $payoutAmount = 0;

        if ($isWin) {
            $winAmount = $amount * count($winDetails);
            $payoutAmount = $winAmount * $payout;
        }

        return [
            'is_win' => $isWin,
            'type' => 'xiu_chu_dau',
            'numbers' => $numbers,
            'bet_amount' => $amount,
            'cost_xac' => $costXac,
            'win_amount' => $winAmount,
            'payout_amount' => $payoutAmount,
            'details' => $winDetails,
        ];
    }

    /**
     * Match Xỉu Chủ Đuôi: Match 2 số cuối của 3 số cuối GĐB
     */
    protected function matchXiuChuDuoi(array $numbers, array $results, float $amount, array $meta, BettingTicket $ticket): array
    {
        $isWin = false;
        $winDetails = [];

        foreach ($numbers as $number) {
            $num2 = str_pad((string)$number, 2, '0', STR_PAD_LEFT);

            foreach ($results as $result) {
                if ($result->db_last3) {
                    $lastTwo = substr($result->db_last3, -2);
                    if ($lastTwo === $num2) {
                        $isWin = true;
                        $winDetails[] = [
                            'number' => $num2,
                            'station' => $result->station,
                            'matched' => $lastTwo,
                            'full' => $result->db_last3,
                            'prize' => 'GDB',
                        ];
                    }
                }
            }
        }

        // MN/MT: dùng rate từ xiu_chu, MB: dùng rate từ xiu_chu_duoi
        $isBac = $ticket->region === 'bac';
        if ($isBac) {
            $rate = $this->rateResolver->resolve('xiu_chu_duoi', 2);
        } else {
            // MN/MT: lấy rate từ xiu_chu
            $rate = $this->rateResolver->resolve('xiu_chu', 3);
        }

        $buyRate = $rate[0] ?? 0.75;
        $payout = $rate[1] ?? 90;

        // Tính tiền xác - Đuôi không nhân thêm
        $costXac = $amount * $buyRate;

        $winAmount = 0;
        $payoutAmount = 0;

        if ($isWin) {
            $winAmount = $amount * count($winDetails);
            $payoutAmount = $winAmount * $payout;
        }

        return [
            'is_win' => $isWin,
            'type' => 'xiu_chu_duoi',
            'numbers' => $numbers,
            'bet_amount' => $amount,
            'cost_xac' => $costXac,
            'win_amount' => $winAmount,
            'payout_amount' => $payoutAmount,
            'details' => $winDetails,
        ];
    }

    /**
     * Match Xiên (Miền Bắc only): Xiên 2/3/4 số
     */
    protected function matchXien(array $numbers, array $results, float $amount, array $meta, BettingTicket $ticket): array
    {
        $xienSize = (int)($meta['xien_size'] ?? 2);

        if ($ticket->region !== 'bac') {
            return [
                'is_win' => false,
                'type' => 'xien',
                'numbers' => $numbers,
                'bet_amount' => $amount,
                'cost_xac' => 0,
                'win_amount' => 0,
                'payout_amount' => 0,
                'error' => 'Xiên chỉ áp dụng cho Miền Bắc',
            ];
        }

        // Xiên yêu cầu tất cả các số phải trúng lô
        $matchedNumbers = [];

        foreach ($numbers as $number) {
            $num2 = str_pad((string)$number, 2, '0', STR_PAD_LEFT);

            foreach ($results as $result) {
                if ($result->countLo2($num2) > 0) {
                    $matchedNumbers[] = $num2;
                    break; // Đã trúng ở 1 đài là đủ
                }
            }
        }

        // Phải trúng đủ số lượng số theo xien_size
        $isWin = count($matchedNumbers) >= $xienSize;

        $rate = $this->rateResolver->resolve('xien', null, $xienSize);

        $buyRate = $rate[0] ?? 0.75;
        $payout = $rate[1] ?? match($xienSize) {
            2 => 15,
            3 => 550,
            4 => 3500,
            default => 15,
        };

        // Tính tiền xác: tiền cược * buy_rate (đơn giản)
        $costXac = $amount * $buyRate;

        $winAmount = 0;
        $payoutAmount = 0;

        if ($isWin) {
            $winAmount = $amount;
            $payoutAmount = $winAmount * $payout;
        }

        return [
            'is_win' => $isWin,
            'type' => 'xien',
            'xien_size' => $xienSize,
            'numbers' => $numbers,
            'matched_numbers' => $matchedNumbers,
            'bet_amount' => $amount,
            'cost_xac' => $costXac,
            'win_amount' => $winAmount,
            'payout_amount' => $payoutAmount,
        ];
    }

    /**
     * Match Đá Thẳng: Đánh cặp 2 số cùng đài
     */
    protected function matchDaThang(array $numbers, array $results, float $amount, array $meta, BettingTicket $ticket): array
    {
        $isWin = false;
        $winDetails = [];
        $isBac = $ticket->region === 'bac';

        // Sinh các cặp từ danh sách số
        $pairs = [];
        $numCount = count($numbers);
        for ($i = 0; $i < $numCount; $i++) {
            for ($j = $i + 1; $j < $numCount; $j++) {
                $pairs[] = [$numbers[$i], $numbers[$j]];
            }
        }

        // Check từng cặp
        foreach ($pairs as $pair) {
            $num1 = str_pad((string)$pair[0], 2, '0', STR_PAD_LEFT);
            $num2 = str_pad((string)$pair[1], 2, '0', STR_PAD_LEFT);

            foreach ($results as $result) {
                $hit1 = $result->countLo2($num1) > 0;
                $hit2 = $result->countLo2($num2) > 0;

                if ($hit1 && $hit2) {
                    $isWin = true;
                    $winDetails[] = [
                        'pair' => [$num1, $num2],
                        'station' => $result->station,
                    ];
                }
            }
        }

        $rate = $this->rateResolver->resolve('da_thang', 2);

        $buyRate = $rate[0] ?? 0.75;
        $payout = $rate[1] ?? 70;

        // Tính tiền xác
        $pairCount = count($pairs);
        $winPairCount = count($winDetails); // Số cặp ăn được
        
        if ($isBac) {
            // MB: tiền cược * số cặp * 27 * buy_rate
            $costXac = $amount * $pairCount * 27 * $buyRate;
        } else {
            // MT/MN: tiền cược * 2 * 18 * buy_rate (đá thẳng)
            $costXac = $amount * 2 * 18 * $buyRate;
        }

        $winAmount = 0;
        $payoutAmount = 0;

        if ($isWin) {
            // Tiền thắng: tiền cược * số cặp ăn được * payout
            $winAmount = $amount * $winPairCount;
            $payoutAmount = $winAmount * $payout;
        }

        return [
            'is_win' => $isWin,
            'type' => 'da_thang',
            'numbers' => $numbers,
            'pairs' => $pairs,
            'bet_amount' => $amount,
            'cost_xac' => $costXac,
            'win_amount' => $winAmount,
            'payout_amount' => $payoutAmount,
            'details' => $winDetails,
        ];
    }

    /**
     * Match Đá Xiên/Chéo: Đánh cặp 2 số khác đài
     */
    protected function matchDaXien(array $numbers, array $results, float $amount, array $meta, BettingTicket $ticket): array
    {
        $isWin = false;
        $winDetails = [];
        
        // Tính số đài: ưu tiên từ meta (giống BetPricingService), fallback về count($results)
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
        // Fallback: dùng số lượng results nếu không có trong meta
        if (!$stationCount) {
            $stationCount = count($results);
        }

        // Sinh các cặp từ danh sách số
        $pairs = [];
        $numCount = count($numbers);
        for ($i = 0; $i < $numCount; $i++) {
            for ($j = $i + 1; $j < $numCount; $j++) {
                $pairs[] = [$numbers[$i], $numbers[$j]];
            }
        }

        // Check từng cặp xuyên các đài
        foreach ($pairs as $pair) {
            $num1 = str_pad((string)$pair[0], 2, '0', STR_PAD_LEFT);
            $num2 = str_pad((string)$pair[1], 2, '0', STR_PAD_LEFT);
            
            // Đá xiên CHỈ thắng khi: 2 đài KHÁC NHAU, mỗi đài 1 số
            // LƯU Ý: KHÔNG tính cùng đài (1 đài có cả 2 số = THUA)
            
            $numStations = count($results);
            for ($i = 0; $i < $numStations; $i++) {
                for ($j = $i + 1; $j < $numStations; $j++) {
                    $result1 = $results[$i];
                    $result2 = $results[$j];

                    $hit1_in_1 = $result1->countLo2($num1) > 0;
                    $hit2_in_2 = $result2->countLo2($num2) > 0;
                    $hit2_in_1 = $result1->countLo2($num2) > 0;
                    $hit1_in_2 = $result2->countLo2($num1) > 0;

                    // Cross: Station i có num1 và Station j có num2
                    if ($hit1_in_1 && $hit2_in_2) {
                        $isWin = true;
                        $winDetails[] = [
                            'pair' => [$num1, $num2],
                            'stations' => [$result1->station, $result2->station],
                            'type' => 'cross_station',
                        ];
                        break 2; // Cặp đã thắng, thoát khỏi 2 vòng lặp
                    }
                    
                    // Cross: Station i có num2 và Station j có num1 (đảo ngược)
                    if ($hit2_in_1 && $hit1_in_2) {
                        $isWin = true;
                        $winDetails[] = [
                            'pair' => [$num1, $num2],
                            'stations' => [$result1->station, $result2->station],
                            'type' => 'cross_station',
                        ];
                        break 2; // Cặp đã thắng, thoát khỏi 2 vòng lặp
                    }
                }
            }
        }

        $rate = $this->rateResolver->resolve('da_xien', null, null, 2);

        $buyRate = $rate[0] ?? 0.75;
        $payout = $rate[1] ?? 70;

        // Tính tiền xác theo số đài (chỉ áp dụng cho MN/MT)
        $isBac = $ticket->region === 'bac';
        if ($isBac) {
            // MB không có đá chéo, fallback
            $costXac = $amount * $buyRate;
        } else {
            // MN/MT: Da cheo 2 dai: tiền cược * 4 * 18 * buy_rate
            // Da cheo 3 dai: tiền cược * 4 * 3 * 18 * buy_rate
            // Da cheo 4 dai: tiền cược * 4 * 6 * 18 * buy_rate
            $multiplier = match($stationCount) {
                2 => 4,
                3 => 4 * 3,
                4 => 4 * 6,
                default => 4,
            };
            $costXac = $amount * $multiplier * 18 * $buyRate;
        }
        
        $winPairCount = count($winDetails); // Số cặp ăn được

        $winAmount = 0;
        $payoutAmount = 0;

        if ($isWin) {
            // Tiền thắng: tiền cược * số cặp ăn được * payout
            $winAmount = $amount * $winPairCount;
            $payoutAmount = $winAmount * $payout;
        }

        return [
            'is_win' => $isWin,
            'type' => 'da_xien',
            'numbers' => $numbers,
            'pairs' => $pairs,
            'station_count' => $stationCount,
            'bet_amount' => $amount,
            'cost_xac' => $costXac,
            'win_amount' => $winAmount,
            'payout_amount' => $payoutAmount,
            'details' => $winDetails,
        ];
    }

    /**
     * Lấy kết quả xổ số cho một phiếu cược
     *
     * @param BettingTicket $ticket
     * @return array Array of LotteryResult models
     */
    protected function getLotteryResults(BettingTicket $ticket): array
    {
        $query = LotteryResult::where('draw_date', $ticket->betting_date)
            ->where('region', $ticket->region);

        // Parse station (có thể có dạng "tp.hcm + dong thap")
        $stations = $this->parseStations($ticket->station);

        if (!empty($stations)) {
            // Normalize station names (loại bỏ dấu) để match với LotteryResult
            $normalizedStations = array_map([$this, 'normalizeStationName'], $stations);
            
            // Lấy tất cả kết quả có region và date khớp
            $allResults = $query->get();
            
            // Filter bằng cách so sánh normalized station name
            $matchedResults = [];
            foreach ($allResults as $result) {
                $normalizedResultStation = $this->normalizeStationName($result->station);
                // Check exact match hoặc match với bất kỳ station nào trong list
                foreach ($normalizedStations as $normalizedStation) {
                    if ($normalizedStation === $normalizedResultStation) {
                        $matchedResults[] = $result;
                        break; // Đã match, không cần check tiếp
                    }
                }
            }
            
            return $matchedResults;
        }

        return $query->get()->all();
    }

    /**
     * Parse station string thành array
     *
     * @param string|null $stationStr "tp.hcm + dong thap" hoặc "tp.hcm"
     * @return array
     */
    protected function parseStations(?string $stationStr): array
    {
        if (empty($stationStr)) {
            return [];
        }

        // Tách theo dấu +
        $stations = explode('+', $stationStr);

        return array_map('trim', $stations);
    }

    /**
     * Normalize station name: loại bỏ dấu và chuyển về lowercase
     * Để so sánh "dong thap" với "đồng tháp"
     *
     * @param string $stationName
     * @return string
     */
    protected function normalizeStationName(string $stationName): string
    {
        $stationName = mb_strtolower(trim($stationName), 'UTF-8');
        
        // Loại bỏ dấu tiếng Việt
        $accents = [
            'à' => 'a', 'á' => 'a', 'ạ' => 'a', 'ả' => 'a', 'ã' => 'a',
            'â' => 'a', 'ầ' => 'a', 'ấ' => 'a', 'ậ' => 'a', 'ẩ' => 'a', 'ẫ' => 'a',
            'ă' => 'a', 'ằ' => 'a', 'ắ' => 'a', 'ặ' => 'a', 'ẳ' => 'a', 'ẵ' => 'a',
            'è' => 'e', 'é' => 'e', 'ẹ' => 'e', 'ẻ' => 'e', 'ẽ' => 'e',
            'ê' => 'e', 'ề' => 'e', 'ế' => 'e', 'ệ' => 'e', 'ể' => 'e', 'ễ' => 'e',
            'ì' => 'i', 'í' => 'i', 'ị' => 'i', 'ỉ' => 'i', 'ĩ' => 'i',
            'ò' => 'o', 'ó' => 'o', 'ọ' => 'o', 'ỏ' => 'o', 'õ' => 'o',
            'ô' => 'o', 'ồ' => 'o', 'ố' => 'o', 'ộ' => 'o', 'ổ' => 'o', 'ỗ' => 'o',
            'ơ' => 'o', 'ờ' => 'o', 'ớ' => 'o', 'ợ' => 'o', 'ở' => 'o', 'ỡ' => 'o',
            'ù' => 'u', 'ú' => 'u', 'ụ' => 'u', 'ủ' => 'u', 'ũ' => 'u',
            'ư' => 'u', 'ừ' => 'u', 'ứ' => 'u', 'ự' => 'u', 'ử' => 'u', 'ữ' => 'u',
            'ỳ' => 'y', 'ý' => 'y', 'ỵ' => 'y', 'ỷ' => 'y', 'ỹ' => 'y',
            'đ' => 'd',
            'À' => 'a', 'Á' => 'a', 'Ạ' => 'a', 'Ả' => 'a', 'Ã' => 'a',
            'Â' => 'a', 'Ầ' => 'a', 'Ấ' => 'a', 'Ậ' => 'a', 'Ẩ' => 'a', 'Ẫ' => 'a',
            'Ă' => 'a', 'Ằ' => 'a', 'Ắ' => 'a', 'Ặ' => 'a', 'Ẳ' => 'a', 'Ẵ' => 'a',
            'È' => 'e', 'É' => 'e', 'Ẹ' => 'e', 'Ẻ' => 'e', 'Ẽ' => 'e',
            'Ê' => 'e', 'Ề' => 'e', 'Ế' => 'e', 'Ệ' => 'e', 'Ể' => 'e', 'Ễ' => 'e',
            'Ì' => 'i', 'Í' => 'i', 'Ị' => 'i', 'Ỉ' => 'i', 'Ĩ' => 'i',
            'Ò' => 'o', 'Ó' => 'o', 'Ọ' => 'o', 'Ỏ' => 'o', 'Õ' => 'o',
            'Ô' => 'o', 'Ồ' => 'o', 'Ố' => 'o', 'Ộ' => 'o', 'Ổ' => 'o', 'Ỗ' => 'o',
            'Ơ' => 'o', 'Ờ' => 'o', 'Ớ' => 'o', 'Ợ' => 'o', 'Ở' => 'o', 'Ỡ' => 'o',
            'Ù' => 'u', 'Ú' => 'u', 'Ụ' => 'u', 'Ủ' => 'u', 'Ũ' => 'u',
            'Ư' => 'u', 'Ừ' => 'u', 'Ứ' => 'u', 'Ự' => 'u', 'Ử' => 'u', 'Ữ' => 'u',
            'Ỳ' => 'y', 'Ý' => 'y', 'Ỵ' => 'y', 'Ỷ' => 'y', 'Ỹ' => 'y',
            'Đ' => 'd',
        ];
        
        return strtr($stationName, $accents);
    }

    /**
     * Cập nhật thống kê cho khách hàng
     */
    protected function updateCustomerStats(Customer $customer, $date, string $result, float $winAmount, float $payoutAmount, float $totalCostXac): void
    {
        $carbon = Carbon::parse($date);

        // Cập nhật theo ngày
        if ($result === 'win') {
            // Thắng: khách trả payout cho user, nên cộng vào win_amount
            $customer->increment('daily_win_amount', $payoutAmount);
            $customer->increment('total_win_amount', $payoutAmount);
        }
        // Thua hoặc thắng: customer luôn trả cost_xac
        $customer->increment('daily_lose_amount', $totalCostXac);
        $customer->increment('total_lose_amount', $totalCostXac);

        // Cập nhật theo tháng
        // TODO: Cần logic phức tạp hơn để track theo tháng/năm
    }

    /**
     * Normalize betting_data từ legacy format sang new format
     * 
     * Legacy format: {betting_type_code, numbers, meta, ...}
     * New format: [{type, numbers, amount, station, meta}, ...]
     * 
     * @param array $bettingData
     * @param BettingTicket $ticket
     * @return array
     */
    protected function normalizeBettingData(array $bettingData, BettingTicket $ticket): array
    {
        // Check if already new format (array of bets)
        if (!empty($bettingData) && isset($bettingData[0]) && is_array($bettingData[0]) && isset($bettingData[0]['type'])) {
            // Already new format
            return $bettingData;
        }

        // Legacy format: single bet object
        $type = $bettingData['betting_type_code'] ?? '';
        $numbers = $bettingData['numbers'] ?? [];
        $amount = (float)($ticket->bet_amount ?? 0);
        $meta = $bettingData['meta'] ?? [];

        // Use station from ticket
        $station = $ticket->station;

        return [[
            'type' => $type,
            'numbers' => $numbers,
            'amount' => $amount,
            'station' => $station,
            'meta' => $meta,
        ]];
    }

    /**
     * Chuyển đổi type thành method suffix
     */
    protected function getMethodSuffix(string $type): string
    {
        // Convert "bao_lo" -> "BaoLo", "xiu_chu_dau" -> "XiuChuDau"
        return str_replace('_', '', ucwords($type, '_'));
    }

    /**
     * Quyết toán hàng loạt cho một ngày cụ thể
     *
     * @param string $date YYYY-MM-DD
     * @param string|null $region Miền cụ thể hoặc null cho tất cả
     * @return array
     */
    public function settleBatchByDate(string $date, ?string $region = null): array
    {
        $query = BettingTicket::where('betting_date', $date)
            ->where('result', 'pending');

        if ($region) {
            $query->where('region', $region);
        }

        $tickets = $query->get();
        $settled = 0;
        $failed = 0;
        $results = [];

        foreach ($tickets as $ticket) {
            try {
                $result = $this->settleTicket($ticket);
                if ($result['settled']) {
                    $settled++;
                } else {
                    $failed++;
                }
                $results[] = [
                    'ticket_id' => $ticket->id,
                    'success' => $result['settled'],
                    'result' => $result,
                ];
            } catch (\Exception $e) {
                $failed++;
                $results[] = [
                    'ticket_id' => $ticket->id,
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
                Log::error("Settlement failed for ticket {$ticket->id}: " . $e->getMessage());
            }
        }

        return [
            'total' => $tickets->count(),
            'settled' => $settled,
            'failed' => $failed,
            'results' => $results,
        ];
    }
}

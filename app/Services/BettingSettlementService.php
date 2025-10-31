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

        // Tính toán thắng thua cho từng bet trong ticket
        $settlementDetails = [];
        $totalWinAmount = 0;
        $totalPayoutAmount = 0;
        $hasWin = false;

        foreach ($bettingData as $betIndex => $bet) {
            $betResult = $this->settleSingleBet($bet, $results, $ticket);

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
            'payout_amount' => $totalPayoutAmount,
            'status' => 'completed',
        ]);

        // Cập nhật thống kê khách hàng
        $this->updateCustomerStats($ticket->customer, $ticket->betting_date, $finalResult, $totalWinAmount, $totalPayoutAmount);

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
     * Match Bao Lô: Số trúng khi xuất hiện trong 2 số cuối của tất cả giải
     */
    protected function matchBaoLo(array $numbers, array $results, float $amount, array $meta, BettingTicket $ticket): array
    {
        $winCount = 0;
        $winDetails = [];
        $digits = (int)($meta['digits'] ?? 2);

        foreach ($numbers as $number) {
            $num = str_pad((string)$number, $digits, '0', STR_PAD_LEFT);

            foreach ($results as $result) {
                $hits = $result->countLo2(substr($num, -2)); // Luôn check 2 số cuối
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
        $rate = $this->rateResolver->resolve(
            $ticket->customer_id,
            'bao_lo',
            $ticket->region,
            $digits
        );

        $buyRate = $rate['buy_rate'] ?? 0.75; // Tỷ lệ thu
        $payout = $rate['win_rate'] ?? 80; // Tỷ lệ trả

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
            $winAmount = $amount * $winCount;
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

        $rate = $this->rateResolver->resolve(
            $ticket->customer_id,
            'dau',
            $ticket->region,
            2
        );

        $buyRate = $rate['buy_rate'] ?? 0.75;
        $payout = $rate['win_rate'] ?? 85;

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

        $rate = $this->rateResolver->resolve(
            $ticket->customer_id,
            'duoi',
            $ticket->region,
            2
        );

        $buyRate = $rate['buy_rate'] ?? 0.75;
        $payout = $rate['win_rate'] ?? 85;

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
        $rate = $this->rateResolver->resolve(
            $ticket->customer_id,
            'dau_duoi',
            $ticket->region,
            2
        );
        $buyRate = $rate['buy_rate'] ?? 0.75;

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

        $rate = $this->rateResolver->resolve(
            $ticket->customer_id,
            'xiu_chu',
            $ticket->region,
            3
        );

        $buyRate = $rate['buy_rate'] ?? 0.75;
        $payout = $rate['win_rate'] ?? 500;

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

        $rate = $this->rateResolver->resolve(
            $ticket->customer_id,
            'xiu_chu_dau',
            $ticket->region,
            2
        );

        $buyRate = $rate['buy_rate'] ?? 0.75;
        $payout = $rate['win_rate'] ?? 90;

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

        $rate = $this->rateResolver->resolve(
            $ticket->customer_id,
            'xiu_chu_duoi',
            $ticket->region,
            2
        );

        $buyRate = $rate['buy_rate'] ?? 0.75;
        $payout = $rate['win_rate'] ?? 90;

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

        $rate = $this->rateResolver->resolve(
            $ticket->customer_id,
            'xien',
            $ticket->region,
            $xienSize
        );

        $buyRate = $rate['buy_rate'] ?? 0.75;
        $payout = match($xienSize) {
            2 => $rate['win_rate'] ?? 15,
            3 => $rate['win_rate'] ?? 550,
            4 => $rate['win_rate'] ?? 3500,
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

        $rate = $this->rateResolver->resolve(
            $ticket->customer_id,
            'da_thang',
            $ticket->region,
            2
        );

        $buyRate = $rate['buy_rate'] ?? 0.75;
        $payout = $rate['win_rate'] ?? 70;

        // Tính tiền xác
        $pairCount = count($pairs);
        if ($isBac) {
            // MB: số cặp * 27 * buy_rate
            $costXac = $amount * $pairCount * 27 * $buyRate;
        } else {
            // MT/MN: 2 * 18 * buy_rate
            $costXac = $amount * 2 * 18 * $buyRate;
        }

        $winAmount = 0;
        $payoutAmount = 0;

        if ($isWin) {
            $winAmount = $amount * count($winDetails);
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
        $stationCount = count($results);

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

            // Cần check cả 2 số ở 2 đài khác nhau
            foreach ($results as $result1) {
                foreach ($results as $result2) {
                    if ($result1->station === $result2->station) continue;

                    $hit1 = $result1->countLo2($num1) > 0;
                    $hit2 = $result2->countLo2($num2) > 0;

                    if ($hit1 && $hit2) {
                        $isWin = true;
                        $winDetails[] = [
                            'pair' => [$num1, $num2],
                            'stations' => [$result1->station, $result2->station],
                        ];
                    }
                }
            }
        }

        $rate = $this->rateResolver->resolve(
            $ticket->customer_id,
            'da_xien',
            $ticket->region,
            2
        );

        $buyRate = $rate['buy_rate'] ?? 0.75;
        $payout = $rate['win_rate'] ?? 70;

        // Tính tiền xác theo số đài
        // 2 đài: 4 * 18, 3 đài: 4 * 3 * 18, 4 đài: 4 * 6 * 18
        $multiplier = match($stationCount) {
            2 => 4,
            3 => 4 * 3,
            4 => 4 * 6,
            default => 4,
        };

        $costXac = $amount * $multiplier * 18 * $buyRate;

        $winAmount = 0;
        $payoutAmount = 0;

        if ($isWin) {
            $winAmount = $amount * count($winDetails);
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
            $query->whereIn('station', $stations);
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
     * Cập nhật thống kê cho khách hàng
     */
    protected function updateCustomerStats(Customer $customer, $date, string $result, float $winAmount, float $payoutAmount): void
    {
        $carbon = Carbon::parse($date);

        // Cập nhật theo ngày
        if ($result === 'win') {
            $customer->increment('daily_win', $payoutAmount);
        } else {
            $customer->increment('daily_loss', $winAmount);
        }

        // Cập nhật theo tháng
        // TODO: Cần logic phức tạp hơn để track theo tháng/năm
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

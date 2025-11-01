<?php

// Test single vs double d-token logic
function testParsing($message) {
    $tokens = preg_split('/\s+/', strtolower(str_replace([',', '.'], ' ', $message)));
    $tokens = array_filter($tokens);

    $numbers = [];
    $pair_d_dau = [];
    $bets = [];
    $station = 'tay ninh';
    $events = [];
    $last_token_type = null;

    foreach ($tokens as $tok) {
        // Station
        if (in_array($tok, ['t', 'tninh', 'tn'])) {
            $station = 'tay ninh';
            $events[] = "Station: $station";
            $last_token_type = 'station';
            continue;
        }

        // Number
        if (preg_match('/^\d{2,4}$/', $tok)) {
            // QUAN TRỌNG: Nếu token trước là 'd' và đã có pair_d_dau
            // → FLUSH group cũ trước khi bắt đầu số mới
            if ($last_token_type === 'd' && count($pair_d_dau) > 0 && count($numbers) > 0) {
                $dauAmt = $pair_d_dau[0] ?? 0;
                $duoiAmt = $pair_d_dau[1] ?? null;

                $events[] = "*** FLUSH (d→number): " . count($numbers) . " numbers with dau={$dauAmt}, duoi=" . ($duoiAmt ?? 'null');

                foreach ($numbers as $n) {
                    if ($dauAmt > 0) {
                        $bets[] = ['number' => $n, 'type' => 'dau', 'amount' => $dauAmt, 'station' => $station];
                    }
                    if ($duoiAmt !== null && $duoiAmt > 0) {
                        $bets[] = ['number' => $n, 'type' => 'duoi', 'amount' => $duoiAmt, 'station' => $station];
                    }
                }

                $numbers = [];
                $pair_d_dau = [];
                $events[] = "*** RESET";
            }

            $numbers[] = $tok;
            $events[] = "Number: $tok";
            $last_token_type = 'number';
            continue;
        }

        // d+amount (d35n, d40n, etc.)
        if (preg_match('/^d(\d+)([nk])$/', $tok, $m)) {
            $amt = (int)$m[1] * 1000;
            $pair_d_dau[] = $amt;
            $events[] = "D-token: {$tok} = {$amt} (total d-tokens: " . count($pair_d_dau) . ")";
            $last_token_type = 'd';
        }
    }

    // Final flush if any remaining
    if (count($numbers) > 0 && count($pair_d_dau) > 0) {
        $dauAmt = $pair_d_dau[0] ?? 0;
        $duoiAmt = $pair_d_dau[1] ?? null;
        $events[] = "*** FINAL FLUSH: " . count($numbers) . " numbers with dau={$dauAmt}, duoi=" . ($duoiAmt ?? 'null');
        foreach ($numbers as $n) {
            if ($dauAmt > 0) {
                $bets[] = ['number' => $n, 'type' => 'dau', 'amount' => $dauAmt, 'station' => $station];
            }
            if ($duoiAmt !== null && $duoiAmt > 0) {
                $bets[] = ['number' => $n, 'type' => 'duoi', 'amount' => $duoiAmt, 'station' => $station];
            }
        }
    }

    return ['bets' => $bets, 'events' => $events];
}

echo "=== TEST CASE 1: Single d-token ===\n";
$input1 = "T,ninh 03 43 83 23 d35n";
echo "Input: $input1\n\n";
$result1 = testParsing($input1);

echo "Events:\n";
foreach ($result1['events'] as $e) echo "  $e\n";

echo "\nBets (" . count($result1['bets']) . " total):\n";
foreach ($result1['bets'] as $b) {
    $amtK = $b['amount'] / 1000;
    echo "  {$b['number']} {$b['type']} {$amtK}n\n";
}
echo "\nExpected: 4 bets (chỉ đầu 35n, KHÔNG có đuôi)\n";
echo (count($result1['bets']) === 4 ? "✅ PASS" : "❌ FAIL") . "\n";

echo "\n" . str_repeat("=", 60) . "\n\n";

echo "=== TEST CASE 2: Double d-tokens (consecutive) ===\n";
$input2 = "T,ninh 27 65 05 69 85 d35n d70n";
echo "Input: $input2\n\n";
$result2 = testParsing($input2);

echo "Events:\n";
foreach ($result2['events'] as $e) echo "  $e\n";

echo "\nBets (" . count($result2['bets']) . " total):\n";
$grouped2 = [];
foreach ($result2['bets'] as $b) {
    $key = "{$b['type']}_{$b['amount']}";
    if (!isset($grouped2[$key])) $grouped2[$key] = [];
    $grouped2[$key][] = $b['number'];
}
foreach ($grouped2 as $key => $nums) {
    [$type, $amt] = explode('_', $key);
    $amtK = $amt / 1000;
    echo "  $type {$amtK}n: " . implode(', ', $nums) . "\n";
}
echo "\nExpected: 10 bets (5 đầu 35n + 5 đuôi 70n)\n";
echo (count($result2['bets']) === 10 ? "✅ PASS" : "❌ FAIL") . "\n";

echo "\n" . str_repeat("=", 60) . "\n\n";

echo "=== TEST CASE 3: Mixed (single d, then double d) ===\n";
$input3 = "T,ninh 03 43 83 23 d35n 27 65 05 69 85 d35n d70n 67 63 d0n d35n";
echo "Input: $input3\n\n";
$result3 = testParsing($input3);

echo "Events:\n";
foreach ($result3['events'] as $e) echo "  $e\n";

echo "\nBets (" . count($result3['bets']) . " total):\n";
$grouped3 = [];
foreach ($result3['bets'] as $b) {
    $key = "{$b['type']}_{$b['amount']}";
    if (!isset($grouped3[$key])) $grouped3[$key] = [];
    $grouped3[$key][] = $b['number'];
}
foreach ($grouped3 as $key => $nums) {
    [$type, $amt] = explode('_', $key);
    $amtK = $amt / 1000;
    echo "  $type {$amtK}n: " . implode(', ', $nums) . " (" . count($nums) . " số)\n";
}

echo "\nExpected:\n";
echo "  - Group 1: 03,43,83,23 chỉ đầu 35n (4 bets)\n";
echo "  - Group 2: 27,65,05,69,85 đầu 35n + đuôi 70n (10 bets)\n";
echo "  - Group 3: 67,63 chỉ đuôi 35n (2 bets, d0n skip)\n";
echo "  Total: 16 bets\n";
echo (count($result3['bets']) === 16 ? "✅ PASS" : "❌ FAIL") . "\n";

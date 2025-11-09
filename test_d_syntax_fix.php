<?php
/**
 * Test cú pháp d...n d...n (đầu và đuôi)
 *
 * Nếu có 2 d...n: d1 = đầu, d2 = đuôi
 * Nếu có 1 d...n: mặc định là đầu
 */

// Simulate a simple test without Laravel dependencies
function simulateParser($input) {
    $tokens = preg_split('/\s+/', trim($input));
    $numbers = [];
    $pair_d = [];
    $bets = [];

    foreach ($tokens as $tok) {
        // Number
        if (preg_match('/^\d{2,4}$/', $tok)) {
            $numbers[] = $tok;
        }
        // d+amount (d150n, d100n, etc.)
        elseif (preg_match('/^d(\d+)n$/', $tok, $m)) {
            $pair_d[] = (int)$m[1] * 1000;
        }
    }

    // Flush: create bets
    if (!empty($numbers) && !empty($pair_d)) {
        $dauAmt = $pair_d[0] ?? null;
        $duoiAmt = $pair_d[1] ?? null;

        foreach ($numbers as $num) {
            if ($dauAmt) {
                $bets[] = ['number' => $num, 'type' => 'dau', 'amount' => $dauAmt];
            }
            if ($duoiAmt) {
                $bets[] = ['number' => $num, 'type' => 'duoi', 'amount' => $duoiAmt];
            }
        }
    }

    return $bets;
}

echo "=== Test 1: Input với 2 d tokens (đầu + đuôi) ===\n";
$input1 = "10 50 90 30 d150n d100n";
echo "Input: $input1\n\n";

$bets1 = simulateParser($input1);
echo "Expected: 8 bets (4 số × 2 kiểu)\n";
echo "Actual: " . count($bets1) . " bets\n\n";

$grouped1 = [];
foreach ($bets1 as $bet) {
    $key = $bet['type'] . '_' . ($bet['amount'] / 1000) . 'n';
    if (!isset($grouped1[$key])) $grouped1[$key] = [];
    $grouped1[$key][] = $bet['number'];
}

foreach ($grouped1 as $key => $nums) {
    echo "  $key: " . implode(', ', $nums) . "\n";
}

$test1Pass = (count($bets1) === 8) &&
             (count($grouped1['dau_150n'] ?? []) === 4) &&
             (count($grouped1['duoi_100n'] ?? []) === 4);
echo "\n" . ($test1Pass ? "✅ PASS" : "❌ FAIL") . "\n";

echo "\n" . str_repeat("=", 60) . "\n\n";

echo "=== Test 2: Input với 1 d token (chỉ đầu) ===\n";
$input2 = "10 50 90 30 d150n";
echo "Input: $input2\n\n";

$bets2 = simulateParser($input2);
echo "Expected: 4 bets (4 số × 1 kiểu đầu)\n";
echo "Actual: " . count($bets2) . " bets\n\n";

$grouped2 = [];
foreach ($bets2 as $bet) {
    $key = $bet['type'] . '_' . ($bet['amount'] / 1000) . 'n';
    if (!isset($grouped2[$key])) $grouped2[$key] = [];
    $grouped2[$key][] = $bet['number'];
}

foreach ($grouped2 as $key => $nums) {
    echo "  $key: " . implode(', ', $nums) . "\n";
}

$test2Pass = (count($bets2) === 4) &&
             (count($grouped2['dau_150n'] ?? []) === 4) &&
             !isset($grouped2['duoi_150n']);
echo "\n" . ($test2Pass ? "✅ PASS" : "❌ FAIL") . "\n";

echo "\n" . str_repeat("=", 60) . "\n";
echo "\n🎯 Logic mong muốn đã được verify!\n";
echo "Bây giờ cần test với parser thực để đảm bảo fix hoạt động đúng.\n";

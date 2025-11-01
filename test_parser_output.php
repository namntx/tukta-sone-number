<?php

// Simple test to check parser output structure
$input = "T,ninh  03 43 83 23 d35n d40n 27 65 05 69 85 d35n d70n 67 63 d0n d35n";

// Simulate the parsing logic inline to test the fix
function testParsing($message) {
    $tokens = preg_split('/\s+/', strtolower(str_replace([',', '.'], ' ', $message)));
    $tokens = array_filter($tokens);

    $numbers = [];
    $pair_d_dau = [];
    $bets = [];
    $station = 'tay ninh';
    $events = [];

    foreach ($tokens as $tok) {
        // Station
        if (in_array($tok, ['t', 'tninh', 'tn'])) {
            $station = 'tay ninh';
            $events[] = "Station: $station";
            continue;
        }

        // Number
        if (preg_match('/^\d{2,4}$/', $tok)) {
            $numbers[] = $tok;
            $events[] = "Number: $tok";
            continue;
        }

        // d+amount (d35n, d40n, etc.)
        if (preg_match('/^d(\d+)([nk])$/', $tok, $m)) {
            $amt = (int)$m[1] * 1000;
            $pair_d_dau[] = $amt;
            $events[] = "D-token: {$tok} = {$amt}";

            // AUTO FLUSH when we have 2 elements (dau + duoi)
            if (count($pair_d_dau) >= 2 && count($numbers) > 0) {
                $dauAmt = $pair_d_dau[0];
                $duoiAmt = $pair_d_dau[1] ?? null;

                $events[] = "*** AUTO FLUSH: " . count($numbers) . " numbers with dau={$dauAmt}, duoi={$duoiAmt}";

                foreach ($numbers as $n) {
                    if ($dauAmt > 0) {
                        $bets[] = ['number' => $n, 'type' => 'dau', 'amount' => $dauAmt, 'station' => $station];
                    }
                    if ($duoiAmt !== null && $duoiAmt > 0) {
                        $bets[] = ['number' => $n, 'type' => 'duoi', 'amount' => $duoiAmt, 'station' => $station];
                    }
                }

                // Reset after flush
                $numbers = [];
                $pair_d_dau = [];
                $events[] = "*** RESET: numbers and pair_d_dau cleared";
            }
        }
    }

    // Final flush if any remaining
    if (count($numbers) > 0 && count($pair_d_dau) >= 2) {
        $dauAmt = $pair_d_dau[0];
        $duoiAmt = $pair_d_dau[1] ?? null;
        $events[] = "*** FINAL FLUSH: " . count($numbers) . " numbers with dau={$dauAmt}, duoi={$duoiAmt}";
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

echo "Input: $input\n\n";

$result = testParsing($input);

echo "=== Events ===\n";
foreach ($result['events'] as $event) {
    echo "$event\n";
}

echo "\n=== Bets Generated (" . count($result['bets']) . " total) ===\n";
$grouped = [];
foreach ($result['bets'] as $bet) {
    $key = "{$bet['type']}_{$bet['amount']}";
    if (!isset($grouped[$key])) {
        $grouped[$key] = [];
    }
    $grouped[$key][] = $bet['number'];
}

foreach ($grouped as $key => $numbers) {
    [$type, $amt] = explode('_', $key);
    $amtK = $amt / 1000;
    echo "  $type {$amtK}n: " . implode(', ', $numbers) . " (" . count($numbers) . " số)\n";
}

echo "\n=== Expected Output ===\n";
echo "  dau 35n: 03, 43, 83, 23 (4 số)\n";
echo "  duoi 40n: 03, 43, 83, 23 (4 số)\n";
echo "  dau 35n: 27, 65, 05, 69, 85 (5 số)\n";
echo "  duoi 70n: 27, 65, 05, 69, 85 (5 số)\n";
echo "  duoi 35n: 67, 63 (2 số) [d0n không emit vì amount=0]\n";
echo "Total: 20 bets\n";

$success = count($result['bets']) === 20;
echo "\n" . ($success ? "✅ SUCCESS" : "❌ FAILED") . ": Got " . count($result['bets']) . " bets (expected 20)\n";

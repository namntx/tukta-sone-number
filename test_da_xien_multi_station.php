<?php

// Simple test for da_xien multi-station
$input = "14,27,72 tn bt dx 0.8n";

// Simulate parsing logic
function testMultiStation($message) {
    $tokens = preg_split('/[,\s]+/', strtolower($message));
    $tokens = array_filter($tokens);

    $numbers = [];
    $stations = [];
    $type = null;
    $amount = 0;
    $events = [];

    foreach ($tokens as $tok) {
        // Numbers
        if (preg_match('/^\d{2,4}$/', $tok)) {
            $numbers[] = $tok;
            $events[] = "Number: $tok";
            continue;
        }

        // Stations
        $stationMap = ['tn' => 'tay ninh', 'bt' => 'ben tre', 'hcm' => 'tp.hcm'];
        if (isset($stationMap[$tok])) {
            // LOGIC MỚI: Nếu chưa có type → cộng dồn
            if ($type === null) {
                $stations[] = $stationMap[$tok];
                $events[] = "Station added: {$stationMap[$tok]} (total: " . count($stations) . ")";
            } else {
                $events[] = "Station ignored (type already set): {$stationMap[$tok]}";
            }
            continue;
        }

        // Type
        if ($tok === 'dx') {
            $type = 'da_xien';
            $events[] = "Type set: da_xien";
            continue;
        }

        // Amount
        if (preg_match('/^(\d+\.?\d*)n$/', $tok, $m)) {
            $amount = (float)$m[1] * 1000;
            $events[] = "Amount: $amount";
            continue;
        }
    }

    return [
        'numbers' => $numbers,
        'stations' => $stations,
        'type' => $type,
        'amount' => $amount,
        'meta' => ['dai_count' => count($stations)],
        'events' => $events,
    ];
}

echo "Input: $input\n\n";

$result = testMultiStation($input);

echo "=== Events ===\n";
foreach ($result['events'] as $e) {
    echo "  $e\n";
}

echo "\n=== Result ===\n";
echo "Numbers: " . implode(', ', $result['numbers']) . " (" . count($result['numbers']) . ")\n";
echo "Stations: " . implode(' + ', $result['stations']) . " (" . count($result['stations']) . ")\n";
echo "Type: {$result['type']}\n";
echo "Amount: " . number_format($result['amount']) . " VND\n";
echo "dai_count: {$result['meta']['dai_count']}\n";

echo "\n=== Expected ===\n";
echo "✅ 3 numbers: 14, 27, 72\n";
echo "✅ 2 stations: tay ninh + ben tre\n";
echo "✅ Type: da_xien\n";
echo "✅ Amount: 8,000 VND\n";
echo "✅ meta.dai_count: 2\n";

$success = count($result['stations']) === 2 && $result['meta']['dai_count'] === 2;
echo "\n" . ($success ? "✅ SUCCESS" : "❌ FAILED") . "\n";

// Cost_xac calculation test
echo "\n=== Cost_xac Calculation ===\n";
$dai_count = $result['meta']['dai_count'];
$multiplier = match($dai_count) {
    2 => 4,
    3 => 4 * 3,
    4 => 4 * 6,
    default => 4,
};
$cost_xac = $result['amount'] * $multiplier * 18 * 1.0; // buy_rate = 1
echo "Formula: amount × multiplier × 18 × buy_rate\n";
echo "  = {$result['amount']} × {$multiplier} × 18 × 1.0\n";
echo "  = " . number_format($cost_xac) . " VND\n";
echo "\nExpected: 576,000 VND (8,000 × 4 × 18 × 1)\n";
echo ($cost_xac === 576000 ? "✅ CORRECT" : "❌ WRONG") . "\n";

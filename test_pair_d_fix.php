<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\BettingMessageParser;
use App\Services\BetPricingService;

$pricing = app(BetPricingService::class);
$parser = new BettingMessageParser($pricing);

$input = "T,ninh  03 43 83 23 d35n d40n 27 65 05 69 85 d35n d70n 67 63 d0n d35n";

echo "Input: $input\n\n";

$result = $parser->parseMessage($input, ['region' => 'nam']);

echo "Is Valid: " . ($result['is_valid'] ? 'true' : 'false') . "\n";
echo "Total Bets: " . count($result['multiple_bets']) . "\n\n";

// Group bets by their amount combination
$groups = [];
foreach ($result['multiple_bets'] as $bet) {
    $key = $bet['type'] . '_' . $bet['amount'];
    if (!isset($groups[$key])) {
        $groups[$key] = [];
    }
    $groups[$key][] = $bet['numbers'][0];
}

echo "Grouped Bets:\n";
foreach ($groups as $key => $numbers) {
    [$type, $amount] = explode('_', $key);
    echo "  $type $amount: " . implode(', ', $numbers) . " (" . count($numbers) . " số)\n";
}

echo "\n--- Expected Output ---\n";
echo "Group 1: 03, 43, 83, 23 → đầu 35n, đuôi 40n (8 bets)\n";
echo "Group 2: 27, 65, 05, 69, 85 → đầu 35n, đuôi 70n (10 bets)\n";
echo "Group 3: 67, 63 → đầu 0n, đuôi 35n (2 bets for đuôi only)\n";
echo "Total: 20 bets (hoặc 18 nếu d0n bỏ qua)\n\n";

echo "\n--- Debug Events ---\n";
$autoFlushCount = 0;
foreach ($result['debug']['events'] as $event) {
    if ($event['kind'] === 'pair_d_auto_flush') {
        $autoFlushCount++;
        echo "Auto flush #$autoFlushCount triggered\n";
    }
    if ($event['kind'] === 'emit_pair_d') {
        echo "Emit pair_d: " . count($event['numbers']) . " numbers, đầu={$event['dau']}, đuôi={$event['duoi']}\n";
    }
}

echo "\n--- Actual Bets ---\n";
foreach ($result['multiple_bets'] as $i => $bet) {
    echo ($i + 1) . ". {$bet['numbers'][0]} {$bet['type']} {$bet['amount']}\n";
}

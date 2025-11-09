<?php

// Bootstrap Laravel
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\BettingMessageParser;
use App\Services\BetPricingService;
use App\Services\LotteryScheduleService;

$pricing = app(BetPricingService::class);
$scheduleService = app(LotteryScheduleService::class);
$parser = new BettingMessageParser($pricing, $scheduleService);

// Test Input 1: 2dai không flush sau khi xử lí xong nhóm cược đầu
echo "========== TEST INPUT 1 ==========\n";
$input1 = "2dai 28 dau 125n.  Tg 384 684 271 272 274 168 252 751 773 939 979 915 616 353 323 464 322 115 476 xc 25n.  Kg xc 272 25n";
$result1 = $parser->parseMessage($input1, ['region' => 'nam']);

echo "Input: $input1\n\n";
echo "Number of bets: " . count($result1['multiple_bets']) . "\n";

// Group bets by station
$betsByStation = [];
foreach ($result1['multiple_bets'] as $bet) {
    $station = $bet['station'] ?? 'NULL';
    if (!isset($betsByStation[$station])) {
        $betsByStation[$station] = [];
    }
    $betsByStation[$station][] = $bet;
}

foreach ($betsByStation as $station => $bets) {
    echo "\n--- Station: $station ---\n";
    foreach ($bets as $idx => $bet) {
        echo "  Bet " . ($idx + 1) . ": ";
        echo implode(', ', $bet['numbers']) . " | ";
        echo $bet['type'] . " | ";
        echo $bet['amount'] . "đ\n";
    }
    echo "  Total bets for $station: " . count($bets) . "\n";
}

echo "\n\nExpected behavior:\n";
echo "  - Bet 1: 28 dau (2 provinces auto-resolved or 2 bets)\n";
echo "  - Bet 2-N: Tg station with xc bets\n";
echo "  - Bet N+1: Kg station with xc bet for 272\n";
echo "\nACTUAL: Check if 28 dau appears with Tg/Kg stations (BUG if yes)\n";

// Test Input 2: Đài không flush khi gặp đài mới
echo "\n\n========== TEST INPUT 2 ==========\n";
$input2 = "Tg 11 51 39 dd250n 91 59 dđ200n 32 72 35 75 09 90 dđ150n. Kg lo 58 68 50n.";
$result2 = $parser->parseMessage($input2, ['region' => 'nam']);

echo "Input: $input2\n\n";
echo "Number of bets: " . count($result2['multiple_bets']) . "\n";

// Group bets by station
$betsByStation2 = [];
foreach ($result2['multiple_bets'] as $bet) {
    $station = $bet['station'] ?? 'NULL';
    if (!isset($betsByStation2[$station])) {
        $betsByStation2[$station] = [];
    }
    $betsByStation2[$station][] = $bet;
}

foreach ($betsByStation2 as $station => $bets) {
    echo "\n--- Station: $station ---\n";
    foreach ($bets as $idx => $bet) {
        echo "  Bet " . ($idx + 1) . ": ";
        echo implode(', ', $bet['numbers']) . " | ";
        echo $bet['type'] . " | ";
        echo $bet['amount'] . "đ\n";
    }
    echo "  Total bets for $station: " . count($bets) . "\n";
}

echo "\n\nExpected behavior:\n";
echo "  - All bets with numbers 11,51,39 / 91,59 / 32,72,35,75,09,90 should be for station: tien giang\n";
echo "  - Bets with numbers 58,68 should be for station: kien giang\n";
echo "\nACTUAL: Check if 58,68 appears with multiple stations (BUG if yes)\n";

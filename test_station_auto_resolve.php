<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\BettingMessageParser;
use App\Services\BetPricingService;
use App\Services\LotteryScheduleService;

echo "=== TEST AUTO-RESOLVE ĐÀI CHÍNH ===\n\n";

$pricing = new BetPricingService();
$pricing->begin(null, 'nam');
$scheduleService = new LotteryScheduleService();
$parser = new BettingMessageParser($pricing, $scheduleService);

// Test case: "23 lo 10n" vào thứ 6 (2025-10-31)
// Đài chính phải là Bình Dương

$message = '23 lo 10n';
$date = '2025-10-31';
$region = 'nam';

echo "Message: {$message}\n";
echo "Date: {$date} (thứ 6)\n";
echo "Region: {$region}\n\n";

$result = $parser->parseMessage($message, [
    'region' => $region,
    'date' => $date,
]);

echo "✅ Parsed successfully: " . ($result['is_valid'] ? 'YES' : 'NO') . "\n";
echo "\n";

if ($result['is_valid'] && !empty($result['multiple_bets'])) {
    $bet = $result['multiple_bets'][0];
    
    echo "📋 BET DETAILS:\n";
    echo "  Type: {$bet['type']}\n";
    echo "  Numbers: [" . implode(', ', $bet['numbers']) . "]\n";
    echo "  Amount: {$bet['amount']}\n";
    echo "  Station: '{$bet['station']}'\n";
    echo "\n";
    
    $expectedStation = 'binh duong';
    if ($bet['station'] === $expectedStation) {
        echo "✅ CORRECT! Station is '{$expectedStation}' (Bình Dương)\n";
    } else {
        echo "❌ WRONG! Expected '{$expectedStation}' but got '{$bet['station']}'\n";
    }
} else {
    echo "❌ Parse failed or no bets created\n";
    if (!empty($result['errors'])) {
        echo "Errors: " . implode(', ', $result['errors']) . "\n";
    }
}

echo "\n=== DEBUG EVENTS ===\n";
$relevantEvents = array_filter($result['debug']['events'], fn($e) => 
    str_contains($e['kind'], 'station') || 
    str_contains($e['kind'], 'auto_resolve')
);
foreach ($relevantEvents as $e) {
    $kind = $e['kind'];
    unset($e['kind']);
    $data = json_encode($e, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    echo "  - {$kind}:\n";
    echo "    " . str_replace("\n", "\n    ", $data) . "\n";
}


<?php
/**
 * Test parser issue với input: "24 lo 15n T,Giang lo 26 25 58 50n"
 *
 * Expected:
 * - Bet 1: số 24, bao_lo, 15n
 * - Bet 2: Tiền Giang, số 26, 25, 58, bao_lo, 50n (mỗi con)
 *
 * Actual issue: số 24 bị nhảy vào bet 2
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$parser = app(App\Services\BettingMessageParser::class);

echo "=== Test Parser Issue ===\n\n";

$input = "24 lo 15n T,Giang lo 26 25 58 50n";
echo "Input: $input\n\n";

$result = $parser->parseMessage($input, ['region' => 'nam']);

echo "Number of bets: " . count($result['bets']) . "\n\n";

foreach ($result['bets'] as $i => $bet) {
    echo "Bet " . ($i + 1) . ":\n";
    echo "  Type: " . ($bet['type'] ?? 'N/A') . "\n";
    echo "  Numbers: " . json_encode($bet['numbers'] ?? []) . "\n";
    echo "  Amount: " . ($bet['amount'] ?? 'N/A') . "\n";
    echo "  Station: " . ($bet['station'] ?? 'N/A') . "\n";
    echo "\n";
}

echo "=== Expected ===\n";
echo "Bet 1: type=bao_lo, numbers=[24], amount=15000, station=null\n";
echo "Bet 2: type=bao_lo, numbers=[26], amount=50000, station=tien giang\n";
echo "Bet 3: type=bao_lo, numbers=[25], amount=50000, station=tien giang\n";
echo "Bet 4: type=bao_lo, numbers=[58], amount=50000, station=tien giang\n";

echo "\n=== Analysis ===\n";
echo "Issue: Số 24 không được nhảy vào các bet sau\n";
echo "Root cause: Khi gặp 'lo' token thứ 2, parser kế thừa last_numbers từ bet trước\n";

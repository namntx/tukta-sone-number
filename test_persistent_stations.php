<?php

require_once 'vendor/autoload.php';

use App\Services\BettingMessageParser;

$parser = new BettingMessageParser();

echo "=== TEST PERSISTENT STATIONS ===\n\n";

$testCase = '3dai tp la bp 56.65 đx 1n .56,59 đx1n. 72.79.54 đx 1n.';
echo "Input: '$testCase'\n";
echo "Normalized: '" . $parser->testNormalize($testCase) . "'\n";
echo "Tokens: " . json_encode($parser->testTokenize($testCase)) . "\n\n";

$result = $parser->testParseMessage($testCase);
if ($result['is_valid']) {
    echo "✅ Valid - " . count($result['multiple_bets']) . " bets\n";
    foreach ($result['multiple_bets'] as $bet) {
        echo "  - Station: {$bet['station']}, Type: {$bet['type']}, Numbers: " . implode(',', $bet['numbers']) . ", Amount: {$bet['amount']}\n";
    }
} else {
    echo "❌ Invalid: " . implode(', ', $result['errors']) . "\n";
}

echo "\n=== EXPECTED OUTPUT ===\n";
echo "Should create:\n";
echo "- 3 bets đá xiên 56 65 for tp la bp\n";
echo "- 3 bets đá xiên 56 59 for tp la bp\n";
echo "- 9 bets đá xiên 72 79 54 for tp la bp\n";
echo "Total: 15 bets\n";

<?php

require_once 'vendor/autoload.php';

use App\Services\BettingMessageParser;

$parser = new BettingMessageParser();

$messages = [
    "56 65 dx 1n",      // Valid: numbers + directive + amount
    "dx 56 65 1n",      // Valid: directive + numbers + amount
    "tp 56 65 dx 1n",   // Valid: station + numbers + directive + amount
    "tp dx 56 65 1n",   // Valid: station + directive + numbers + amount
];

foreach ($messages as $message) {
    echo "Testing: '$message'\n";

    // Test tokenization
    $tokens = $parser->testTokenize($message);
    echo "Tokens: " . json_encode($tokens) . "\n";

    // Test parsing
    $result = $parser->parseMessage($message);
    echo "Bets: " . count($result['multiple_bets'] ?? []) . "\n";

    if (!empty($result['multiple_bets'])) {
        foreach ($result['multiple_bets'] as $bet) {
            echo "  - {$bet['station']} | {$bet['type']} | " . implode(',', $bet['numbers']) . " | {$bet['amount']}\n";
        }
    }

    echo "\n";
}

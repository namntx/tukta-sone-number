<?php

require_once 'vendor/autoload.php';

use App\Services\BettingMessageParser;

// Test cases cho 2dai/3dai với đài cụ thể
$testCases = [
    '2dai tn ag 68 5n',
    '3dai hcm dn vt 89 10n', 
    '2dai tây ninh an giang 15 20n',
    '3dai tp.hcm đồng nai bình dương 25 30n',
    '2dai tn ag lo 68 5n',
    '3dai hcm dn vt xc 515 20n',
];

$parser = new BettingMessageParser();

echo "=== TEST 2DAI/3DAI VỚI ĐÀI CỤ THỂ ===\n\n";

foreach ($testCases as $testCase) {
    echo "Input: '$testCase'\n";
    $result = $parser->testParseMessage($testCase);
    
    if ($result['is_valid']) {
        echo "✅ Valid\n";
        foreach ($result['multiple_bets'] as $bet) {
            echo "  - Station: {$bet['station']}, Type: {$bet['type']}, Numbers: " . implode(',', $bet['numbers']) . ", Amount: {$bet['amount']}\n";
        }
    } else {
        echo "❌ Invalid: " . implode(', ', $result['errors']) . "\n";
    }
    echo "\n";
}

echo "=== TEST NORMALIZATION ===\n\n";

$normalizationTests = [
    '2dai tn ag 68 5n',
    '3dai hcm dn vt 89 10n',
    '2dai tây ninh an giang 15 20n',
];

foreach ($normalizationTests as $test) {
    echo "Input: '$test'\n";
    echo "Normalized: '" . $parser->testNormalize($test) . "'\n";
    echo "Tokens: " . json_encode($parser->testTokenize($test)) . "\n\n";
}

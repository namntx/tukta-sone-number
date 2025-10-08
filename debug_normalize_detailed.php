<?php

require_once 'vendor/autoload.php';

use App\Services\BettingMessageParser;

$parser = new BettingMessageParser();

echo "=== DEBUG NORMALIZE DETAILED ===\n\n";

$testCases = [
    '3dai tp la bp 56.65 đx 1n .56,59 đx1n. 72.79.54 đx 1n.',
    '56.65',
    '.56,59', 
    '72.79.54'
];

foreach ($testCases as $test) {
    echo "Input: '$test'\n";
    echo "Normalized: '" . $parser->testNormalize($test) . "'\n";
    echo "Tokens: " . json_encode($parser->testTokenize($test)) . "\n\n";
}

// Test step by step normalization
echo "=== STEP BY STEP NORMALIZATION ===\n";
$input = '3dai tp la bp 56.65 đx 1n .56,59 đx1n. 72.79.54 đx 1n.';
echo "Original: '$input'\n";

// Manual normalization steps
$s = $input;
echo "Step 0: '$s'\n";

$s = strtolower($s);
echo "Step 1 (lower): '$s'\n";

// Strip VN
$acc = 'àáạảãâầấậẩẫăằắặẳẵèéẹẻẽêềếệểễìíịỉĩòóọỏõôồốộổỗơờớợởỡùúụủũưừứựửữỳýỵỷỹđ';
$rep = 'aaaaaaaaaaaaaaaaaeeeeeeeeeeeiiiiiooooooooooooooooouuuuuuuuuuuyyyyyd';
$s = strtr($s, array_combine(preg_split('//u',$acc,-1,PREG_SPLIT_NO_EMPTY), preg_split('//u',$rep,-1,PREG_SPLIT_NO_EMPTY)));
echo "Step 2 (strip VN): '$s'\n";

// Replace comma with dot
$s = preg_replace('/(?<=\d),(?=\d)/', '.', $s);
echo "Step 3 (comma to dot): '$s'\n";

// Handle single dot
$s = preg_replace('/(\d+)\.(\d+)/', '$1 $2', $s);
echo "Step 4 (single dot): '$s'\n";

// Handle double dot
$s = preg_replace('/(\d+)\.(\d+)\.(\d+)/', '$1 $2 $3', $s);
echo "Step 5 (double dot): '$s'\n";

echo "\nFinal: '" . $parser->testNormalize($input) . "'\n";
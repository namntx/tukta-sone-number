<?php

require_once 'vendor/autoload.php';

use App\Services\BettingMessageParser;

$parser = new BettingMessageParser();

echo "=== DEBUG PERSISTENT STATIONS ===\n\n";

$testCase = '3dai tp la bp 56.65 đx 1n .56,59 đx1n. 72.79.54 đx 1n.';
echo "Input: '$testCase'\n";
echo "Normalized: '" . $parser->testNormalize($testCase) . "'\n";
echo "Tokens: " . json_encode($parser->testTokenize($testCase)) . "\n\n";

// Test parseLine trực tiếp để xem lastSelectedStations
$reflection = new ReflectionClass($parser);
$parseLineMethod = $reflection->getMethod('parseLine');
$parseLineMethod->setAccessible(true);

$result = $parseLineMethod->invoke($parser, $testCase);
echo "ParseLine result: " . json_encode($result) . "\n\n";

// Kiểm tra lastSelectedStations sau khi parse
$lastSelectedStationsProperty = $reflection->getProperty('lastSelectedStations');
$lastSelectedStationsProperty->setAccessible(true);
$lastSelectedStations = $lastSelectedStationsProperty->getValue($parser);
echo "LastSelectedStations after parse: " . json_encode($lastSelectedStations) . "\n\n";

// Test với test case đơn giản hơn
$simpleCase = '3dai tp la bp 56 65 đx 1n';
echo "Simple case: '$simpleCase'\n";
$result2 = $parseLineMethod->invoke($parser, $simpleCase);
echo "Simple result: " . json_encode($result2) . "\n";
$lastSelectedStations2 = $lastSelectedStationsProperty->getValue($parser);
echo "LastSelectedStations after simple: " . json_encode($lastSelectedStations2) . "\n\n";

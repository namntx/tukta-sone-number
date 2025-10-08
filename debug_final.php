<?php

require_once 'vendor/autoload.php';

use App\Services\BettingMessageParser;

$parser = new BettingMessageParser();

$message = "3dai tp la bp 56.65 đx 1n .56,59 đx1n. 72.79.54 đx 1n.";

echo "Testing: '$message'\n";

$result = $parser->parseMessage($message);
$bets = $result['multiple_bets'] ?? [];

echo "Total bets: " . count($bets) . "\n\n";

$grouped = [];
foreach ($bets as $bet) {
    $key = implode(',', $bet['numbers']);
    if (!isset($grouped[$key])) {
        $grouped[$key] = [];
    }
    $grouped[$key][] = $bet['station'];
}

foreach ($grouped as $numbers => $stations) {
    $uniqueStations = array_unique($stations);
    echo "- " . count($uniqueStations) . " bet đá xiên $numbers cho " . count($uniqueStations) . " đài: " . implode(', ', $uniqueStations) . "\n";
}

echo "\nExpected:\n";
echo "- 3 bet đá xiên 56,65 cho 3 đài: tp.hcm, long an, bình phước\n";
echo "- 3 bet đá xiên 56,59 cho 3 đài: tp.hcm, long an, bình phước\n";
echo "- 9 bet đá xiên 72,79,54 cho 3 đài: tp.hcm, long an, bình phước\n";

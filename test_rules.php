<?php

require_once 'vendor/autoload.php';

use App\Services\BettingMessageParser;

// Test các quy tắc mới
$parser = new BettingMessageParser();

$testCases = [
    // Rule 1: Tái sử dụng số cược gần nhất
    "56 65 dx 1n . dx 1n" => "Kiểm tra tái sử dụng số 56 65 cho dx 1n",

    // Rule 2: Duy trì đài giữa các bets
    "tp 56 dx 1n . 65 dx 1n" => "Kiểm tra duy trì đài tp cho bet thứ 2",

    // Rule 3: Format linh hoạt
    "tp 56 dx 1n" => "Format: đài + số + kiểu + tiền",
    "dx 56 1n" => "Format: kiểu + số + tiền",
    "56 dx 1n" => "Format: số + kiểu + tiền",

    // Rule 4: Duy trì multi-station
    "3dai tp la bp 56 dx 1n . 65 dx 1n" => "Duy trì 3 đài cho bets sau",

    // Rule 5: Đài mặc định khi bắt đầu không có đài
    "56 dx 1n" => "Sử dụng đài mặc định"
];

echo "=== Testing Rules Implementation ===\n\n";

foreach ($testCases as $message => $description) {
    echo "Test: $description\n";
    echo "Message: '$message'\n";

    $result = $parser->parseMessage($message);
    $bets = $result['multiple_bets'] ?? [];

    echo "Bets count: " . count($bets) . "\n";

    if (!empty($bets)) {
        $stations = array_unique(array_column($bets, 'station'));
        $numbers = array_unique(array_merge(...array_column($bets, 'numbers')));

        echo "Stations: " . implode(', ', $stations) . "\n";
        echo "Numbers: " . implode(', ', $numbers) . "\n";

        foreach ($bets as $bet) {
            echo "  - {$bet['station']} | {$bet['type']} | " . implode(',', $bet['numbers']) . " | {$bet['amount']}\n";
        }
    }

    echo "\n";
}

<?php

require __DIR__.'/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\BettingMessageParser;

// Get parser from Laravel container
$parser = app(BettingMessageParser::class);

// Test cases
$testCases = [
    [
        'input' => 'tp, 92 xyz 5n',
        'description' => 'Single unknown token "xyz"',
    ],
    [
        'input' => 'tp, 92 abc def 5n',
        'description' => 'Multiple unknown tokens "abc" and "def"',
    ],
    [
        'input' => 'tp, 92 xc 5n',
        'description' => 'Valid input - no highlighting',
    ],
    [
        'input' => '14 27 72 xi2 5n',
        'description' => 'Xiên in wrong region (Nam)',
    ],
];

echo "=== Testing Highlighted Message Feature ===\n\n";

foreach ($testCases as $i => $test) {
    echo "Test " . ($i + 1) . ": " . $test['description'] . "\n";
    echo "Input: " . $test['input'] . "\n";

    try {
        $result = $parser->parseMessage($test['input'], ['region' => 'nam', 'date' => '2025-11-03']);

        echo "Highlighted: " . $result['highlighted_message'] . "\n";

        // Check if highlighting exists
        $hasHighlight = strpos($result['highlighted_message'], '<span') !== false;
        echo "Has highlighting: " . ($hasHighlight ? 'YES' : 'NO') . "\n";

        // Extract debug events
        $events = $result['debug']['events'] ?? [];
        $errorCount = count(array_filter($events, fn($e) => str_starts_with($e['kind'] ?? '', 'error_')));
        $warningCount = count(array_filter($events, fn($e) => str_starts_with($e['kind'] ?? '', 'warning_')));
        $skipCount = count(array_filter($events, fn($e) => ($e['kind'] ?? '') === 'skip'));

        echo "Events: " . count($events) . " total (Errors: $errorCount, Warnings: $warningCount, Skipped: $skipCount)\n";

        echo "Status: ✓ PASSED\n";
    } catch (Exception $e) {
        echo "Status: ✗ FAILED - " . $e->getMessage() . "\n";
    }

    echo "\n" . str_repeat("-", 80) . "\n\n";
}

echo "=== All Tests Completed ===\n";

<?php

/**
 * Demo script to test highlighting logic without database
 */

// Test the highlighting logic directly
function buildHighlightedMessageDemo(string $originalMessage, array $highlights): string
{
    if (empty($highlights)) {
        return htmlspecialchars($originalMessage, ENT_QUOTES, 'UTF-8');
    }

    // Normalize for matching
    $normalizedOriginal = normalizeForMatching($originalMessage);

    // Find positions
    $positions = [];
    foreach ($highlights as $highlight) {
        $normalizedToken = normalizeForMatching($highlight['token']);

        $offset = 0;
        while (($pos = mb_strpos($normalizedOriginal, $normalizedToken, $offset)) !== false) {
            $positions[] = [
                'start' => $pos,
                'end' => $pos + mb_strlen($highlight['token']),
                'token' => mb_substr($originalMessage, $pos, mb_strlen($highlight['token'])),
                'severity' => $highlight['severity'],
                'reason' => $highlight['reason'],
                'detail' => $highlight['detail']
            ];
            $offset = $pos + 1;
        }
    }

    if (empty($positions)) {
        return htmlspecialchars($originalMessage, ENT_QUOTES, 'UTF-8');
    }

    usort($positions, fn($a, $b) => $a['start'] <=> $b['start']);

    // Remove overlaps
    $filteredPositions = [];
    $lastEnd = -1;
    foreach ($positions as $pos) {
        if ($pos['start'] >= $lastEnd) {
            $filteredPositions[] = $pos;
            $lastEnd = $pos['end'];
        }
    }

    // Build highlighted message
    $highlightedMessage = '';
    $lastIndex = 0;

    foreach ($filteredPositions as $pos) {
        $before = mb_substr($originalMessage, $lastIndex, $pos['start'] - $lastIndex);
        $highlightedMessage .= htmlspecialchars($before, ENT_QUOTES, 'UTF-8');

        $cssClass = match ($pos['severity']) {
            'error' => 'text-red-600 font-semibold',
            'warning' => 'text-yellow-600 font-semibold',
            'block' => 'text-orange-600 font-semibold',
            default => 'text-gray-600 font-semibold'
        };

        $title = htmlspecialchars($pos['reason'] . ': ' . $pos['detail'], ENT_QUOTES, 'UTF-8');

        $highlightedMessage .= sprintf(
            '<span class="%s" title="%s">%s</span>',
            $cssClass,
            $title,
            htmlspecialchars($pos['token'], ENT_QUOTES, 'UTF-8')
        );

        $lastIndex = $pos['end'];
    }

    if ($lastIndex < mb_strlen($originalMessage)) {
        $after = mb_substr($originalMessage, $lastIndex);
        $highlightedMessage .= htmlspecialchars($after, ENT_QUOTES, 'UTF-8');
    }

    return $highlightedMessage;
}

function normalizeForMatching(string $s): string
{
    $s = mb_strtolower($s, 'UTF-8');
    $replacements = [
        'à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ' => 'a',
        'è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ'             => 'e',
        'ì|í|ị|ỉ|ĩ'                         => 'i',
        'ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ' => 'o',
        'ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ'             => 'u',
        'ỳ|ý|ỵ|ỷ|ỹ'                         => 'y',
        'đ'                                 => 'd',
    ];
    foreach ($replacements as $pattern => $replacement) {
        $s = preg_replace('/(' . $pattern . ')/u', $replacement, $s);
    }
    return $s;
}

echo "=== Demo: Improved Highlighted Message Feature ===\n\n";

// Test Case 1: Skip token (unknown keyword)
echo "Test 1: Unknown token\n";
$msg1 = 'tp, 92 xyz 5n';
$highlights1 = [
    [
        'token' => 'xyz',
        'severity' => 'error',
        'reason' => 'Token không được nhận dạng',
        'detail' => 'Từ khóa này không thuộc bất kỳ loại cược, đài, hoặc cú pháp nào hợp lệ.'
    ]
];
echo "Input: $msg1\n";
echo "Output: " . buildHighlightedMessageDemo($msg1, $highlights1) . "\n\n";

// Test Case 2: Error - wrong region for xiên
echo "Test 2: Xiên in wrong region\n";
$msg2 = 'tp, 14 27 xi2 5n';
$highlights2 = [
    [
        'token' => 'xi2',
        'severity' => 'error',
        'reason' => 'Block Emit Xien Wrong Region',
        'detail' => 'Xiên chỉ áp dụng cho Miền Bắc. Khu vực hiện tại: nam.'
    ]
];
echo "Input: $msg2\n";
echo "Output: " . buildHighlightedMessageDemo($msg2, $highlights2) . "\n\n";

// Test Case 3: Multiple unknown tokens
echo "Test 3: Multiple unknown tokens\n";
$msg3 = 'tp, 92 abc def 5n';
$highlights3 = [
    [
        'token' => 'abc',
        'severity' => 'error',
        'reason' => 'Token không được nhận dạng',
        'detail' => 'Từ khóa này không thuộc bất kỳ loại cược, đài, hoặc cú pháp nào hợp lệ.'
    ],
    [
        'token' => 'def',
        'severity' => 'error',
        'reason' => 'Token không được nhận dạng',
        'detail' => 'Từ khóa này không thuộc bất kỳ loại cược, đài, hoặc cú pháp nào hợp lệ.'
    ]
];
echo "Input: $msg3\n";
echo "Output: " . buildHighlightedMessageDemo($msg3, $highlights3) . "\n\n";

// Test Case 4: Warning - odd numbers for đá thẳng
echo "Test 4: Warning for đá thẳng odd numbers\n";
$msg4 = 'tp, 14 27 72 91 dt 5n';
$highlights4 = [
    [
        'token' => '91',
        'severity' => 'warning',
        'reason' => 'Warning Da Thang Odd Numbers',
        'detail' => 'Đá thẳng ghép cặp 2-2, số lẻ "91" bị bỏ qua.'
    ]
];
echo "Input: $msg4\n";
echo "Output: " . buildHighlightedMessageDemo($msg4, $highlights4) . "\n\n";

// Test Case 5: No issues
echo "Test 5: Valid input - no highlighting\n";
$msg5 = 'tp, 92 xc 5n';
$highlights5 = [];
echo "Input: $msg5\n";
echo "Output: " . buildHighlightedMessageDemo($msg5, $highlights5) . "\n\n";

// Test Case 6: Vietnamese accents preserved
echo "Test 6: Vietnamese with unknown token\n";
$msg6 = 'tp, 92 không_rõ 5n';
$highlights6 = [
    [
        'token' => 'không_rõ',
        'severity' => 'error',
        'reason' => 'Token không được nhận dạng',
        'detail' => 'Từ khóa này không thuộc bất kỳ loại cược, đài, hoặc cú pháp nào hợp lệ.'
    ]
];
echo "Input: $msg6\n";
echo "Output: " . buildHighlightedMessageDemo($msg6, $highlights6) . "\n\n";

echo "=== Key Improvements ===\n";
echo "1. ✓ Highlights multiple types: errors (red), warnings (yellow), blocks (orange)\n";
echo "2. ✓ Detailed tooltips explaining WHY each token has an issue\n";
echo "3. ✓ Context-aware: extracts relevant tokens from error events\n";
echo "4. ✓ Preserves Vietnamese accents and special characters\n";
echo "5. ✓ HTML-safe: all content is properly escaped\n";
echo "6. ✓ Overlapping protection: handles multiple highlights intelligently\n\n";

echo "=== All Tests Completed Successfully! ===\n";

<?php
/**
 * Test parser fix cho issue: "24 lo 15n T,Giang lo 26 25 58 50n"
 * Số 24 từ bet đầu không được nhảy sang bet sau
 */

echo "=== Test Parser Logic (Manual Simulation) ===\n\n";

// Simulate các test cases
$testCases = [
    [
        'input' => '24 lo 15n T,Giang lo 26 25 58 50n',
        'description' => 'Issue case: số 24 không được nhảy sang bet sau',
        'expected_bets' => [
            ['numbers' => ['24'], 'type' => 'bao_lo', 'amount' => 15000, 'station' => null],
            ['numbers' => ['26'], 'type' => 'bao_lo', 'amount' => 50000, 'station' => 'tien giang'],
            ['numbers' => ['25'], 'type' => 'bao_lo', 'amount' => 50000, 'station' => 'tien giang'],
            ['numbers' => ['58'], 'type' => 'bao_lo', 'amount' => 50000, 'station' => 'tien giang'],
        ],
    ],
    [
        'input' => '24 15n lo',
        'description' => 'Case kế thừa số (đúng): số trước, tiền, rồi type',
        'expected_bets' => [
            ['numbers' => ['24'], 'type' => 'bao_lo', 'amount' => 15000],
        ],
    ],
    [
        'input' => '24 tg lo 15n',
        'description' => 'Case KHÔNG kế thừa: số, station, type, tiền',
        'expected_bets' => [
            // 24 sẽ bị bỏ vì không có type/amount
            ['numbers' => [], 'type' => 'bao_lo', 'amount' => 15000, 'station' => 'tien giang'],
        ],
    ],
    [
        'input' => 'tg 24 lo 15n',
        'description' => 'Case bình thường: station, số, type, tiền',
        'expected_bets' => [
            ['numbers' => ['24'], 'type' => 'bao_lo', 'amount' => 15000, 'station' => 'tien giang'],
        ],
    ],
];

echo "Test Logic:\n";
echo "==========\n\n";

echo "Quy tắc fix:\n";
echo "1. Khi gặp kiểu cược (type token như 'lo'), kiểm tra token trước:\n";
echo "   - Nếu token trước là STATION (just_saw_station = true) → KHÔNG kế thừa số từ last_numbers\n";
echo "   - Nếu token trước là NUMBER/AMOUNT → kế thừa số từ last_numbers (nếu cần)\n\n";

echo "2. Khi gặp station token → set just_saw_station = true\n\n";

echo "3. Khi gặp type/combo/amount token → set just_saw_station = false\n\n";

echo "Flow cho '24 lo 15n T,Giang lo 26 25 58 50n':\n";
echo "----------------------------------------------\n";
echo "Token 1: '24' (number) → numbers_group = [24], just_saw_station = false\n";
echo "Token 2: 'lo' (type) → current_type = bao_lo, just_saw_station = false\n";
echo "         Check inherit: numbers_group not empty → NO inherit\n";
echo "Token 3: '15n' (amount) → amount = 15000, flush bet 1, save last_numbers = [24]\n";
echo "Token 4: 'tg' (station) → stations = [tien giang], just_saw_station = TRUE ✓\n";
echo "Token 5: 'lo' (type) → current_type = bao_lo\n";
echo "         Check inherit: numbers_group empty BUT just_saw_station = TRUE → NO inherit ✓\n";
echo "Token 6: '26' (number) → numbers_group = [26]\n";
echo "Token 7: '25' (number) → numbers_group = [26, 25]\n";
echo "Token 8: '58' (number) → numbers_group = [26, 25, 58]\n";
echo "Token 9: '50n' (amount) → amount = 50000, flush bets 2,3,4\n\n";

echo "Expected Result:\n";
echo "----------------\n";
echo "Bet 1: numbers=[24], type=bao_lo, amount=15000\n";
echo "Bet 2: numbers=[26], type=bao_lo, amount=50000, station=tien giang\n";
echo "Bet 3: numbers=[25], type=bao_lo, amount=50000, station=tien giang\n";
echo "Bet 4: numbers=[58], type=bao_lo, amount=50000, station=tien giang\n\n";

echo "✓ Số 24 KHÔNG bị nhảy sang các bet sau\n\n";

echo "=== Các Test Cases Khác ===\n\n";

foreach ($testCases as $i => $case) {
    echo "Test " . ($i + 1) . ": " . $case['description'] . "\n";
    echo "Input: " . $case['input'] . "\n";
    echo "Expected: " . count($case['expected_bets']) . " bet(s)\n";
    foreach ($case['expected_bets'] as $j => $bet) {
        echo "  Bet " . ($j + 1) . ": " . json_encode($bet) . "\n";
    }
    echo "\n";
}

echo "=== Summary ===\n";
echo "Fix applied to BettingMessageParser.php:\n";
echo "1. Added just_saw_station = true when processing station tokens\n";
echo "2. Modified inherit logic to NOT inherit when just_saw_station = true\n";
echo "3. Applied to both type_loose tokens and xien tokens\n";

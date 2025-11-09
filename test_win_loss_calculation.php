<?php
/**
 * Test script để kiểm tra tính toán thắng thua cho cược đầu và đuôi
 *
 * Yêu cầu:
 * - Cược đầu: tính 2 số cuối của giải 8 (g8)
 * - Cược đuôi: tính 2 số cuối của giải đặc biệt (db)
 */

require __DIR__ . '/vendor/autoload.php';

use App\Models\LotteryResult;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Test Tính toán Thắng Thua ===\n\n";

// Tạo mock data để test
$mockResult = new LotteryResult([
    'draw_date' => '2025-11-02',
    'region' => 'nam',
    'station' => 'tien giang',
    'station_code' => 'tg',
    'prizes' => [
        'db' => ['123456'],  // Giải đặc biệt: 123456 -> 2 số cuối: 56
        'g1' => ['12345'],
        'g2' => ['12345', '67890'],
        'g3' => ['12345', '67890', '11111'],
        'g4' => ['12345', '67890', '11111', '22222'],
        'g5' => ['12345', '67890', '11111', '22222', '33333', '44444'],
        'g6' => ['1234', '5678', '9012'],
        'g7' => ['123', '456', '789'],
        'g8' => ['28'],  // Giải 8: 28 -> 2 số cuối: 28
    ],
    'all_numbers' => ['123456', '12345', '67890', '11111', '22222', '33333', '44444', '1234', '5678', '9012', '123', '456', '789', '28'],
    'db_full' => '123456',
    'db_first2' => '12',
    'db_last2' => '56',
    'db_first3' => '123',
    'db_last3' => '456',
    'tails2_counts' => [],
    'tails3_counts' => [],
    'heads2_counts' => [],
]);

echo "Dữ liệu test:\n";
echo "- Giải đặc biệt: {$mockResult->db_full} (2 số cuối: {$mockResult->db_last2})\n";
echo "- Giải 8: " . ($mockResult->prizes['g8'][0] ?? 'N/A') . "\n";
echo "- G8 Last 2: " . $mockResult->getG8Last2() . "\n\n";

// Test case 1: Cược đầu - match với giải 8
echo "Test 1: Cược đầu với số 28 (match với giải 8)\n";
$result1 = $mockResult->matchDau('28');
echo "  Kết quả: " . ($result1 ? "✓ TRÚNG (đúng)" : "✗ THUA (sai)") . "\n";
echo "  Expected: TRÚNG (vì 28 = 2 số cuối của giải 8)\n\n";

// Test case 2: Cược đầu - không match
echo "Test 2: Cược đầu với số 12 (không match với giải 8)\n";
$result2 = $mockResult->matchDau('12');
echo "  Kết quả: " . ($result2 ? "✗ TRÚNG (sai)" : "✓ THUA (đúng)") . "\n";
echo "  Expected: THUA (vì 12 ≠ 28)\n\n";

// Test case 3: Cược đuôi - match với giải đặc biệt
echo "Test 3: Cược đuôi với số 56 (match với giải đặc biệt)\n";
$result3 = $mockResult->matchDuoi('56');
echo "  Kết quả: " . ($result3 ? "✓ TRÚNG (đúng)" : "✗ THUA (sai)") . "\n";
echo "  Expected: TRÚNG (vì 56 = 2 số cuối của giải đặc biệt)\n\n";

// Test case 4: Cược đuôi - không match
echo "Test 4: Cược đuôi với số 28 (không match với giải đặc biệt)\n";
$result4 = $mockResult->matchDuoi('28');
echo "  Kết quả: " . ($result4 ? "✗ TRÚNG (sai)" : "✓ THUA (đúng)") . "\n";
echo "  Expected: THUA (vì 28 ≠ 56)\n\n";

// Test case 5: Cược đầu với số có padding
echo "Test 5: Cược đầu với số 08 (số có 0 đầu)\n";
$mockResult2 = new LotteryResult([
    'draw_date' => '2025-11-02',
    'region' => 'nam',
    'station' => 'tien giang',
    'station_code' => 'tg',
    'prizes' => [
        'db' => ['123456'],
        'g8' => ['08'],  // Giải 8: 08
    ],
    'db_full' => '123456',
    'db_first2' => '12',
    'db_last2' => '56',
]);

$result5 = $mockResult2->matchDau('08');
echo "  Giải 8: " . ($mockResult2->prizes['g8'][0] ?? 'N/A') . "\n";
echo "  G8 Last 2: " . $mockResult2->getG8Last2() . "\n";
echo "  Kết quả: " . ($result5 ? "✓ TRÚNG (đúng)" : "✗ THUA (sai)") . "\n";
echo "  Expected: TRÚNG (vì 08 = 08)\n\n";

// Summary
echo "=== TÓM TẮT ===\n";
echo "✓ Cược đầu: tính 2 số cuối của GIẢI 8 (g8)\n";
echo "✓ Cược đuôi: tính 2 số cuối của GIẢI ĐẶC BIỆT (db)\n";

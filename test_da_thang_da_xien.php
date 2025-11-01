<?php

echo "=== TEST 1: Đá thẳng (dt) - 1 đài, ghép cặp 2-2 ===\n\n";

// Test case 1: Đá thẳng với số chẵn
$input1 = "tg 11 22 33 44 dt 10n";
echo "Input: $input1\n";
echo "Expected:\n";
echo "  - 1 đài: Tiền Giang\n";
echo "  - 2 vé: (11,22), (33,44)\n";
echo "  - Mỗi vé: amount=10,000\n\n";

// Test case 2: Đá thẳng với số lẻ
$input2 = "bt 11 22 33 dt 5n";
echo "Input: $input2\n";
echo "Expected:\n";
echo "  - 1 đài: Bến Tre\n";
echo "  - 1 vé: (11,22)\n";
echo "  - Warning: Số lẻ, bỏ số 33\n\n";

// Test case 3: Đá thẳng với nhiều đài (ERROR)
$input3 = "tg bt 11 22 dt 10n";
echo "Input: $input3\n";
echo "Expected:\n";
echo "  - ❌ ERROR: Đá thẳng yêu cầu đúng 1 đài\n\n";

echo str_repeat("=", 60) . "\n\n";

echo "=== TEST 2: Đá xiên (dx) - ≥2 đài, C(n,2) combinations ===\n\n";

// Test case 4: Đá xiên 2 đài, 3 số
$input4 = "tn bt 11 22 33 dx 1n";
echo "Input: $input4\n";
echo "Expected:\n";
echo "  - 2 đài: Tây Ninh, Bến Tre\n";
echo "  - 3 vé: (11,22), (11,33), (22,33)\n";
echo "  - station_pairs: [[tn,bt]] (1 cặp)\n";
echo "  - meta.dai_count: 2\n\n";

// Test case 5: Đá xiên 3 đài, 2 số
$input5 = "tg bt ag 11 22 dx 5n";
echo "Input: $input5\n";
echo "Expected:\n";
echo "  - 3 đài: Tiền Giang, Bến Tre, An Giang\n";
echo "  - 1 vé: (11,22)\n";
echo "  - station_pairs: [[tg,bt], [tg,ag], [bt,ag]] (3 cặp)\n";
echo "  - meta.dai_count: 3\n\n";

// Test case 6: Đá xiên với 1 đài (ERROR)
$input6 = "tg 11 22 dx 10n";
echo "Input: $input6\n";
echo "Expected:\n";
echo "  - ❌ ERROR: Đá xiên yêu cầu tối thiểu 2 đài\n\n";

echo str_repeat("=", 60) . "\n\n";

echo "=== TEST 3: Combination Calculations ===\n\n";

// C(n,2) for numbers
function countCombinations($n) {
    return ($n * ($n - 1)) / 2;
}

echo "Đá xiên - Number pairs (C(n,2)):\n";
echo "  2 số → " . countCombinations(2) . " vé\n";
echo "  3 số → " . countCombinations(3) . " vé\n";
echo "  4 số → " . countCombinations(4) . " vé\n";
echo "  5 số → " . countCombinations(5) . " vé\n\n";

echo "Đá xiên - Station pairs (C(m,2)):\n";
echo "  2 đài → " . countCombinations(2) . " cặp đài\n";
echo "  3 đài → " . countCombinations(3) . " cặp đài\n";
echo "  4 đài → " . countCombinations(4) . " cặp đài\n\n";

echo str_repeat("=", 60) . "\n\n";

echo "=== TEST 4: Cost_xac Formulas ===\n\n";

echo "Đá thẳng (1 đài):\n";
echo "  Miền Bắc: amount × số_cặp × 27 × buy_rate\n";
echo "  MT/MN: amount × 2 × 18 × buy_rate\n";
echo "  VD: tg 11 22 33 44 dt 10n (buy_rate=1)\n";
echo "    = 10,000 × 2 (cặp) × 18 × 1\n";
echo "    = 360,000 VND\n\n";

echo "Đá xiên (≥2 đài):\n";
echo "  2 đài: amount × 4 × 18 × buy_rate\n";
echo "  3 đài: amount × 12 × 18 × buy_rate (4×3)\n";
echo "  4 đài: amount × 24 × 18 × buy_rate (4×6)\n";
echo "  VD: tn bt 11 22 33 dx 1n (buy_rate=1, 3 vé)\n";
echo "    Mỗi vé: 1,000 × 4 × 18 × 1 = 72,000 VND\n";
echo "    Tổng 3 vé: 216,000 VND\n\n";

echo str_repeat("=", 60) . "\n\n";

echo "=== TEST 5: Settlement Logic (Đá xiên) ===\n\n";

echo "Điều kiện trúng cho 1 vé (cặp số a,b) với cặp đài (X,Y):\n";
echo "  1. Cả 2 số về cùng 1 đài (X hoặc Y), HOẶC\n";
echo "  2. Mỗi đài về 1 số (X về a, Y về b hoặc ngược lại)\n\n";

echo "Ví dụ: Vé (11,22) với cặp đài (TN, BT)\n";
echo "  ✅ Trúng nếu: TN về 11, BT về 22\n";
echo "  ✅ Trúng nếu: TN về 22, BT về 11\n";
echo "  ✅ Trúng nếu: TN về cả 11 và 22\n";
echo "  ✅ Trúng nếu: BT về cả 11 và 22\n";
echo "  ❌ Không trúng: TN về 11, BT không về 22\n\n";

echo "Với 3 đài (TG, BT, AG) → 3 cặp đài:\n";
echo "  - Cặp (TG,BT): Check điều kiện trúng\n";
echo "  - Cặp (TG,AG): Check điều kiện trúng\n";
echo "  - Cặp (BT,AG): Check điều kiện trúng\n";
echo "  Trúng nếu BẤT KỲ cặp đài nào thỏa điều kiện\n\n";

echo "✅ All test cases explained!\n";

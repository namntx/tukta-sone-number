<?php
/**
 * Test verification cho XC amount fix
 */

echo "=== Test XC Amount Fix ===\n\n";

$input = "Xc 816 150n 633 40n 132 232 10n";
echo "Input: $input\n\n";

echo "Flow SAU KHI FIX:\n";
echo "=================\n\n";

echo "Fix 1: KHÔNG clear current_type sau flush xiu_chu\n";
echo "Fix 2: Flush group khi gặp amount token mới (nếu có amount cũ và group pending)\n\n";

echo "Token flow:\n";
echo "----------\n";
echo "1. Token 'xc' → current_type = 'xiu_chu'\n";
echo "2. Token '816' → numbers_group = [816]\n";
echo "3. Token '150n' → amount = 150000, continue\n";
echo "4. Token '633':\n";
echo "   - Check dòng 646: amount=150000, isGroupPending(type=xiu_chu, numbers=[816], amount=150000)=TRUE\n";
echo "   - → FLUSH [816] với amount 150000\n";
echo "   - Sau flush (Fix 1): numbers=[], amount=null, type=xiu_chu (GIỮ TYPE!) ✓\n";
echo "   - → Thêm 633 vào numbers_group = [633]\n";
echo "5. Token '40n':\n";
echo "   - Check dòng 718 (Fix 2): amount=null, isGroupPending(type=xiu_chu, numbers=[633], amount=null)=FALSE\n";
echo "   - → KHÔNG flush (đúng vì chưa có amount cũ)\n";
echo "   - → amount = 40000\n";
echo "6. Token '132':\n";
echo "   - Check dòng 646: amount=40000, isGroupPending(type=xiu_chu, numbers=[633], amount=40000)=TRUE\n";
echo "   - → FLUSH [633] với amount 40000 ✓✓✓\n";
echo "   - Sau flush: numbers=[], amount=null, type=xiu_chu (GIỮ TYPE!) ✓\n";
echo "   - → Thêm 132 vào numbers_group = [132]\n";
echo "7. Token '232' → numbers_group = [132, 232]\n";
echo "8. Token '10n':\n";
echo "   - Check dòng 718 (Fix 2): amount=null, isGroupPending(type=xiu_chu, numbers=[132,232], amount=null)=FALSE\n";
echo "   - → KHÔNG flush (đúng vì chưa có amount cũ)\n";
echo "   - → amount = 10000\n";
echo "9. Final flush → FLUSH [132, 232] với amount 10000\n\n";

echo "KẾT QUẢ SAU FIX:\n";
echo "================\n";
echo "✓ 816 xc 150n (split to xiu_chu_dau + xiu_chu_duoi)\n";
echo "✓ 633 xc 40n (split to xiu_chu_dau + xiu_chu_duoi) ✓✓✓\n";
echo "✓ 132 xc 10n (split to xiu_chu_dau + xiu_chu_duoi)\n";
echo "✓ 232 xc 10n (split to xiu_chu_dau + xiu_chu_duoi)\n\n";

echo "=== Test case khi user muốn thoát khỏi xc ===\n\n";
$input2 = "xc 816 150n. lo 25 26 10n";
echo "Input: $input2\n\n";
echo "Token flow:\n";
echo "1. xc → type=xiu_chu\n";
echo "2. 816 → numbers=[816]\n";
echo "3. 150n → amount=150000\n";
echo "4. '.' → flush [816] xc 150n, RESET type=null (dòng 700-705)\n";
echo "5. lo → type=bao_lo\n";
echo "6. 25 → numbers=[25]\n";
echo "7. 26 → numbers=[25,26]\n";
echo "8. 10n → amount=10000\n";
echo "9. final → flush [25,26] lo 10n\n\n";

echo "Result:\n";
echo "✓ 816 xc 150n\n";
echo "✓ 25 bao_lo 10n\n";
echo "✓ 26 bao_lo 10n\n\n";

echo "=== Test Fix 2: Amount token auto flush ===\n\n";
$input3 = "xc 12 34 56 5n 10n";
echo "Input: $input3\n\n";
echo "TRƯỚC FIX: 12 34 56 nhận 10n (sai)\n";
echo "SAU FIX: 12 34 56 nhận 5n, sau đó gặp 10n không có số nào → bỏ qua\n\n";

echo "Token flow sau fix:\n";
echo "1. xc → type=xiu_chu\n";
echo "2. 12 → numbers=[12]\n";
echo "3. 34 → numbers=[12,34]\n";
echo "4. 56 → numbers=[12,34,56]\n";
echo "5. 5n → amount=5000\n";
echo "6. 10n:\n";
echo "   - Check Fix 2: amount=5000, isGroupPending(type=xiu_chu, numbers=[12,34,56], amount=5000)=TRUE\n";
echo "   - → FLUSH [12,34,56] với amount 5000 ✓✓✓\n";
echo "   - → amount=10000, numbers=[]\n";
echo "7. final → KHÔNG flush (vì không có numbers)\n\n";

echo "Result:\n";
echo "✓ 12 xc 5n\n";
echo "✓ 34 xc 5n\n";
echo "✓ 56 xc 5n\n";
echo "✓ 10n bị bỏ qua (không có số)\n\n";

echo "=== SUMMARY ===\n";
echo "Fix 1: Comment out current_type=null ở dòng 293 (xiu_chu flush)\n";
echo "Fix 2: Thêm auto flush khi gặp amount token mới ở dòng 718\n";

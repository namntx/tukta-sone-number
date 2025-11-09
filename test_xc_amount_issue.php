<?php
/**
 * Test xỉu chủ (xc) amount issue
 *
 * Input: "Xc 816 150n 633 40n 132 232 10n"
 *
 * Expected:
 * - 816 xc 150n
 * - 633 xc 40n
 * - 132 xc 10n
 * - 232 xc 10n
 *
 * Actual issue: 633 nhận 10n thay vì 40n
 *
 * Root cause:
 * Khi gặp amount token (40n, 10n), parser chỉ update ctx['amount']
 * mà KHÔNG flush group hiện tại nếu đã có amount trước đó.
 */

echo "=== Test XC Amount Issue ===\n\n";

$input = "Xc 816 150n 633 40n 132 232 10n";
echo "Input: $input\n\n";

echo "Flow hiện tại (BUG):\n";
echo "-------------------\n";
echo "1. Token 'xc' → current_type = 'xiu_chu'\n";
echo "2. Token '816' → numbers_group = [816]\n";
echo "3. Token '150n' → amount = 150000, continue (KHÔNG flush)\n";
echo "4. Token '633':\n";
echo "   - Check: amount=150000, isGroupPending=true\n";
echo "   - → FLUSH [816] với amount 150000 ✓\n";
echo "   - → Thêm 633 vào numbers_group = [633]\n";
echo "5. Token '40n' → amount = 40000, continue (KHÔNG flush) ❌\n";
echo "6. Token '132':\n";
echo "   - Check: amount=40000, isGroupPending=true\n";
echo "   - → FLUSH [633] với amount 40000 ✓\n";
echo "   - → Thêm 132 vào numbers_group = [132]\n";
echo "7. Token '232' → numbers_group = [132, 232]\n";
echo "8. Token '10n' → amount = 10000, continue (KHÔNG flush) ❌\n";
echo "   - BUG: amount bị ghi đè thành 10000!\n";
echo "9. Final flush → [132, 232] với amount 10000\n\n";

echo "Result (BUG):\n";
echo "- 816 xc 150n ✓\n";
echo "- 633 xc 40n ✓\n";
echo "- 132, 232 xc 10n (đúng vì cùng group)\n\n";

echo "Wait... có vẻ logic đúng? Hãy xem lại...\n\n";

echo "Phân tích lại:\n";
echo "-------------\n";
echo "Input: Xc 816 150n 633 40n 132 232 10n\n\n";

echo "Có thể user muốn:\n";
echo "- 816 xc 150n\n";
echo "- 633 xc 40n (riêng)\n";
echo "- 132 232 xc 10n (cùng group)\n\n";

echo "Nhưng parser hiểu:\n";
echo "- 816 xc 150n\n";
echo "- 633 xc 40n (flush khi gặp 132)\n";
echo "- 132 232 xc 10n\n\n";

echo "VẬY LÀ ĐÚNG! Nhưng user báo bug 'K nhận 40n mà nhận 10n'\n\n";

echo "Có thể vấn đề là:\n";
echo "1. Parser KHÔNG flush khi gặp amount mới?\n";
echo "2. Hoặc có race condition gì đó?\n\n";

echo "Hãy kiểm tra kỹ hơn...\n\n";

echo "=== Kiểm tra logic flush khi gặp amount token ===\n\n";
echo "Code hiện tại (dòng 711-718):\n";
echo "```php\n";
echo "if (preg_match('/^(\d+(?:\.\d+)?)(n|k)\$/', \$tok, \$m)) {\n";
echo "    \$ctx['amount'] = (int)round((float)\$m[1] * 1000);\n";
echo "    // KHÔNG có flush ở đây!\n";
echo "    continue;\n";
echo "}\n";
echo "```\n\n";

echo "Vấn đề: Khi gặp amount token MỚI, không check xem có group pending với amount CŨ không!\n\n";

echo "FIX: Trước khi update amount, phải flush group nếu:\n";
echo "- Đã có amount cũ\n";
echo "- Có group pending\n\n";

echo "Code fix:\n";
echo "```php\n";
echo "if (preg_match('/^(\d+(?:\.\d+)?)(n|k)\$/', \$tok, \$m)) {\n";
echo "    \$newAmount = (int)round((float)\$m[1] * 1000);\n";
echo "    \n";
echo "    // FLUSH nếu đã có amount cũ và group pending\n";
echo "    if (!empty(\$ctx['amount']) && \$isGroupPending(\$ctx)) {\n";
echo "        \$flushGroup(\$outBets, \$ctx, \$events, 'amount_token_auto_flush');\n";
echo "    }\n";
echo "    \n";
echo "    \$ctx['amount'] = \$newAmount;\n";
echo "    continue;\n";
echo "}\n";
echo "```\n\n";

echo "Với fix này:\n";
echo "5. Token '40n':\n";
echo "   - Check: amount=null (đã flush ở step 4), group=[633]\n";
echo "   - → KHÔNG flush\n";
echo "   - → amount = 40000\n";
echo "8. Token '10n':\n";
echo "   - Check: amount=40000 (từ step 5), group=[633], pending=true\n";
echo "   - → FLUSH [633] với amount 40000 ✓✓✓\n";
echo "   - → amount = 10000\n";
echo "9. Final flush → [132, 232] với amount 10000\n\n";

echo "Result (FIXED):\n";
echo "- 816 xc 150n ✓\n";
echo "- 633 xc 40n ✓✓✓\n";
echo "- 132, 232 xc 10n ✓\n";

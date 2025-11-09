<?php
/**
 * Test dd combo token after xc issue
 *
 * Input: "T, Giang . 47 46 dd200n Xc 863 50n"
 *
 * Expected:
 * - 47 dau_duoi 200n
 * - 46 dau_duoi 200n
 * - 863 xiu_chu 50n
 *
 * Actual BUG:
 * - 47 46 nhận xiu_chu 200n (sai!)
 */

echo "=== Test DD After XC Issue ===\n\n";

$input = "T, Giang . 47 46 dd200n Xc 863 50n";
echo "Input: $input\n\n";

echo "Token flow (SAU FIX XC TRƯỚC ĐÓ - CÓ BUG MỚI):\n";
echo "==============================================\n\n";

echo "Context trước input này:\n";
echo "- Giả sử trước đó có: 'xc 384 ... 25n.'\n";
echo "- Sau flush xc (fix trước): current_type=xiu_chu (GIỮ type!) ✓\n";
echo "- Sau token '.': reset last_type, stations, BUT current_type VẪN là xiu_chu! ❌\n\n";

echo "Token flow:\n";
echo "1. 'T, Giang' (tg) → station=tien giang, just_saw_station=true\n";
echo "2. '.' → flush, reset last_type, stations, NHƯNG KHÔNG reset current_type\n";
echo "   → current_type VẪN là xiu_chu! ❌\n";
echo "3. '47' → numbers=[47], type vẫn là xiu_chu\n";
echo "4. '46' → numbers=[47,46], type vẫn là xiu_chu\n";
echo "5. 'dd200n' → combo token, check current_type === 'xiu_chu' → TRUE! ❌\n";
echo "   → Code dòng 724-729: if current_type === xiu_chu\n";
echo "   → if code === 'dd' → xc_dd_amount = 200000\n";
echo "   → BUG: Treat dd200n as xiu_chu combo instead of dau_duoi! ❌\n\n";

echo "Root Cause:\n";
echo "-----------\n";
echo "Sau fix trước (comment out current_type=null ở dòng 293-294),\n";
echo "xiu_chu type được GIỮ LẠI sau flush (đúng cho continuous xc).\n";
echo "NHƯNG token '.' (dấu chấm) KHÔNG reset current_type!\n\n";

echo "Dòng 694-709: Token '.' chỉ reset:\n";
echo "- dai_count\n";
echo "- dai_capture_remaining\n";
echo "- stations\n";
echo "- last_numbers\n";
echo "- last_type\n";
echo "NHƯNG KHÔNG reset current_type! ❌\n\n";

echo "FIX:\n";
echo "----\n";
echo "Thêm dòng reset current_type khi gặp token '.':\n\n";
echo "```php\n";
echo "if (\$tok === '.') {\n";
echo "    if (\$isGroupPending(\$ctx)) \$flushGroup(...);\n";
echo "    \n";
echo "    // Reset all context for new betting slip\n";
echo "    \$ctx['dai_count'] = null;\n";
echo "    \$ctx['dai_capture_remaining'] = 0;\n";
echo "    \$ctx['stations'] = [];\n";
echo "    \$ctx['last_numbers'] = [];\n";
echo "    \$ctx['last_type'] = null;\n";
echo "    \$ctx['current_type'] = null; // ← THÊM DÒNG NÀY ✓\n";
echo "    \n";
echo "    \$ctx['just_saw_station'] = false;\n";
echo "    continue;\n";
echo "}\n";
echo "```\n\n";

echo "Với fix này:\n";
echo "2. '.' → flush, reset last_type, stations, AND current_type=null ✓\n";
echo "3. '47' → numbers=[47], type=null\n";
echo "4. '46' → numbers=[47,46], type=null\n";
echo "5. 'dd200n' → combo token, check current_type === 'xiu_chu' → FALSE ✓\n";
echo "   → targetType = 'dau_duoi'\n";
echo "   → current_type = 'dau_duoi'\n";
echo "   → amount = 200000\n";
echo "   → Flush [47,46] dau_duoi 200n ✓✓✓\n";
echo "6. 'Xc' → type = 'xiu_chu'\n";
echo "7. '863' → numbers=[863]\n";
echo "8. '50n' → amount = 50000\n";
echo "9. final → flush [863] xc 50n\n\n";

echo "Result (FIXED):\n";
echo "✓ 47 dau_duoi 200n\n";
echo "✓ 46 dau_duoi 200n\n";
echo "✓ 863 xiu_chu 50n\n\n";

echo "=== Impact Analysis ===\n\n";
echo "Fix trước (giữ current_type sau flush xiu_chu) vẫn đúng cho:\n";
echo "- 'xc 12 5n 34 10n' → continuous xc bets (KHÔNG có dấu chấm)\n\n";

echo "Fix mới (reset current_type khi gặp '.') đảm bảo:\n";
echo "- Dấu chấm (.) là boundary rõ ràng giữa các phiếu cược\n";
echo "- Sau dấu chấm, context reset hoàn toàn\n";
echo "- Không bị contamination từ phiếu trước\n";

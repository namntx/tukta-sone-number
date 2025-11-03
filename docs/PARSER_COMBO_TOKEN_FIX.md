# Parser Combo Token Fix - Ngăn Kế Thừa Số Sai

## Vấn Đề

**Input:** `23 12 49 20 dd10n 293 120 lo 20n 20 29 dt 10n`

**Ý định:**
1. `23 12 49 20 dd10n` → Đầu+Đuôi cho 4 số (23,12,49,20), mỗi số 10n
2. `293 120 lo 20n` → Bao lô cho 2 số (293,120), mỗi số 20n
3. `20 29 dt 10n` → Đá thẳng cặp (20,29), 10n

**Output SAI (trước fix):**
- 19 bets tổng cộng
- `lo 20n` kế thừa TOÀN BỘ số từ đầu: [23,12,49,20,293,120] → SAI!
- `dt 10n` kế thừa TOÀN BỘ số từ đầu: [23,12,49,20,293,120,20,29] → SAI!

### Events Debug (trước fix):

```json
{
    "kind": "pair_combo",
    "token": "dd10n",
    "type": "dau_duoi",
    "amount": 10000
},
{
    "kind": "number",
    "value": "293"
},
{
    "kind": "number",
    "value": "120"
},
{
    "kind": "type_switch_flush"
},
{
    "kind": "inherit_numbers_for_type",
    "type": "bao_lo",
    "numbers": ["23","12","49","20","293","120"]  ← SAI!
}
```

## Nguyên Nhân

### Vấn đề 1: Combo Token Không Flush Ngay

**Combo token** (dd10n, lo20n, d10n) là instruction hoàn chỉnh (type + amount), nhưng **KHÔNG flush ngay** sau khi set.

**Code cũ (dòng 564-610):**
```php
if (preg_match('/^(d|dd|lo)(\d+)(n|k)$/', $tok, $m)) {
    $code = $m[1]; $amt = (int)$m[2]*1000;

    // ... xử lý ...

    if ($targetType==='dau_duoi') {
        $ctx['current_type']='dau_duoi';
        $ctx['amount']=$amt;
        $addEvent($events,'pair_combo',['token'=>$tok,'type'=>'dau_duoi','amount'=>$amt]);
    }

    $ctx['just_saw_station'] = false;
    continue;  // ← KHÔNG FLUSH!
}
```

**Hậu quả:**
1. `23 12 49 20 dd10n` → Set type + amount nhưng KHÔNG flush
2. `293 120` → Tiếp tục thêm vào cùng `numbers_group`
3. `numbers_group` = [23,12,49,20,293,120] → SAI!

### Vấn đề 2: last_numbers Không Được Cập Nhật Đúng

**Code cũ:**
- Mỗi lần thêm số: `$ctx['last_numbers'] = $ctx['numbers_group'];` (dòng 541)
- Khi flush: **KHÔNG cập nhật** `last_numbers`

**Hậu quả:**
- Sau flush `dd10n`, `last_numbers` vẫn giữ giá trị cũ
- Khi kế thừa (Rule 1), lấy số từ `last_numbers` → Lấy nhầm số cũ

## Giải Pháp

### Fix 1: Flush Ngay Sau Combo Token

**Code mới (dòng 604-607):**
```php
if (preg_match('/^(d|dd|lo)(\d+)(n|k)$/', $tok, $m)) {
    // ... set type + amount ...

    // QUAN TRỌNG: Flush ngay sau combo token để không kéo số tiếp theo vào cùng group
    if ($isGroupPending($ctx)) {
        $flushGroup($outBets, $ctx, $events, 'combo_token_auto_flush');
    }

    $ctx['just_saw_station'] = false;
    continue;
}
```

**Kết quả:**
- `23 12 49 20 dd10n` → Flush NGAY sau dd10n
- `293 120` → Thuộc group MỚI, không bị trộn với số cũ

### Fix 2: Lưu last_numbers Khi Flush

**Code mới (dòng 214-218):**
```php
$flushGroup = function(array &$outBets, array &$ctx, array &$events, ?string $reason=null) use (...) {
    if ($reason) $addEvent($events, $reason);

    $numbers = array_values(array_unique($ctx['numbers_group'] ?? []));
    // ...

    // LƯU last_numbers = numbers của group này TRƯỚC KHI flush
    // Để Rule 1 hoạt động: cược sau không có số thì kế thừa số từ group vừa flush
    if (!empty($numbers)) {
        $ctx['last_numbers'] = $numbers;
    }

    // ... reset numbers_group ...
};
```

**Kết quả:**
- Mỗi lần flush → Lưu số của group hiện tại vào `last_numbers`
- Group tiếp theo nếu cần kế thừa → Lấy từ `last_numbers` của group VỪA flush (không phải tất cả số từ đầu)

## Trace Logic Sau Fix

**Input:** `23 12 49 20 dd10n 293 120 lo 20n 20 29 dt 10n`

### Bước 1: `23 12 49 20 dd10n`
```
Thêm số: numbers_group = [23,12,49,20]
Gặp dd10n → Set type='dau_duoi', amount=10000
Flush ngay:
  - Lưu: last_numbers = [23,12,49,20]
  - Emit: 8 bets (4 đầu + 4 đuôi)
  - Reset: numbers_group = [], current_type = null
```

### Bước 2: `293 120 lo 20n`
```
Thêm số: numbers_group = [293,120]
Gặp lo → Set type='bao_lo' (không flush vì current_type=null)
Gặp 20n → Set amount=20000
... (đợi token tiếp theo để flush)
```

### Bước 3: `20 29 dt 10n`
```
Gặp 20 → Thêm số → Trigger type_switch_flush vì gặp số mới
Flush group trước (lo 20n):
  - Lưu: last_numbers = [293,120]
  - Emit: 2 bets bao lô
  - Reset: numbers_group = [], current_type = null

Thêm số: numbers_group = [20,29]
Gặp dt → Set type='da_thang'
Gặp 10n → Set amount=10000
... (đợi final_flush)

Final flush:
  - Lưu: last_numbers = [20,29]
  - Emit: 1 bet đá thẳng (20,29)
```

## Kết Quả Sau Fix

**Output đúng:**
- **Group 1:** `23 12 49 20 dd10n` → 8 bets (đầu+đuôi)
- **Group 2:** `293 120 lo 20n` → 2 bets (bao lô 3 số)
- **Group 3:** `20 29 dt 10n` → 1 bet (đá thẳng)
- **Tổng:** 11 bets

**Events mới:**
```json
{
    "kind": "pair_combo",
    "token": "dd10n",
    "type": "dau_duoi",
    "amount": 10000
},
{
    "kind": "combo_token_auto_flush"  ← FLUSH NGAY!
},
{
    "kind": "number",
    "value": "293"
},
{
    "kind": "number",
    "value": "120"
}
```

## Tuân Thủ Rules

### Rule 0: Xử lý tuần tự trái → phải ✅
- Flush ngay sau combo token → Đảm bảo xử lý từng group độc lập

### Rule 1: Kế thừa số gần nhất ✅
- `last_numbers` lưu số của group VỪA flush
- Ví dụ: `23 12 lo 10n d 5n`
  - Group 1: `23 12 lo 10n` → Flush → last_numbers=[23,12]
  - Group 2: `d 5n` → Kế thừa [23,12] cho đầu ✅

### Rule 2: Kế thừa đài gần nhất ✅
- Không thay đổi logic xử lý đài

### Rule 3: Format linh hoạt ✅
- Hỗ trợ: `đài + số + kiểu + tiền`, `kiểu + số + tiền`, `số + combo_token`

### Rule 4: Kế thừa 2dai/3dai ✅
- Không thay đổi logic xử lý đài

### Rule 5: Đài mặc định theo miền ✅
- Không thay đổi logic default station

## Files Modified

- `app/Services/BettingMessageParser.php`:
  - **Dòng 214-218:** Lưu `last_numbers` khi flush
  - **Dòng 604-607:** Flush ngay sau combo token

## Testing

### Test Case 1: Combo Token Flush
```
Input: 23 12 49 20 dd10n 293 120 lo 20n
Expected:
  - Group 1: [23,12,49,20] với dau_duoi
  - Group 2: [293,120] với bao_lo
Result: ✅ PASS
```

### Test Case 2: Kế Thừa Số (Rule 1)
```
Input: 23 12 lo 10n d 5n
Expected:
  - Group 1: [23,12] với bao_lo
  - Group 2: [23,12] kế thừa cho đầu
Result: ✅ PASS
```

### Test Case 3: Nhiều Group Liên Tiếp
```
Input: 11 22 dd5n 33 44 lo10n 55 66 dt15n
Expected:
  - 3 groups độc lập, không trộn số
Result: ✅ PASS
```

## Summary

| Vấn đề | Trước Fix | Sau Fix |
|--------|-----------|---------|
| Combo token flush | ❌ Không flush ngay | ✅ Flush ngay |
| last_numbers update | ❌ Không cập nhật khi flush | ✅ Lưu số của group vừa flush |
| Kế thừa số | ❌ Kế thừa tất cả số từ đầu | ✅ Kế thừa số của group gần nhất |
| Tổng bets (test input) | ❌ 19 bets (sai) | ✅ 11 bets (đúng) |
| Tuân thủ 5 Rules | ❌ Sai Rule 0, 1 | ✅ Tuân thủ đầy đủ |

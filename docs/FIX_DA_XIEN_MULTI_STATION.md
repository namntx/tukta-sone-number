# Đá Xiên / Đá Thẳng - Multi-Station Fix

## Vấn đề

Input: `14,27,72 tn bt dx 0.8n`

**Trước khi fix:**
```json
{
  "station": "ben tre",  // ❌ Chỉ 1 đài (thiếu Tây Ninh!)
  "numbers": ["14", "27", "72"],
  "type": "Đá xiên",
  "meta": {}  // ❌ Thiếu dai_count
}
```

**Nguyên nhân:**
- Logic dòng 573 trong `BettingMessageParser.php` chỉ cộng dồn stations khi **chưa có số**
- Với `14 27 72 tn bt` → đã có số → mỗi station mới sẽ **RESET** danh sách stations
- Kết quả: chỉ còn station cuối cùng (ben tre)

## Giải pháp

### 1. Sửa logic multi-station

**File:** `app/Services/BettingMessageParser.php:571-579`

**Trước:**
```php
// Chỉ cộng dồn khi CHƯA có số VÀ chưa có type
if (empty($ctx['numbers_group']) && ($ctx['current_type'] === null)) {
    $ctx['stations'][] = $name;
}
```

**Sau:**
```php
// Cộng dồn khi CHƯA có type (bất kể đã có số)
if ($ctx['current_type'] === null) {
    $ctx['stations'][] = $name;
}
```

**Logic:**
- Cho phép `14 27 72 tn bt dx` → cộng dồn 2 đài trước khi gặp type `dx`
- Khi gặp `dx` → type được set → stations không còn cộng dồn nữa

### 2. Set meta.dai_count tự động

**File:** `app/Services/BettingMessageParser.php:309-315`

```php
// Đá xiên / Đá thẳng: set meta.dai_count từ số lượng stations
if (in_array($type, ['da_xien', 'da_thang'], true)) {
    $stationCount = count($ctx['stations'] ?? []);
    if ($stationCount > 1) {
        $ctx['meta']['dai_count'] = $stationCount;
    }
}
```

**Tự động set:**
- `tn bt dx` → `meta.dai_count = 2`
- `tn bt ag dx` → `meta.dai_count = 3`
- `tn bt ag dl dx` → `meta.dai_count = 4`

## Kết quả

**Sau khi fix:**
```json
{
  "station": "tay ninh + ben tre",  // ✅ 2 đài
  "numbers": ["14", "27", "72"],
  "type": "Đá xiên",
  "amount": 8000,
  "meta": {
    "dai_count": 2  // ✅ Tự động set
  },
  "cost_xac": 576000  // ✅ 8000 × 4 × 18 × 1
}
```

## Cú pháp hỗ trợ

### Đá xiên (2-4 đài)

```
14 27 72 tn bt dx 10n           → 2 đài (Tây Ninh + Bến Tre)
14 27 72 tn bt ag dx 10n        → 3 đài (Tây Ninh + Bến Tre + An Giang)
14 27 72 tn bt ag dl dx 10n     → 4 đài (4 đài)
```

### Đá thẳng (multi-station)

```
14 27 72 tn bt dt 10n           → 2 đài
```

### Với `Ndai` syntax (vẫn hoạt động)

```
2d tn bt 14 27 72 dx 10n        → 2 đài (cú pháp cũ)
3d tn bt ag 14 27 72 dx 10n     → 3 đài (cú pháp cũ)
```

## Cost_xac Formula

**Đá xiên:**
- 2 đài: `amount × 4 × 18 × buy_rate`
- 3 đài: `amount × 12 × 18 × buy_rate` (4 × 3)
- 4 đài: `amount × 24 × 18 × buy_rate` (4 × 6)

**Ví dụ:**
```
Input: 14 27 72 tn bt dx 10n (buy_rate = 1)
dai_count: 2
multiplier: 4
cost_xac = 10,000 × 4 × 18 × 1 = 720,000 VND
```

## Test Cases

✅ `14 27 72 tn bt dx 10n` → 2 đài, dai_count=2
✅ `14 27 tn bt ag dx 5n` → 3 đài, dai_count=3
✅ `14 27 72 tn dt 10n` → 1 đài (đá thẳng có thể 1 đài)
✅ `2d tn bt 14 27 dx 10n` → 2 đài (cú pháp Ndai)

## Breaking Changes

❌ KHÔNG có breaking changes

**Backward compatible:**
- Cú pháp cũ `2d tn bt ...` vẫn hoạt động
- Single station vẫn OK: `tn 14 27 dx 10n`
- Chỉ fix logic cộng dồn multi-station

## Related Files

- `app/Services/BettingMessageParser.php` (2 thay đổi)
- `app/Services/BetPricingService.php` (đã có logic tính cost_xac đúng)
- `test_da_xien_multi_station.php` (test file)

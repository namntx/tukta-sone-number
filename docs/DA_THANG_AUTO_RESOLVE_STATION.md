# Đá Thẳng Auto-Resolve Station Fix

## Vấn Đề

**Input:** `20 29 dt 10n`

**Kết quả SAI:**
- 0 bets
- Error: "Đá thẳng yêu cầu đúng 1 đài" (got=0, expected=1)
- Đá thẳng bị REJECT hoàn toàn

**Nguyên nhân:**
- Người dùng không nhập đài trong input
- Đá thẳng validate `stationCount !== 1` TRƯỚC KHI auto-resolve
- → Reject ngay → KHÔNG tạo bet

## Quy Tắc Bị Vi Phạm

### Rule 2: Kế thừa đài gần nhất
> "Khi hết 1 phiếu cược, phiếu cược tiếp theo vẫn chưa xuất hiện đài mới thì vẫn lấy đài gần nhất để gán cho cược tiếp theo"

### Rule 5: Đài mặc định theo miền
> "Nếu bắt đầu tin nhắn không có đài thì mặc định lấy đài chính của ngày đó theo miền"

## So Sánh Với Các Type Khác

### Bao Lô (ĐÚNG - có auto-resolve)
```
Input: 23 12 lo 10n
→ Không có đài trong ctx['stations']
→ emitBet auto-resolve: station = "tien giang" (đài chính Nam)
→ 2 bets bao lô ✅
```

### Đá Thẳng (SAI - trước fix)
```
Input: 20 29 dt 10n
→ Không có đài trong ctx['stations']
→ Validate TRƯỚC: stationCount !== 1 → REJECT
→ 0 bets ❌ (không đến được emitBet để auto-resolve)
```

## Giải Pháp

**Auto-resolve station TRƯỚC KHI validate** - tuân thủ Rule 2 và Rule 5.

### Code Fix (dòng 326-343)

```php
if ($type === 'da_thang') {
    $stationCount = count($ctx['stations'] ?? []);

    // Auto-resolve station nếu chưa có (Rule 2, Rule 5)
    if ($stationCount === 0) {
        // Lấy đài chính từ lịch (theo date + region)
        try {
            $mainStation = $this->scheduleService->getNStations(
                1,
                $ctx['betting_date'] ?? date('Y-m-d'),
                $region
            );
            if (!empty($mainStation)) {
                $ctx['stations'] = [$mainStation[0]];
                $stationCount = 1;
                $addEvent($events, 'da_thang_auto_resolve_station', [
                    'region' => $region,
                    'date' => $ctx['betting_date'] ?? date('Y-m-d'),
                    'resolved_station' => $mainStation[0],
                ]);
            }
        } catch (\Exception $e) {
            // Ignore error, validation sẽ reject
        }
    }

    // Validate: bắt buộc 1 đài (sau khi đã auto-resolve)
    if ($stationCount !== 1) {
        $addEvent($events, 'error_da_thang_wrong_station_count', [
            'expected' => 1,
            'got' => $stationCount,
            'message' => 'Đá thẳng yêu cầu đúng 1 đài'
        ]);
        $ctx['numbers_group']=[]; $ctx['amount']=null; $ctx['meta']=[]; $ctx['current_type']=null;
        return;
    }

    // ... tiếp tục xử lý đá thẳng ...
}
```

## Flow Sau Fix

### Input: `20 29 dt 10n` (không có đài)

```
1. Gặp 20 29 → numbers_group = [20, 29]
2. Gặp dt → Set type='da_thang'
3. Gặp 10n → Set amount=10000
4. Final flush:
   a. Check stations: ctx['stations'] = [] (empty)
   b. Auto-resolve: getNStations(1, '2025-11-02', 'nam')
      → mainStation = ['tien giang']
   c. Set: ctx['stations'] = ['tien giang']
   d. Validate: stationCount = 1 ✅
   e. Emit: 1 bet đá thẳng (20,29) với station='tien giang' ✅
```

### Input: `23 12 49 20 dd10n 293 120 lo 20n 20 29 dt 10n`

```
Group 1: dd10n → Flush → stations vẫn = []
Group 2: lo 20n → Flush → stations vẫn = []
Group 3: dt 10n
  a. ctx['stations'] = [] (empty)
  b. Auto-resolve: stations = ['tien giang']
  c. Emit: 1 bet đá thẳng ✅
```

## Kế Thừa Station (Rule 2)

**Stations KHÔNG bị reset trong flush** - chỉ reset `numbers_group`, `amount`, `meta`, `current_type`.

Vậy nếu có input:
```
tg 20 29 dt 10n 30 40 dt 5n
```

Flow:
```
1. tg → Set stations=['tien giang']
2. 20 29 dt 10n → Emit với station='tien giang', stations vẫn=['tien giang']
3. 30 40 dt 5n → Kế thừa stations=['tien giang'] ✅ (Rule 2)
```

## Test Cases

### Test 1: Đá thẳng không có đài (auto-resolve)
```
Input: 20 29 dt 10n
Region: nam
Date: 2025-11-02

Expected:
- Auto-resolve: station = 'tien giang' (đài chính Nam)
- 1 bet: numbers=[20,29], type='da_thang', station='tien giang'

Events:
- da_thang_auto_resolve_station: {
    region: 'nam',
    date: '2025-11-02',
    resolved_station: 'tien giang'
  }
```

### Test 2: Đá thẳng có đài rồi (không auto-resolve)
```
Input: tg 20 29 dt 10n

Expected:
- stations=['tien giang'] từ đầu
- KHÔNG auto-resolve
- 1 bet với station='tien giang'

Events:
- KHÔNG có da_thang_auto_resolve_station
```

### Test 3: Nhiều đá thẳng liên tiếp (kế thừa station)
```
Input: tg 20 29 dt 10n 30 40 dt 5n

Expected:
- Bet 1: [20,29] với station='tien giang'
- Bet 2: [30,40] với station='tien giang' (kế thừa) ✅ Rule 2

Events:
- Chỉ resolve 1 lần ở đầu
```

### Test 4: Full input từ issue
```
Input: 23 12 49 20 dd10n 293 120 lo 20n 20 29 dt 10n
Region: nam

Expected:
- Group 1: dd10n → 8 bets
- Group 2: lo 20n → 2 bets
- Group 3: dt 10n → 1 bet đá thẳng ✅ (auto-resolve station)

Total: 11 bets
```

## Đá Xiên (Không Auto-Resolve)

**Đá xiên yêu cầu ≥2 đài** - KHÔNG thể auto-resolve (vì chỉ có 1 đài chính/ngày).

Vậy đá xiên **BẮT BUỘC người dùng phải nhập đài**:
```
tn bt 11 22 33 dx 10n  ← Đúng (2 đài)
11 22 33 dx 10n         ← SAI (thiếu đài, không auto-resolve)
```

Đá xiên chỉ hoạt động khi:
1. Người dùng nhập đài: `tn bt 11 22 dx 10n`
2. Hoặc dùng Ndai: `2dai tn bt 11 22 dx 10n`

## Summary

| Vấn đề | Trước Fix | Sau Fix |
|--------|-----------|---------|
| Đá thẳng không có đài | ❌ Reject (0 bets) | ✅ Auto-resolve từ schedule |
| Tuân thủ Rule 2 (kế thừa đài) | ✅ OK (nếu có đài) | ✅ OK |
| Tuân thủ Rule 5 (đài mặc định) | ❌ Không áp dụng | ✅ Áp dụng |
| Đá xiên không có đài | ❌ Reject | ❌ Reject (bắt buộc ≥2 đài) |
| Event mới | - | `da_thang_auto_resolve_station` |

## Related Files

- **Parser**: `app/Services/BettingMessageParser.php` (dòng 326-343)
- **Schedule Service**: `app/Services/LotteryScheduleService.php`
- **Documentation**: `docs/DA_THANG_DA_XIEN.md`

## Implementation Notes

1. Auto-resolve sử dụng `LotteryScheduleService::getNStations(1, date, region)`
2. Chỉ resolve khi `stationCount === 0` (chưa có đài)
3. Nếu resolve thất bại (exception), validation sẽ reject
4. Station sau khi resolve được lưu vào `ctx['stations']` → Kế thừa cho bet tiếp theo (Rule 2)

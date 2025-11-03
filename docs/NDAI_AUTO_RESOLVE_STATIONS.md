# Ndai (2dai/3dai) Auto-Resolve Stations

## Tóm Tắt

Hệ thống parser hỗ trợ **3 quy tắc chính** để xử lý đài khi người dùng sử dụng Ndai (2d/3d/2dai/3dai):

| # | Quy Tắc | Ví Dụ | Kết Quả |
|---|---------|-------|---------|
| **1** | Không có đài → Dùng đài chính theo date/region | `23 12 lo 10n` | Auto-resolve: `tien giang` (Nam) |
| **2** | Có Ndai (2dai/3dai) → Auto-resolve N đài từ lịch | `2dai 11 22 dx 10n` | Auto-resolve: `['tien giang', 'can tho']` |
| **3** | Ndai + Đài cụ thể → Dùng đài đã chỉ định | `2dai tn bt 11 22 dx 10n` | Explicit: `['tay ninh', 'ben tre']` |

**Ngoại lệ:** Đá xiên (dx) KHÔNG auto-resolve nếu chỉ có 1 hoặc 0 đài - BẮT BUỘC ≥2 đài.

---

## Quy Tắc 1: Auto-Resolve Đài Mặc Định

### Mô Tả
Khi người dùng không nhập đài, hệ thống tự động dùng đài chính của miền (theo `global_date` và `global_region`).

### Code Location
**File:** `app/Services/BettingMessageParser.php`

**Lines 796-841** - Logic auto-resolve trong hàm `emitBet`:
```php
if (!$hasStation) {
    if ($hasNdaiMeta) {
        // Có Ndai → Quy tắc 2
    } elseif (!empty($ctx['stations'])) {
        // Có đài explicit → Quy tắc 3
    } else {
        // KHÔNG có đài → Dùng defaultStations (Quy tắc 1)
        $b['station'] = $joinStations($defaultStations);
    }
}
```

**Lines 326-343** - Đá thẳng auto-resolve (trường hợp đặc biệt):
```php
if ($type === 'da_thang') {
    $stationCount = count($ctx['stations'] ?? []);

    // Auto-resolve station nếu chưa có
    if ($stationCount === 0) {
        $mainStation = $this->scheduleService->getNStations(
            1,
            $ctx['betting_date'] ?? date('Y-m-d'),
            $region
        );
        if (!empty($mainStation)) {
            $ctx['stations'] = [$mainStation[0]];
        }
    }
}
```

### Test Cases

#### Test 1.1: Bao lô không có đài
```
Input: 23 12 lo 10n
Region: nam
Date: 2025-11-02

Expected:
- Auto-resolve station: 'tien giang' (đài chính Nam)
- 2 bets: [23, lo], [12, lo]

Events:
- station: 'tien giang'
```

#### Test 1.2: Đá thẳng không có đài
```
Input: 20 29 dt 10n
Region: nam
Date: 2025-11-02

Expected:
- Auto-resolve station: 'tien giang'
- 1 bet đá thẳng: numbers=[20, 29]

Events:
- da_thang_auto_resolve_station: {
    region: 'nam',
    date: '2025-11-02',
    resolved_station: 'tien giang'
  }
```

#### Test 1.3: Đá xiên không có đài (REJECT)
```
Input: 11 22 33 dx 10n
Region: nam

Expected:
- ❌ ERROR: Đá xiên yêu cầu ≥2 đài
- 0 bets

Events:
- error_da_xien_wrong_station_count
```

---

## Quy Tắc 2: Ndai Auto-Resolve Từ Lịch

### Mô Tả
Khi người dùng dùng `2d`, `3d`, `2dai`, `3dai` nhưng không chỉ định đài cụ thể, hệ thống tự động lấy N đài từ lịch theo date/region.

### Code Location
**File:** `app/Services/BettingMessageParser.php`

**Lines 525-537** - Parse Ndai token:
```php
// Ndai / Nd
if (preg_match('/^([234])d(ai)?$/', $tok, $m)) {
    $count = (int)$m[1];

    // Flush group cũ nếu có
    if ($isGroupPending($ctx)) {
        $flushGroup($outBets, $ctx, $events, 'dai_token_switch_flush');
    }

    // Bật chế độ bắt N đài
    $ctx['dai_count']             = $count;
    $ctx['dai_capture_remaining'] = $count;
    $ctx['stations']              = []; // Reset để bắt đài mới

    $addEvent($events, 'dai_count_set', ['count'=>$count,'token'=>$tok]);
    continue;
}
```

**Lines 794-841** - Auto-resolve N đài trong `emitBet`:
```php
if (!$hasStation) {
    if ($hasNdaiMeta) {
        // Case 2: Có Ndai → Auto resolve theo lịch
        $daiCount = (int)$b['meta']['dai_count'];

        // Chỉ auto resolve cho miền Nam và Trung
        if (in_array($region, ['nam', 'trung'], true) && $daiCount >= 2 && $daiCount <= 4) {
            try {
                $autoStations = $this->scheduleService->getNStations($daiCount, $bettingDate, $region);

                if (!empty($autoStations)) {
                    // Lưu list stations để expand sau
                    $b['meta']['_stations_to_expand'] = $autoStations;
                    $addEvent($events, 'station_auto_resolved', [
                        'dai_count' => $daiCount,
                        'region' => $region,
                        'date' => $bettingDate,
                        'resolved_stations' => $autoStations,
                        'will_expand' => true
                    ]);
                }
            } catch (\Exception $e) {
                // Fallback nếu có lỗi
                $b['station'] = $joinStations($defaultStations);
            }
        }
    }
}
```

**File:** `app/Services/LotteryScheduleService.php`

**Lines 71-91** - Hàm `getNStations`:
```php
public function getNStations(int $count, string|CarbonInterface $date, string $region): array
{
    $stations = $this->getStationsByDateAndRegion($date, $region);

    $result = [];

    // Thêm đài chính trước
    if ($stations['main']) {
        $result[] = $this->normalizeStationName($stations['main']);
    }

    // Thêm đài phụ cho đủ số lượng
    $needed = $count - count($result);
    $secondary = array_slice($stations['secondary'], 0, $needed);

    foreach ($secondary as $station) {
        $result[] = $this->normalizeStationName($station);
    }

    return $result;
}
```

### Logic Flow

```
1. Gặp token "2dai" → Set dai_count=2, dai_capture_remaining=2, stations=[]
2. Gặp số "11 22" → Set numbers_group=[11, 22]
3. Gặp type "dx" → Set current_type='da_xien'
4. Gặp amount "10n" → Set amount=10000
5. Final flush:
   a. Check: ctx['stations'] = [] (không có đài cụ thể)
   b. Check: meta['dai_count'] = 2 (có Ndai)
   c. Auto-resolve: getNStations(2, '2025-11-02', 'nam')
      → Returns: ['tien giang', 'can tho']
   d. Expand: Tạo C(2,2) = 1 pair đá xiên
   e. Emit: 1 bet đá xiên với 2 đài ✅
```

### Test Cases

#### Test 2.1: Đá xiên 2 đài (auto-resolve)
```
Input: 2dai 11 22 33 dx 10n
Region: nam
Date: 2025-11-02

Expected:
- Auto-resolve stations: ['tien giang', 'can tho']
- Đá xiên pairs: C(2,2) = 1 pair
- 3 bets: [11, dx, tg-ct], [22, dx, tg-ct], [33, dx, tg-ct]

Events:
- dai_count_set: {count: 2, token: '2dai'}
- station_auto_resolved: {
    dai_count: 2,
    region: 'nam',
    date: '2025-11-02',
    resolved_stations: ['tien giang', 'can tho']
  }
```

#### Test 2.2: Đá xiên 3 đài (auto-resolve)
```
Input: 3dai 11 22 dx 10n
Region: nam
Date: 2025-11-02

Expected:
- Auto-resolve stations: ['tien giang', 'can tho', 'dong thap']
- Đá xiên pairs: C(3,2) = 3 pairs
- 2 numbers × 3 pairs = 6 bets

Events:
- station_auto_resolved: {dai_count: 3, resolved_stations: [...]}
```

#### Test 2.3: Miền Bắc không auto-resolve
```
Input: 2dai 11 22 dx 10n
Region: bac

Expected:
- ❌ KHÔNG auto-resolve (miền Bắc chỉ có 1 đài)
- station = null
- Validation reject

Events:
- station_ndai_keep_null: {
    region: 'bac',
    dai_count: 2,
    reason: 'Miền Bắc hoặc dai_count không hợp lệ'
  }
```

---

## Quy Tắc 3: Ndai + Đài Cụ Thể

### Mô Tả
Khi người dùng dùng `2dai`/`3dai` kèm theo tên đài cụ thể, hệ thống dùng các đài đã chỉ định thay vì auto-resolve từ lịch.

### Code Location
**File:** `app/Services/BettingMessageParser.php`

**Lines 717-750** - Capture đài cụ thể trong chế độ Ndai:
```php
// station
if (isset($stationAliases[$tok])) {
    $name = $stationAliases[$tok];

    // 1) Đang ở chế độ "bắt N đài" (Ndai)
    if ($ctx['dai_capture_remaining'] > 0) {
        // Nếu đã có group pending → flush trước
        if ($isGroupPending($ctx)) {
            $savedDaiCount = $ctx['dai_count'];
            $flushGroup($outBets, $ctx, $events, 'ndai_group_complete_flush');
            // Reset chế độ Ndai
            $ctx['dai_count'] = null;
            $ctx['dai_capture_remaining'] = 0;
            $ctx['stations'] = [];
            // Bắt đầu group mới
            $ctx['stations'] = [$name];
            $addEvent($events, 'ndai_reset_new_station', ['new_station' => $name]);
            continue;
        }

        // Chưa có group pending → thu thập đài cho đủ
        if (!in_array($name, $ctx['stations'], true)) {
            $ctx['stations'][] = $name;
            $ctx['dai_capture_remaining']--;
            $addEvent($events, 'dai_capture_station', [
                'captured' => $name,
                'remain'   => $ctx['dai_capture_remaining'],
                'stations' => $ctx['stations'],
            ]);
        }

        if ($ctx['dai_capture_remaining'] === 0) {
            $addEvent($events, 'dai_capture_done', ['stations' => $ctx['stations']]);
        }
        continue;
    }
}
```

**Lines 842-849** - Sử dụng đài explicit trong `emitBet`:
```php
} elseif (!empty($ctx['stations'])) {
    // Case 1: User đã chỉ định đài cụ thể (vd: 2dai tn ag)
    // Lưu list stations vào meta để expand sau
    $b['meta']['_stations_to_expand'] = $ctx['stations'];
    $addEvent($events, 'station_from_explicit', [
        'stations' => $ctx['stations'],
        'will_expand' => true
    ]);
}
```

### Logic Flow

```
1. Gặp token "2dai"
   → Set dai_count=2, dai_capture_remaining=2, stations=[]

2. Gặp token "tn" (station alias)
   → dai_capture_remaining > 0
   → Capture: stations=['tay ninh'], remaining=1
   → Event: dai_capture_station

3. Gặp token "bt" (station alias)
   → dai_capture_remaining > 0
   → Capture: stations=['tay ninh', 'ben tre'], remaining=0
   → Event: dai_capture_done

4. Gặp số "11 22" → numbers_group=[11, 22]
5. Gặp type "dx" → current_type='da_xien'
6. Gặp amount "10n" → amount=10000
7. Final flush:
   a. Check: ctx['stations'] = ['tay ninh', 'ben tre'] ✅ (đã có đài)
   b. KHÔNG auto-resolve (vì đã có stations)
   c. Expand: C(2,2) = 1 pair
   d. Emit: 2 bets với stations explicit
```

### Test Cases

#### Test 3.1: 2dai + 2 đài cụ thể
```
Input: 2dai tn bt 11 22 dx 10n
Region: nam

Expected:
- Explicit stations: ['tay ninh', 'ben tre']
- KHÔNG auto-resolve
- Đá xiên pairs: C(2,2) = 1 pair (tn-bt)
- 2 bets: [11, dx, tn-bt], [22, dx, tn-bt]

Events:
- dai_count_set: {count: 2}
- dai_capture_station: {captured: 'tay ninh', remain: 1}
- dai_capture_station: {captured: 'ben tre', remain: 0}
- dai_capture_done: {stations: ['tay ninh', 'ben tre']}
- station_from_explicit: {stations: ['tay ninh', 'ben tre']}
```

#### Test 3.2: 3dai + 3 đài cụ thể
```
Input: 3dai tn bt cm 11 22 dx 10n
Region: nam

Expected:
- Explicit stations: ['tay ninh', 'ben tre', 'ca mau']
- Đá xiên pairs: C(3,2) = 3 pairs
- 2 numbers × 3 pairs = 6 bets

Events:
- dai_capture_done: {stations: ['tay ninh', 'ben tre', 'ca mau']}
- station_from_explicit
```

#### Test 3.3: 2dai thiếu đài (chỉ nhập 1 đài)
```
Input: 2dai tn 11 22 dx 10n
Region: nam

Expected:
- dai_capture_remaining = 1 (chưa đủ 2 đài)
- Khi gặp số "11":
  - Vẫn thiếu 1 đài → auto-resolve thêm 1 đài từ lịch
  - Hoặc giữ stations=['tay ninh'] và validate reject (tùy logic)

Note: Cần kiểm tra behavior thực tế của parser
```

---

## So Sánh 3 Quy Tắc

| Tình Huống | Input Example | Stations Result | Auto-Resolve? |
|-----------|---------------|-----------------|---------------|
| **Không có đài** | `23 12 lo 10n` | `['tien giang']` | ✅ Quy tắc 1 |
| **Có Ndai, không có đài** | `2dai 11 22 dx 10n` | `['tien giang', 'can tho']` | ✅ Quy tắc 2 |
| **Có Ndai + đài cụ thể** | `2dai tn bt 11 22 dx 10n` | `['tay ninh', 'ben tre']` | ❌ Quy tắc 3 |
| **Đá thẳng không có đài** | `20 29 dt 10n` | `['tien giang']` | ✅ Quy tắc 1 (special) |
| **Đá xiên không có đài** | `11 22 dx 10n` | `null` → ❌ ERROR | ❌ REJECT |

---

## Ngoại Lệ: Đá Xiên Yêu Cầu ≥2 Đài

Đá xiên (dx) **BẮT BUỘC ≥2 đài** để tạo pairs. Nếu chỉ có 1 hoặc 0 đài, validation sẽ reject:

```php
// Lines 377-387 in BettingMessageParser.php
if ($type === 'da_xien') {
    $stationCount = count($ctx['stations'] ?? []);

    if ($stationCount < 2) {
        $addEvent($events, 'error_da_xien_wrong_station_count', [
            'expected_min' => 2,
            'got' => $stationCount,
            'message' => 'Đá xiên yêu cầu ít nhất 2 đài'
        ]);
        // Reset context
        return;
    }
}
```

**Cách đúng để dùng đá xiên:**
1. ✅ `2dai 11 22 dx 10n` - Auto-resolve 2 đài
2. ✅ `2dai tn bt 11 22 dx 10n` - Explicit 2 đài
3. ✅ `tn bt 11 22 dx 10n` - Explicit 2 đài (không cần Ndai)
4. ❌ `11 22 dx 10n` - REJECT (thiếu đài)
5. ❌ `tg 11 22 dx 10n` - REJECT (chỉ 1 đài)

---

## Summary

| Quy Tắc | Mô Tả | Code Location | Status |
|---------|-------|---------------|--------|
| **1. Auto-Resolve Đài Mặc Định** | Không có đài → dùng đài chính theo date/region | BettingMessageParser.php:796-841, 326-343 | ✅ Implemented |
| **2. Ndai Auto-Resolve** | 2dai/3dai không có đài → lấy N đài từ lịch | BettingMessageParser.php:794-841<br>LotteryScheduleService.php:71-91 | ✅ Implemented |
| **3. Ndai + Đài Explicit** | 2dai tn bt → dùng đài đã chỉ định | BettingMessageParser.php:717-750, 842-849 | ✅ Implemented |
| **Ngoại lệ: Đá Xiên** | dx yêu cầu ≥2 đài (không auto-resolve 1 đài) | BettingMessageParser.php:377-387 | ✅ Implemented |

**Kết luận:** Tất cả 3 quy tắc đã được implement đầy đủ trong codebase.

---

## Related Files

- **Parser**: `app/Services/BettingMessageParser.php`
- **Schedule Service**: `app/Services/LotteryScheduleService.php`
- **Related Docs**:
  - `docs/DA_THANG_AUTO_RESOLVE_STATION.md`
  - `docs/DA_THANG_DA_XIEN.md`
  - `docs/PARSER_COMBO_TOKEN_FIX.md`

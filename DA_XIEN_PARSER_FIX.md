# SỬA PARSER ĐÁ XIÊN - KHÔNG NHÂN BẢN THEO ĐÀI

## 🐛 Vấn đề

**Trước khi sửa:**
Input: `bd vl 33 23 25 18 88 19 dx 1n`

Parser tạo **12 bets** (sai):
- 6 bets với `station="binh duong"`
- 6 bets với `station="vinh long"`

**Nguyên nhân:**
`$emitBet()` tự động NHÂN BẢN mỗi bet theo số lượng đài khi `station=null`:

```php
// Trong $emitBet() - DÒNG 112-123
if (empty($bet['station'])) {
    if (!empty($ctx['stations'])) {
        // Nếu có nhiều đài trong context → NHÂN BẢN mỗi đài một vé
        if (count($ctx['stations']) > 1) {
            foreach ($ctx['stations'] as $st) {
                $clone = $bet;
                $clone['station'] = $st;
                $outBets[] = $clone;
            }
            return;
        }
    }
}
```

**Vấn đề:** Logic này phù hợp với các loại cược single-station (bao lô, đầu, đuôi), nhưng **KHÔNG** phù hợp với đá xiên (multi-station).

## ✅ Sửa lỗi

**File:** `app/Services/BettingMessageParser.php`

**Method:** Xử lý `da_xien` type (dòng 360-418)

**Giải pháp:** Bỏ qua `$emitBet()` và trực tiếp thêm vào `$outBets[]` với `station` đã join:

```php
// Join stations thành string "station1 + station2"
$stationString = count($stations) === 1 ? $stations[0] : implode(' + ', $stations);

// Emit mỗi cặp số là 1 vé
// Đá xiên: KHÔNG nhân bản theo đài, chỉ 1 vé với tất cả cặp số
foreach ($numberPairs as $pair) {
    $outBets[] = [
        'numbers' => $pair,
        'type'    => 'da_xien',
        'amount'  => $amount,
        'meta'    => [
            'station_mode' => 'across',
            'station_pairs' => $stationPairs,
            'dai_count' => $stationCount,
        ],
        'station' => $stationString,
    ];
}
```

## ✅ Kết quả

**Sau khi sửa:**
Input: `bd vl 33 23 25 18 88 19 dx 1n`

Parser tạo **15 bets** (đúng):
- Tất cả có `station="binh duong + vinh long"`
- Tạo 15 cặp số: C(6,2) = 6×5/2 = 15

```
1: numbers=[33,23], station=binh duong + vinh long
2: numbers=[33,25], station=binh duong + vinh long
3: numbers=[33,18], station=binh duong + vinh long
4: numbers=[33,88], station=binh duong + vinh long
5: numbers=[33,19], station=binh duong + vinh long
6: numbers=[23,25], station=binh duong + vinh long
7: numbers=[23,18], station=binh duong + vinh long
8: numbers=[23,88], station=binh duong + vinh long
9: numbers=[23,19], station=binh duong + vinh long
10: numbers=[25,18], station=binh duong + vinh long
11: numbers=[25,88], station=binh duong + vinh long
12: numbers=[25,19], station=binh duong + vinh long
13: numbers=[18,88], station=binh duong + vinh long
14: numbers=[18,19], station=binh duong + vinh long
15: numbers=[88,19], station=binh duong + vinh long
```

## 🔧 Settlement Logic

Settlement service đã có sẵn logic để parse `"binh duong + vinh long"`:

```php
protected function parseStations(?string $stationStr): array
{
    if (empty($stationStr)) {
        return [];
    }

    // Tách theo dấu +
    $stations = explode('+', $stationStr);

    return array_map('trim', $stations);
}
```

Trong `matchDaXien()`:
- Lấy tất cả results từ cả 2 đài
- Check điều kiện thắng xuyên các đài
- Tính tiền dựa trên `station_pairs` trong `meta`

## 📊 Tác động

| Loại cược | Trước | Sau |
|-----------|-------|-----|
| Bao lô/Đầu/Đuôi | Đúng (1 đài) | ✅ Không đổi |
| Đá thẳng | Đúng (1 đài) | ✅ Không đổi |
| Đá xiên | ❌ SAI (nhân bản) | ✅ Đúng (join đài) |

## 🎯 Kết luận

**Parser đá xiên đã đúng:**
- ✅ Tạo C(n,2) bets từ n số
- ✅ Mỗi bet có `station="station1 + station2"`
- ✅ Settlement parse đúng và check xuyên các đài
- ✅ Không ảnh hưởng đến các loại cược khác


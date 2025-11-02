# Tính năng Auto Resolve Đài cho 2dai/3dai/4dai

## 📋 Tổng quan

Khi user nhập tin nhắn cược với `2dai`, `3dai`, hoặc `4dai` **mà không chỉ định tên đài cụ thể**, hệ thống sẽ **tự động resolve** đài chính và đài phụ dựa trên:
- **Ngày cược** (để xác định thứ mấy trong tuần)
- **Miền cược** (Bắc/Trung/Nam)

## 🎯 Use Cases

### ✅ Case 1: Có chỉ định đài cụ thể
```
Input: 2dai tn ag lo 68 5n
```
→ Parser bắt 2 đài: **Tây Ninh** và **An Giang**  
→ Tạo **2 bets riêng** (1 bet cho mỗi đài)
```
Bet 1: station = "tay ninh"
Bet 2: station = "an giang"
```

### ✅ Case 2: Không chỉ định đài → Auto resolve + Split
```
Input: 2dai 12 lo 10n
Context: { region: 'nam', date: '2025-01-02' } // Thứ Năm
```
→ Hệ thống tự động lấy đài theo lịch: **Tây Ninh** + **An Giang**  
→ Sau đó **SPLIT thành 2 bets riêng** (1 bet cho mỗi đài)
```
Bet 1: station = "tay ninh", amount = 10000
Bet 2: station = "an giang", amount = 10000
```

**💡 CẢ HAI TRƯỜNG HỢP ĐỀU TẠO NHIỀU BETS RIÊNG!**

## 📅 Lịch Đài theo Thứ

### Miền Nam

| Thứ | Đài chính | Đài phụ |
|-----|-----------|---------|
| **Thứ Hai** | TP.HCM | Đồng Tháp, Cà Mau |
| **Thứ Ba** | Vũng Tàu | Bến Tre, Bạc Liêu |
| **Thứ Tư** | Đồng Nai | Cần Thơ, Sóc Trăng |
| **Thứ Năm** | Tây Ninh | An Giang, Bình Thuận |
| **Thứ Sáu** | Bình Dương | Vĩnh Long, Trà Vinh |
| **Thứ Bảy** | TP.HCM | Long An, Bình Phước, Hậu Giang |
| **Chủ Nhật** | Tiền Giang | Kiên Giang, Đà Lạt |

### Miền Trung

| Thứ | Đài chính | Đài phụ |
|-----|-----------|---------|
| **Thứ Hai** | Phú Yên | Thừa Thiên Huế |
| **Thứ Ba** | Quảng Nam | Đắk Lắk |
| **Thứ Tư** | Khánh Hòa | Đà Nẵng |
| **Thứ Năm** | Quảng Bình | Bình Định, Quảng Trị |
| **Thứ Sáu** | Gia Lai | Ninh Thuận |
| **Thứ Bảy** | Quảng Ngãi | Đà Nẵng, Đắk Nông |
| **Chủ Nhật** | Khánh Hòa | Kon Tum |

### Miền Bắc

| Thứ | Đài chính | Đài phụ |
|-----|-----------|---------|
| **Tất cả** | Hà Nội | — |

⚠️ **Lưu ý**: Miền Bắc không áp dụng auto resolve cho 2dai/3dai.

## 🔧 Implementation

### Service: `LotteryScheduleService`

```php
// Lấy N đài theo ngày và miền
$stations = $scheduleService->getNStations(
    count: 2,              // 2, 3, hoặc 4 đài
    date: '2025-01-02',    // Ngày cược
    region: 'nam'          // bac, trung, nam
);
// → ['tay ninh', 'an giang']
```

### Parser: `BettingMessageParser`

Trong `parseMessage()`, sau khi parse xong:

1. **Nếu có `ctx['stations']`** (user đã chỉ định đài):
   ```php
   $b['station'] = 'tay ninh + an giang';
   ```

2. **Nếu có `meta['dai_count']` nhưng không có stations**:
   ```php
   // Auto resolve theo lịch
   $autoStations = $scheduleService->getNStations($daiCount, $date, $region);
   $b['station'] = 'tay ninh + an giang';
   ```

3. **Nếu không có gì**:
   ```php
   // Fallback default
   $b['station'] = 'tp.hcm';
   ```

## ✅ Validation Rules

1. ✅ Auto resolve chỉ áp dụng cho **Miền Nam** và **Miền Trung**
2. ✅ Miền Bắc → giữ `station = null` (không auto resolve)
3. ✅ Số đài hợp lệ: 2, 3, hoặc 4
4. ✅ Date phải là valid date string hoặc Carbon instance
5. ✅ Nếu không đủ đài phụ → lấy tối đa có thể

## 📝 Context Parameters

Khi gọi `parseMessage()`, bạn có thể truyền context:

```php
$parser->parseMessage('2dai lo 68 5n', [
    'region' => 'nam',           // bac|trung|nam
    'date' => '2025-01-02',      // Y-m-d hoặc Carbon
    'customer_id' => 123,        // optional
]);
```

**Fallback**:
- `region`: Lấy từ `session('global_region', 'nam')`
- `date`: Lấy từ `session('global_date', now())`

## 🧪 Testing

Chạy test:
```bash
php test_2dai_auto_resolve.php
```

**Test Cases**:
- ✅ Test 1: `2dai tn ag lo 68 5n` (có đài cụ thể)
- ✅ Test 2: `2dai lo 68 5n` (Thứ Năm Miền Nam → TN + AG)
- ✅ Test 3: `2dai lo 68 5n` (Thứ Hai Miền Nam → TP.HCM + ĐT)
- ✅ Test 4: `3dai lo 68 5n` (Thứ Bảy Miền Nam → TP.HCM + LA + BP)
- ✅ Test 5: `2d lo 68 5n` (Thứ Tư Miền Trung → KH + ĐN)
- ✅ Test 6: `2dai lo 68 5n` (Miền Bắc → null, không auto)

## 📊 Debug Events

Parser emit các events để tracking:

```json
{
  "kind": "dai_count_set",
  "count": 2,
  "token": "2dai"
}

{
  "kind": "station_auto_resolved",
  "dai_count": 2,
  "region": "nam",
  "date": "2025-01-02",
  "resolved_stations": ["tay ninh", "an giang"],
  "joined": "tay ninh + an giang"
}
```

## 🚀 Usage trong Controller

```php
use App\Services\BettingMessageParser;

public function store(Request $request)
{
    $parser = app(BettingMessageParser::class);
    
    $result = $parser->parseMessage($request->message, [
        'region' => session('global_region', 'nam'),
        'date' => session('global_date', now()->format('Y-m-d')),
        'customer_id' => $request->customer_id,
    ]);
    
    if ($result['is_valid']) {
        foreach ($result['multiple_bets'] as $bet) {
            // $bet['station'] đã được auto resolve nếu cần
            // Ví dụ: "tay ninh + an giang"
            BettingTicket::create([
                'station' => $bet['station'],
                'type' => $bet['type'],
                // ...
            ]);
        }
    }
}
```

## 🔍 Troubleshooting

### Vấn đề: Station vẫn là default thay vì auto resolve

**Nguyên nhân**: Parser không nhận được `date` hoặc `region` đúng

**Giải pháp**:
```php
// Đảm bảo truyền date và region trong context
$result = $parser->parseMessage($message, [
    'date' => now()->format('Y-m-d'),
    'region' => 'nam'
]);
```

### Vấn đề: Miền Bắc không auto resolve

**Đây là behavior đúng!** Theo yêu cầu `DOC_FUNC.md`, auto resolve chỉ áp dụng cho Miền Nam và Trung.

### Vấn đề: Không đủ đài phụ

Ví dụ: Yêu cầu `4dai` nhưng chỉ có 3 đài trong lịch Thứ Bảy Miền Nam.

**Behavior**: Lấy tối đa có thể (3 đài)

---

**Phiên bản**: 1.0.0  
**Ngày tạo**: 2025-11-01  
**Tác giả**: AI Assistant


# Hướng dẫn sử dụng tính năng Quyết toán Phiếu cược

## Tổng quan

Tính năng quyết toán tự động giúp bạn tính toán thắng/thua cho phiếu cược dựa trên kết quả xổ số thực tế. Hệ thống sẽ tự động:

- ✅ So khớp các số cược với kết quả xổ số
- ✅ Tính toán tiền thắng dựa trên hệ số cược
- ✅ Cập nhật trạng thái phiếu cược (win/lose)
- ✅ Cập nhật thống kê cho khách hàng

---

## Các loại cược được hỗ trợ

### 1. **Bao Lô** (`bao_lo`)
- **Quy tắc**: Số trúng khi xuất hiện trong 2 số cuối của tất cả các giải
- **Tỷ lệ mặc định**: x80
- **Ví dụ**: Đánh 10, 50 bao lô 15k → Nếu 10 về 3 nháy, 50 về 2 nháy → Trúng 5 nháy tổng cộng

### 2. **Đầu** (`dau`)
- **Quy tắc**: Trúng khi match 2 số đầu giải đặc biệt
- **Tỷ lệ mặc định**: x85
- **Ví dụ**: Đánh đầu 71 → GĐB: 71XXX → TRÚNG

### 3. **Đuôi** (`duoi`)
- **Quy tắc**: Trúng khi match 2 số cuối giải đặc biệt
- **Tỷ lệ mặc định**: x85
- **Ví dụ**: Đánh đuôi 39 → GĐB: XXX39 → TRÚNG

### 4. **Đầu Đuôi** (`dau_duoi`)
- **Quy tắc**: Kết hợp cả đầu và đuôi (tính như 2 bet riêng)
- **Ví dụ**: Đánh đầu đuôi 10 → Có thể trúng cả đầu và đuôi

### 5. **Xỉu Chủ** (`xiu_chu`)
- **Quy tắc**: Trúng khi match 3 số cuối giải đặc biệt
- **Tỷ lệ mặc định**: x500
- **Ví dụ**: Đánh xỉu chủ 035 → GĐB: XXX035 → TRÚNG

### 6. **Xỉu Chủ Đầu** (`xiu_chu_dau`)
- **Quy tắc**: Match 2 số đầu của 3 số cuối GĐB
- **Tỷ lệ mặc định**: x90
- **Ví dụ**: Đánh xỉu chủ đầu 03 → GĐB cuối: 039 → TRÚNG (03x)

### 7. **Xỉu Chủ Đuôi** (`xiu_chu_duoi`)
- **Quy tắc**: Match 2 số cuối của 3 số cuối GĐB
- **Tỷ lệ mặc định**: x90
- **Ví dụ**: Đánh xỉu chủ đuôi 39 → GĐB cuối: 039 → TRÚNG (x39)

### 8. **Xiên** (`xien`) - Chỉ áp dụng Miền Bắc
- **Quy tắc**: Tất cả các số phải trúng lô (về ít nhất 1 nháy)
- **Tỷ lệ**:
  - Xiên 2: x15
  - Xiên 3: x550
  - Xiên 4: x3500
- **Ví dụ**: Xiên 2 số 11, 22 → Cả 11 và 22 đều phải về lô → TRÚNG

---

## Cách sử dụng

### Phương pháp 1: Quyết toán từ Command Line (CLI)

Dành cho Admin hoặc chạy tự động qua cronjob.

#### Quyết toán tất cả phiếu cược của một ngày:

```bash
php artisan betting:settle 2025-01-15
```

#### Quyết toán cho một miền cụ thể:

```bash
php artisan betting:settle 2025-01-15 --region=nam
```

#### Quyết toán cho một phiếu cược cụ thể:

```bash
php artisan betting:settle --ticket=123
```

#### Quyết toán hôm qua (mặc định):

```bash
php artisan betting:settle
```

---

### Phương pháp 2: Quyết toán từ Web Interface

#### A. Quyết toán một phiếu cược

1. Vào trang **Quản lý phiếu cược** (`/user/betting-tickets`)
2. Tìm phiếu cược cần quyết toán (trạng thái `pending`)
3. Nhấn nút **"Quyết toán"** bên cạnh phiếu cược
4. Hệ thống sẽ tự động:
   - Tìm kết quả xổ số tương ứng
   - So khớp các số cược
   - Tính toán tiền thắng/thua
   - Cập nhật trạng thái

**Route endpoint:**
```
POST /user/betting-tickets/{id}/settle
```

#### B. Quyết toán hàng loạt

1. Vào trang **Quản lý phiếu cược**
2. Chọn bộ lọc ngày và miền
3. Nhấn nút **"Quyết toán hàng loạt"**
4. Hệ thống sẽ quyết toán tất cả phiếu pending của ngày đó

**Route endpoint:**
```
POST /user/betting-tickets/settle-batch
Params: date (YYYY-MM-DD), region (optional)
```

---

## Quy trình quyết toán

```
┌─────────────────────────────────────────────────┐
│  1. Lấy phiếu cược cần quyết toán (pending)    │
└─────────────────────────────────────────────────┘
                     ↓
┌─────────────────────────────────────────────────┐
│  2. Tìm kết quả xổ số tương ứng                 │
│     - Theo ngày cược (betting_date)             │
│     - Theo miền (region)                        │
│     - Theo đài (station)                        │
└─────────────────────────────────────────────────┘
                     ↓
┌─────────────────────────────────────────────────┐
│  3. So khớp từng bet trong phiếu cược          │
│     - Kiểm tra loại cược                        │
│     - So sánh số cược với kết quả              │
│     - Đếm số nháy trúng                         │
└─────────────────────────────────────────────────┘
                     ↓
┌─────────────────────────────────────────────────┐
│  4. Tính toán tiền thắng                        │
│     - Lấy tỷ lệ trả từ customer_rates          │
│     - Tính: win_amount * multiplier            │
└─────────────────────────────────────────────────┘
                     ↓
┌─────────────────────────────────────────────────┐
│  5. Cập nhật phiếu cược                         │
│     - result: win/lose                          │
│     - win_amount: số tiền thắng                 │
│     - payout_amount: số tiền phải trả          │
│     - status: completed                         │
└─────────────────────────────────────────────────┘
                     ↓
┌─────────────────────────────────────────────────┐
│  6. Cập nhật thống kê khách hàng                │
│     - daily_win/daily_loss                      │
│     - monthly_win/monthly_loss                  │
│     - yearly_win/yearly_loss                    │
└─────────────────────────────────────────────────┘
```

---

## Ví dụ thực tế

### Ví dụ 1: Bao Lô

**Phiếu cược:**
- Khách hàng: Nguyễn Văn A
- Ngày: 2025-01-15
- Miền: Nam
- Đài: TP.HCM
- Cược: 10, 50 bao lô, mỗi số 15.000đ

**Kết quả xổ số TP.HCM 2025-01-15:**
- Giải 8: 10, 72
- Giải 7: 350, 789
- Giải 6: 5010, 2150, 7888
- ...

**Quyết toán:**
- Số 10: Trúng 3 nháy (Giải 8: 10, Giải 6: 5010, 2150)
- Số 50: Trúng 2 nháy (Giải 7: 350, Giải 6: 2150)
- Tổng: 5 nháy
- Tiền thắng: 5 × 15.000 = 75.000đ
- Tiền trả (x80): 75.000 × 80 = 6.000.000đ
- **Kết quả: WIN**

### Ví dụ 2: Xiên 2 (Miền Bắc)

**Phiếu cược:**
- Khách hàng: Trần Thị B
- Ngày: 2025-01-15
- Miền: Bắc
- Cược: Xiên 2 số 11, 22 × 100.000đ

**Kết quả xổ số Miền Bắc 2025-01-15:**
- Có số 11 về lô (1 nháy)
- Có số 22 về lô (2 nháy)

**Quyết toán:**
- Cả 2 số đều trúng lô → Điều kiện xiên thỏa mãn
- Tiền thắng: 100.000đ
- Tiền trả (x15): 100.000 × 15 = 1.500.000đ
- **Kết quả: WIN**

### Ví dụ 3: Đầu + Đuôi

**Phiếu cược:**
- Cược: Đầu đuôi 73 × 50.000đ

**Kết quả GĐB:**
- 734567

**Quyết toán:**
- Đầu: 73 = 73 → TRÚNG
- Đuôi: 67 ≠ 73 → TRƯỢT
- Tiền thắng đầu: 50.000 × 85 = 4.250.000đ
- Tiền thắng đuôi: 0đ
- **Kết quả: WIN (1/2)**

---

## Lưu ý quan trọng

### 1. Kết quả xổ số phải có sẵn
- Hệ thống cần có kết quả xổ số trong bảng `lottery_results`
- Nếu chưa có kết quả → Phiếu cược giữ nguyên trạng thái `pending`
- Kiểm tra kết quả tại: `/user/kqxs`

### 2. Hệ số trả thưởng
- Hệ số được lấy từ `betting_rates` của khách hàng
- Nếu không có → Dùng hệ số mặc định
- Cấu hình hệ số tại: `/user/customers/{id}/rates`

### 3. Đài cược
- Phải khớp chính xác tên đài
- Hỗ trợ nhiều đài (ví dụ: "tp.hcm + dong thap")
- Mỗi đài được tính riêng

### 4. Miền cược
- Xiên chỉ áp dụng cho Miền Bắc
- Các loại cược khác áp dụng cho cả 3 miền

### 5. Quyền truy cập
- User chỉ có thể quyết toán phiếu cược của mình
- Admin có thể quyết toán tất cả phiếu cược

---

## Troubleshooting

### Lỗi: "Chưa có kết quả xổ số"
**Nguyên nhân:** Chưa scrape kết quả cho ngày/đài tương ứng

**Giải pháp:**
```bash
php artisan lottery:scrape 2025-01-15
```

### Lỗi: "Không tìm thấy loại cược"
**Nguyên nhân:** BettingType chưa được tạo trong database

**Giải pháp:** Kiểm tra bảng `betting_types` và đảm bảo có đủ các loại cược

### Phiếu cược không được quyết toán
**Kiểm tra:**
1. Trạng thái phiếu cược có phải `pending` không?
2. Đã có kết quả xổ số chưa?
3. Tên đài có khớp không?
4. Miền cược có đúng không?

---

## API Response Format

### Thành công:
```json
{
  "settled": true,
  "result": "win",
  "win_amount": 75000,
  "payout_amount": 6000000,
  "details": [
    {
      "is_win": true,
      "type": "bao_lo",
      "numbers": ["10", "50"],
      "bet_amount": 30000,
      "win_count": 5,
      "win_amount": 75000,
      "payout_amount": 6000000,
      "details": [...]
    }
  ]
}
```

### Chưa có kết quả:
```json
{
  "settled": false,
  "result": "pending",
  "win_amount": 0,
  "payout_amount": 0,
  "details": {
    "error": "Chưa có kết quả xổ số"
  }
}
```

---

## Tự động hóa với Cronjob

Để tự động quyết toán hàng ngày, thêm vào crontab:

```bash
# Chạy lúc 19:00 hàng ngày (sau khi có kết quả)
0 19 * * * cd /path/to/project && php artisan betting:settle >> /var/log/betting-settlement.log 2>&1
```

Hoặc dùng Laravel Scheduler trong `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    $schedule->command('betting:settle')
        ->dailyAt('19:00')
        ->timezone('Asia/Ho_Chi_Minh');
}
```

---

## Liên hệ hỗ trợ

Nếu có vấn đề hoặc câu hỏi, vui lòng liên hệ:
- Email: support@tukta-sone.com
- Hotline: 1900-xxxx-xxxx

---

**Phiên bản:** 1.0.0
**Ngày cập nhật:** 2025-01-15

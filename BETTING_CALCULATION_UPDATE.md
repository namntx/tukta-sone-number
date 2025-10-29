# Cập nhật Logic Tính toán Cược - Betting Calculation Update

## Tổng quan (Overview)

Đã cập nhật logic tính toán tiền xác (commission) và tiền thắng (winning amount) cho tất cả các loại cược theo từng miền (Bắc, Trung, Nam).

## Chi tiết cập nhật (Update Details)

### 1. **BetPricingService.php** - Cập nhật công thức tính toán

#### A. Miền Nam và Miền Trung (MN/MT)

##### Đầu Đuôi (Head/Tail)
- **Tiền xác**: `(tiền cược đầu + tiền cược đuôi) * buy_rate`
- **Tiền thắng**: `tiền cược * payout`

##### Lô (Lottery)
- **Lô 2 số**: `tiền cược * 18 * buy_rate`
- **Lô 3 số**: `tiền cược * 17 * buy_rate`
- **Lô 4 số**: `tiền cược * 16 * buy_rate`
- **Tiền thắng**: `tiền cược * payout`

##### Đá Thẳng (Straight Package)
- **Tiền xác**: `tiền cược * 2 * 18 * buy_rate`
- **Tiền thắng**: `tiền cược cặp số ăn * payout`

##### Đá Chéo (Cross Package)
- **2 đài**: `tiền cược * 4 * 18 * buy_rate`
- **3 đài**: `tiền cược * 4 * 3 * 18 * buy_rate`
- **4 đài**: `tiền cược * 4 * 6 * 18 * buy_rate`
- **Tiền thắng**: `tiền cược cặp số ăn * payout`

##### Xỉu Chủ MN/MT
- Chỉ tính G7 và GĐB (G7 là đầu, GĐB là đuôi)
- **Tiền xác đầu**: `tiền cược đầu * 1 * buy_rate`
- **Tiền xác đuôi**: `tiền cược đuôi * 1 * buy_rate`
- **Tiền thắng**: `tiền cược * payout`

---

#### B. Miền Bắc (MB)

##### Lô (Lottery)
- **Lô 2 số**: `tiền cược * 27 * buy_rate`
- **Lô 3 số**: `tiền cược * 23 * buy_rate`
- **Lô 4 số**: `tiền cược * 20 * buy_rate`
- **Tiền thắng**: `tiền cược * payout`

##### Xiên (Chain)
- **Xiên 2, 3, 4**: Tất cả số phải về mới ăn
- **Tiền xác**: `tiền cược * buy_rate`
- **Tiền thắng**: `tiền cược * payout`
- **Ví dụ**: `xi2 10 20 1n` → kết quả có 2 số là ăn

##### Đá Thẳng (Straight Package)
- **Tiền xác**: `tiền cược * số cặp * 27 * buy_rate`
- **Tiền thắng**: `tiền cược * payout`

##### Xỉu Chủ MB
- Chỉ tính GĐB và G6 (G6 là đầu, GĐB là đuôi)
- **Xỉu chủ chung**: `tiền cược * 4 * buy_rate`

##### Xỉu Chủ Đầu Đuôi MB
- **Tiền xác**: `(tiền đầu * 3 + tiền đuôi) * buy_rate`
- **Tiền thắng**: `tiền cược * payout`

##### Đầu Đuôi MB
- **Tiền xác**: `(tiền đầu * 4 + tiền đuôi) * buy_rate`
- **Tiền thắng**: `tiền cược * payout`

---

### 2. **BettingTicketController.php** - Tích hợp pricing vào API

#### Cập nhật method `parseMessage()`

```php
public function parseMessage(Request $request, BettingMessageParser $parser, BetPricingService $pricing)
```

**Thay đổi:**
1. Thêm parameter `BetPricingService $pricing`
2. Khởi tạo pricing service với customer_id và region
3. Tính toán `cost_xac` và `potential_win` cho mỗi bet
4. Thêm summary với tổng tiền xác và tổng tiền thắng dự kiến

**Response JSON bao gồm:**
```json
{
  "is_valid": true,
  "multiple_bets": [
    {
      "station": "tp.hcm",
      "numbers": ["12", "34"],
      "type": "Bao lô 2 số",
      "amount": 10000,
      "cost_xac": 180000,
      "potential_win": 700000,
      "buy_rate": 0.18,
      "payout": 70,
      "meta": {...}
    }
  ],
  "summary": {
    "total_cost_xac": 180000,
    "total_potential_win": 700000,
    "total_bets": 1
  }
}
```

---

### 3. **Dashboard View (dashboard.blade.php)** - Hiển thị preview

#### Cập nhật bảng preview

**Các cột mới:**
- **Xác**: Hiển thị tiền xác (cost_xac) cho mỗi cược
- **Thắng**: Hiển thị tiền thắng dự kiến (potential_win)

**Footer totals:**
- **Tổng Cược**: Tổng tiền cược
- **Tổng Xác**: Tổng tiền xác phải trả
- **Tổng Thắng**: Tổng tiền thắng dự kiến

**Format số:**
- `< 1k`: Hiển thị số đầy đủ
- `>= 1k`: Hiển thị dạng "123k"
- `>= 1M`: Hiển thị dạng "1.2M"

---

## Công thức tổng hợp (Summary Formulas)

### Hệ số nhân (Multipliers)

| Loại cược | MB | MN/MT |
|-----------|----|----|
| Lô 2 số | 27 | 18 |
| Lô 3 số | 23 | 17 |
| Lô 4 số | 20 | 16 |
| Đầu | 4 | 1 |
| Đuôi | 1 | 1 |
| Xỉu chủ | 4 | 1 |
| Xỉu chủ đầu | 3 | 1 |
| Đá thẳng | số_cặp * 27 | 2 * 18 |

### Công thức chung

```
Tiền xác = Tiền cược * Hệ số * buy_rate
Tiền thắng = Tiền cược * payout
```

---

## Testing

### Test Cases

1. **Lô 2 số MB**: `12 lo 10n` (MB)
   - Xác: 10,000 * 27 * 0.18 = 48,600
   - Thắng: 10,000 * 70 = 700,000

2. **Lô 2 số MN**: `12 lo 10n` (MN)
   - Xác: 10,000 * 18 * 0.18 = 32,400
   - Thắng: 10,000 * 70 = 700,000

3. **Xiên 2 MB**: `xi2 10 20 1n`
   - Xác: 1,000 * 0.18 = 180
   - Thắng: 1,000 * payout

4. **Đầu đuôi MB**: `12 dd 10n`
   - Xác: (10,000 * 4 + 10,000 * 1) * 0.18 = 9,000
   - Thắng: 10,000 * payout

5. **Xỉu chủ đầu đuôi MB**: `123 xc d 5 10`
   - Xác: (5,000 * 3 + 10,000) * 0.18 = 4,500
   - Thắng: tiền cược * payout

---

## Files Modified

1. `app/Services/BetPricingService.php`
2. `app/Http/Controllers/User/BettingTicketController.php`
3. `resources/views/user/dashboard.blade.php`

---

## Notes

- Tất cả tính toán được làm tròn đến số nguyên
- Buy_rate và payout được lấy từ BettingRateResolver
- Region được detect từ request hoặc session
- Preview được cập nhật real-time khi parse message


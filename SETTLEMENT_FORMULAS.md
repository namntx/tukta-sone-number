# Công thức tính toán Settlement (Quyết toán)

## Tổng quan

Document này mô tả chi tiết công thức tính toán tiền xác (cost_xac) và tiền thắng (payout) cho từng loại cược theo từng miền.

### Các thuật ngữ:
- **cost_xac**: Tiền xác (tiền hoa hồng/chi phí) mà nhà cái thu từ khách
- **buy_rate**: Tỷ lệ thu (mặc định 0.75 = 75%)
- **payout**: Tỷ lệ trả thưởng khi thắng
- **win_amount**: Tiền thắng thô (chưa nhân payout)
- **payout_amount**: Tiền trả thực tế = win_amount × payout

---

## Miền Trung và Miền Nam (MT/MN)

### 1. Bao Lô

**Công thức tiền xác:**
```
cost_xac = amount × multiplier × buy_rate

Trong đó multiplier:
- Lô 2 số: 18
- Lô 3 số: 17
- Lô 4 số: 16
```

**Công thức tiền thắng:**
```
win_amount = amount × số_nháy_trúng
payout_amount = win_amount × payout
```

**Ví dụ:**
```
Đánh: 10, 50 bao lô 2 số, mỗi số 10k
buy_rate = 0.75, payout = 80

Tiền xác:
- cost_xac = 10,000 × 18 × 0.75 = 135,000 VNĐ (cho mỗi số)
- Tổng xác = 135,000 × 2 = 270,000 VNĐ

Kết quả: 10 về 3 nháy, 50 về 2 nháy
- win_amount = 10,000 × (3 + 2) = 50,000 VNĐ
- payout_amount = 50,000 × 80 = 4,000,000 VNĐ
```

---

### 2. Đầu và Đuôi

**Công thức tiền xác:**
```
Đầu: cost_xac = amount × buy_rate
Đuôi: cost_xac = amount × buy_rate
```

**Công thức tiền thắng:**
```
win_amount = amount × số_lần_trúng
payout_amount = win_amount × payout (mặc định 85)
```

**Ví dụ:**
```
Đánh đầu 73, 10k
buy_rate = 0.75, payout = 85

Tiền xác:
- cost_xac = 10,000 × 0.75 = 7,500 VNĐ

Kết quả: GĐB = 734567 (đầu 73 trúng)
- win_amount = 10,000 VNĐ
- payout_amount = 10,000 × 85 = 850,000 VNĐ
```

---

### 3. Đầu Đuôi

**Công thức tiền xác:**
```
cost_xac = (tiền_đầu + tiền_đuôi) × buy_rate
```

**Công thức tiền thắng:**
```
Tính riêng cho đầu và đuôi, sau đó cộng lại
```

**Ví dụ:**
```
Đánh đầu đuôi 10, 20k
buy_rate = 0.75, payout = 85

Tiền xác:
- cost_xac = (20,000 + 20,000) × 0.75 = 30,000 VNĐ

Kết quả: GĐB = 102345 (đầu 10 trúng, đuôi 10 KHÔNG trúng)
- win_amount_đầu = 20,000 × 85 = 1,700,000 VNĐ
- win_amount_đuôi = 0 VNĐ
- Tổng payout = 1,700,000 VNĐ
```

---

### 4. Đá Thẳng

**Công thức tiền xác:**
```
cost_xac = amount × 2 × 18 × buy_rate
```

**Công thức tiền thắng:**
```
win_amount = amount × số_cặp_trúng
payout_amount = win_amount × payout (mặc định 70)
```

**Ví dụ:**
```
Đánh: 10, 20 đá thẳng 10k
buy_rate = 0.75, payout = 70

Tiền xác:
- cost_xac = 10,000 × 2 × 18 × 0.75 = 270,000 VNĐ

Kết quả: Cả 10 và 20 đều về lô trên cùng đài
- win_amount = 10,000 VNĐ
- payout_amount = 10,000 × 70 = 700,000 VNĐ
```

---

### 5. Đá Xiên/Chéo

**Công thức tiền xác:**
```
2 đài: cost_xac = amount × 4 × 18 × buy_rate
3 đài: cost_xac = amount × 4 × 3 × 18 × buy_rate
4 đài: cost_xac = amount × 4 × 6 × 18 × buy_rate
```

**Công thức tiền thắng:**
```
win_amount = amount × số_cặp_trúng
payout_amount = win_amount × payout
```

**Ví dụ:**
```
Đánh: 10, 20 đá xiên 2 đài, 10k
buy_rate = 0.75, payout = 70

Tiền xác:
- cost_xac = 10,000 × 4 × 18 × 0.75 = 540,000 VNĐ

Kết quả: 10 về đài A, 20 về đài B
- win_amount = 10,000 VNĐ
- payout_amount = 10,000 × 70 = 700,000 VNĐ
```

---

### 6. Xỉu Chủ (Chỉ tính GĐB + G7)

**Công thức tiền xác:**
```
cost_xac = amount × buy_rate
```

**Công thức tiền thắng:**
```
win_amount = amount × số_lần_trúng
payout_amount = win_amount × payout (mặc định 500)
```

**Ví dụ:**
```
Đánh xỉu chủ 123, 10k
buy_rate = 0.75, payout = 500

Tiền xác:
- cost_xac = 10,000 × 0.75 = 7,500 VNĐ

Kết quả: GĐB = XXX123
- win_amount = 10,000 VNĐ
- payout_amount = 10,000 × 500 = 5,000,000 VNĐ
```

---

### 7. Xỉu Chủ Đầu Đuôi (GĐB là đuôi, G7 là đầu)

**Công thức tiền xác:**
```
cost_xac = (tiền_đầu + tiền_đuôi) × buy_rate
```

**Ví dụ:**
```
Đánh: 123, 456 xc đầu 5k đuôi 10k
buy_rate = 0.75, payout = 90

Tiền xác:
- cost_xac = (5,000 + 10,000) × 0.75 = 11,250 VNĐ

Kết quả:
- G7 = XXX123 (đầu 12 trúng)
- GĐB = XXX456 (đuôi 56 trúng)
- payout_đầu = 5,000 × 90 = 450,000 VNĐ
- payout_đuôi = 10,000 × 90 = 900,000 VNĐ
- Tổng = 1,350,000 VNĐ
```

---

## Miền Bắc (MB)

### 1. Bao Lô

**Công thức tiền xác:**
```
cost_xac = amount × multiplier × buy_rate

Trong đó multiplier:
- Lô 2 số: 27
- Lô 3 số: 23
- Lô 4 số: 20
```

**Công thức tiền thắng:** (giống MT/MN)
```
win_amount = amount × số_nháy_trúng
payout_amount = win_amount × payout
```

**Ví dụ:**
```
Đánh: 10 bao lô 2 số, 10k (MB)
buy_rate = 0.75, payout = 80

Tiền xác:
- cost_xac = 10,000 × 27 × 0.75 = 202,500 VNĐ

Kết quả: 10 về 3 nháy
- win_amount = 10,000 × 3 = 30,000 VNĐ
- payout_amount = 30,000 × 80 = 2,400,000 VNĐ
```

---

### 2. Đầu

**Công thức tiền xác:**
```
cost_xac = amount × 4 × buy_rate
```

**Ví dụ:**
```
Đánh đầu 73, 10k (MB)
buy_rate = 0.75, payout = 85

Tiền xác:
- cost_xac = 10,000 × 4 × 0.75 = 30,000 VNĐ
```

---

### 3. Đầu Đuôi

**Công thức tiền xác:**
```
cost_xac = (đầu × 4 + đuôi) × buy_rate
```

**Ví dụ:**
```
Đánh đầu đuôi 10, 20k (MB)
buy_rate = 0.75, payout = 85

Tiền xác:
- cost_xac = (20,000 × 4 + 20,000) × 0.75 = 75,000 VNĐ
```

---

### 4. Đá Thẳng

**Công thức tiền xác:**
```
cost_xac = amount × số_cặp × 27 × buy_rate
```

**Ví dụ:**
```
Đánh: 10, 20, 30 đá thẳng 10k (MB)
Số cặp = C(3,2) = 3 cặp: (10,20), (10,30), (20,30)
buy_rate = 0.75, payout = 70

Tiền xác:
- cost_xac = 10,000 × 3 × 27 × 0.75 = 607,500 VNĐ
```

---

### 5. Xiên 2/3/4

**Công thức tiền xác:**
```
cost_xac = amount × buy_rate
```

**Công thức tiền thắng:**
```
Phải trúng đủ tất cả các số
win_amount = amount
payout_amount = amount × payout

Trong đó payout:
- Xiên 2: 15
- Xiên 3: 550
- Xiên 4: 3500
```

**Ví dụ:**
```
Đánh: xi2 10 20, 100k (MB)
buy_rate = 0.75, payout = 15

Tiền xác:
- cost_xac = 100,000 × 0.75 = 75,000 VNĐ

Kết quả: Cả 10 và 20 đều về lô
- win_amount = 100,000 VNĐ
- payout_amount = 100,000 × 15 = 1,500,000 VNĐ
```

---

### 6. Xỉu Chủ (Chỉ tính GĐB + G6)

**Công thức tiền xác:**
```
cost_xac = amount × 4 × buy_rate
```

**Ví dụ:**
```
Đánh xỉu chủ 123, 10k (MB)
buy_rate = 0.75, payout = 500

Tiền xác:
- cost_xac = 10,000 × 4 × 0.75 = 30,000 VNĐ
```

---

### 7. Xỉu Chủ Đầu Đuôi (GĐB là đuôi, G6 là đầu)

**Công thức tiền xác:**
```
cost_xac = (đầu × 3 + đuôi) × buy_rate
```

**Ví dụ:**
```
Đánh: 123, 456 xc đầu 5k đuôi 10k (MB)
buy_rate = 0.75, payout = 90

Tiền xác:
- cost_xac = (5,000 × 3 + 10,000) × 0.75 = 18,750 VNĐ
```

---

## Tổng kết công thức

### So sánh MT/MN vs MB

| Loại cược | MT/MN | MB |
|-----------|-------|-----|
| **Lô 2 số** | amount × 18 × buy_rate | amount × 27 × buy_rate |
| **Lô 3 số** | amount × 17 × buy_rate | amount × 23 × buy_rate |
| **Lô 4 số** | amount × 16 × buy_rate | amount × 20 × buy_rate |
| **Đầu** | amount × buy_rate | amount × 4 × buy_rate |
| **Đuôi** | amount × buy_rate | amount × buy_rate |
| **Đầu Đuôi** | (đầu+đuôi) × buy_rate | (đầu×4+đuôi) × buy_rate |
| **Đá Thẳng** | amount × 2 × 18 × buy_rate | amount × pairs × 27 × buy_rate |
| **Xiên** | N/A | amount × buy_rate |
| **Xỉu Chủ** | amount × buy_rate | amount × 4 × buy_rate |
| **XC Đầu Đuôi** | (đầu+đuôi) × buy_rate | (đầu×3+đuôi) × buy_rate |

---

## Ghi chú kỹ thuật

1. **buy_rate**: Thường là 0.75 (75%), có thể config cho từng khách hàng
2. **payout**: Tỷ lệ trả khác nhau cho từng loại cược, config trong `betting_rates`
3. **Số cặp trong Đá Thẳng**: C(n,2) = n×(n-1)/2
4. **Đá Xiên multiplier**:
   - 2 đài: 4
   - 3 đài: 4×3 = 12
   - 4 đài: 4×6 = 24

---

**Version:** 2.0
**Last Updated:** 2025-01-15
**Author:** Settlement System Team

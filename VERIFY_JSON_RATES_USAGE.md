# XÁC NHẬN: HỆ THỐNG TÍNH TIỀN XÁC VÀ THẮNG THUA ĐÃ LẤY ĐÚNG TỪ JSON

## 📋 KIỂM TRA CODE

### ✅ 1. BettingRateResolver::build()
**File:** `app/Services/BettingRateResolver.php`

**Flow:**
1. Load GLOBAL rates từ `betting_rates` table (customer_id=null, region='*')
2. Load REGION DEFAULT rates từ `betting_rates` table (customer_id=null, region='bac/trung/nam')
3. **Load CUSTOMER OVERRIDE từ JSON:**
   ```php
   if ($customerId) {
       $customer = Customer::find($customerId);
       if ($customer && !empty($customer->betting_rates)) {
           // NEW: Load from JSON column
           $this->loadFromJson($customer->betting_rates, $region);
       }
   }
   ```

**Priority:** Customer JSON > Region Default > Global

### ✅ 2. BetPricingService (Tính tiền xác)
**File:** `app/Services/BetPricingService.php`

**Flow:**
1. `begin($customerId, $region)` → Build resolver với customer_id
   ```php
   $this->resolver = (new BettingRateResolver())->build($customerId, $region);
   ```

2. `previewForBet($bet)` → Resolve rate từ resolver
   ```php
   [$buyRate, $payout] = $this->resolver->resolve($typeCode, $digits, $xienSize, $daiCount);
   ```

3. Tính `cost_xac` và `potential_win` dùng `$buyRate` và `$payout` từ resolver
   - `cost_xac = amount * factor * buy_rate` (factor theo miền)
   - `potential_win = amount * payout`

**✅ Kết luận:** BetPricingService ĐÃ dùng rates từ JSON

### ✅ 3. BettingSettlementService (Tính tiền thắng thua)
**File:** `app/Services/BettingSettlementService.php`

**Flow:**
1. `settleTicket($ticket)` → Build resolver với ticket's customer_id và region
   ```php
   $this->rateResolver->build($ticket->customer_id, $ticket->region);
   ```

2. Trong các method match (`matchBaoLo`, `matchDau`, `matchDuoi`, etc.):
   ```php
   $rate = $this->rateResolver->resolve('bao_lo', $digits);
   $buyRate = $rate[0] ?? 0.75;
   $payout = $rate[1] ?? 80;
   ```

3. Tính `costXac` và `payoutAmount` dùng `$buyRate` và `$payout` từ resolver
   - `costXac = amount * multiplier * buyRate`
   - `payoutAmount = winAmount * payout`

**✅ Kết luận:** BettingSettlementService ĐÃ dùng rates từ JSON

## 📊 LOADFROMJSON() PARSE ĐÚNG CẤU TRÚC JSON

**File:** `app/Services/BettingRateResolver.php::loadFromJson()`

**Xử lý các format:**
- ✅ `"bac:de_dau"` → `type_code='dau'`
- ✅ `"bac:de_duoi"` → `type_code='duoi'`
- ✅ `"bac:de_duoi_4"` → `type_code='duoi'`, `digits=4`
- ✅ `"bac:xien:x2"` → `type_code='xien'`, `xien_size=2`
- ✅ `"bac:bao_lo:d2"` → `type_code='bao_lo'`, `digits=2`
- ✅ `"bac:da_xien:c2"` → `type_code='da_xien'`, `dai_count=2`
- ✅ `"nam:bay_lo:d2"` → `type_code='bay_lo'`, `digits=2`

## ✅ XÁC NHẬN CUỐI CÙNG

1. **BettingRateResolver::build()** load rates từ JSON column `customers.betting_rates` ✅
2. **BetPricingService** dùng resolver đã build với customer_id → lấy từ JSON ✅
3. **BettingSettlementService** dùng resolver đã build với ticket's customer_id → lấy từ JSON ✅
4. **loadFromJson()** parse đúng tất cả format JSON keys ✅

## 🎯 KẾT LUẬN

**HỆ THỐNG ĐÃ TÍNH TIỀN XÁC VÀ TIỀN THẮNG THUA ĐÚNG VỚI RATES TỪ JSON COLUMN `customers.betting_rates`**

Không còn dùng bảng `betting_rates` cho customer-specific rates (chỉ dùng cho default/global).


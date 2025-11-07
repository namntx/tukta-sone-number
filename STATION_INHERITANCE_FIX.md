# Station Inheritance Fix

## Problem

**Input 1:** `TN AG 13,21 lo 5n dx 5n`
- Expected: 1 đá xiên + 4 bao lô (2 TN + 2 AG)
- Got: ✅ Works correctly

**Input 2:** `TN AG 13,21 dx 5n lo 5n`
- Expected: 1 đá xiên + 4 bao lô (2 TN + 2 AG)
- Got: ❌ Only 1 đá xiên + 2 bao lô (2 TN), missing 2 AG bets

## Root Cause

When `dx` (da_xien) or `dt` (da_thang) flushes, stations were being reset to empty array:

```php
// Line 487 (da_xien) - BEFORE FIX
$ctx['stations']=[]; // ← Resets stations!

// Line 398 (da_thang) - BEFORE FIX
$ctx['stations']=[]; // ← Resets stations!
```

**Flow for Input 2:**
1. `TN AG` → stations = [TN, AG]
2. `13,21` → numbers = [13, 21]
3. `dx 5n` → flush da_xien with stations=[TN,AG]
4. After flush: **stations=[]** ← PROBLEM!
5. `lo 5n` → inherit numbers from last_numbers=[13,21]
6. But stations=[], so emitBet uses default station or null
7. Result: Only TN gets bao_lo, AG is lost

## Why This Happened

In previous fix (commit 237bfa0), we added `$ctx['stations']=[]` after da_xien/da_thang flush to prevent station contamination between different groups.

**That fix was for this case:**
```
vt bt 22,29 dx 1.4n...   vt bl 79,29 dx 0.7n
Group 1: vt bt          Group 2: vt bl
```

Without reset, Group 2 would inherit stations from Group 1, causing contamination.

## Solution

**Don't reset stations after flush** - Let them be inherited by next type (if numbers are inherited).

**Stations ARE reset when:**
1. New station token appears with group pending (line 851)
2. New Ndai directive appears (line 654)

**Stations are NOT reset when:**
- After da_xien/da_thang flush → Allow next type to use same stations

## Fix Applied

### File: `app/Services/BettingMessageParser.php`

**Line 400 (da_thang):**
```php
// BEFORE
$ctx['numbers_group']=[]; $ctx['amount']=null; $ctx['meta']=[]; $ctx['current_type']=null; $ctx['stations']=[];

// AFTER
$ctx['numbers_group']=[]; $ctx['amount']=null; $ctx['meta']=[]; $ctx['current_type']=null;
// Không reset stations để type tiếp theo có thể dùng (nếu inherit numbers)
```

**Line 488 (da_xien):**
```php
// BEFORE
$ctx['numbers_group']=[]; $ctx['amount']=null; $ctx['meta']=[]; $ctx['current_type']=null; $ctx['stations']=[];

// AFTER
$ctx['numbers_group']=[]; $ctx['amount']=null; $ctx['meta']=[]; $ctx['current_type']=null;
// Không reset stations để type tiếp theo có thể dùng (nếu inherit numbers)
```

## How It Works Now

### Case 1: Same group, different types (SHOULD share stations)
```
Input: "TN AG 13,21 dx 5n lo 5n"

Flow:
1. TN AG → stations=[TN,AG]
2. 13,21 → numbers=[13,21]
3. dx 5n → flush, stations=[TN,AG] preserved ✅
4. lo 5n → inherit numbers=[13,21], stations=[TN,AG] ✅
5. Result: dx + 2 TN lo + 2 AG lo ✅
```

### Case 2: Different groups (SHOULD NOT contaminate)
```
Input: "TN AG 13,21 dx 5n...   VT BL 79,29 lo 5n"

Flow:
1. TN AG → stations=[TN,AG]
2. 13,21 dx 5n → flush group 1
3. ... (dots)
4. VT → station_switch_flush → stations=[VT] ✅ (reset on new station)
5. BL → stations=[VT,BL]
6. 79,29 lo 5n → use stations=[VT,BL] ✅
7. Result: Group 1 with TN,AG; Group 2 with VT,BL ✅
```

### Case 3: Within same group (SHOULD share)
```
Input: "TN AG 13 lo 5n 21 xc 3n"

Flow:
1. TN AG → stations=[TN,AG]
2. 13 lo 5n → flush lo with stations=[TN,AG]
3. Stations preserved ✅
4. 21 xc 3n → use stations=[TN,AG] ✅
5. Result: 2 lo (TN+AG) + 4 xc (TN+AG, dau+duoi) ✅
```

## Key Points

✅ **Stations persist** within same group (no new station token, no Ndai)
✅ **Stations reset** when new station token appears with pending group
✅ **Stations reset** when Ndai directive appears
✅ **No contamination** between different groups

## Testing

Run test suite:
```bash
php artisan test --filter=BettingMessageParserStationInheritanceTest
```

Expected results:
- test_input_1_lo_before_dx_works ✅
- test_input_2_dx_before_lo_should_also_work ✅
- test_stations_reset_on_new_station_token ✅
- test_stations_shared_within_same_group ✅

## Impact

- Fixes station loss when type order changes (dx before lo)
- Maintains station isolation between different groups
- Backward compatible with all existing flows

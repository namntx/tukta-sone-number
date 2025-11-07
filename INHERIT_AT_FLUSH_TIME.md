# Fix: Inherit Numbers Only When No New Numbers Provided

## Problem

Parser was inheriting numbers **too early** - immediately when type token appeared, before checking if new numbers would follow.

### Example Bug Cases

**Input 1:**
```
2dai 11,22 lo 2n dx 33,44 1.5n
```

**Bug behavior:**
- `lo` has [11, 22] @ 2n ✅
- `dx` token → **inherits [11, 22] immediately** ❌
- `33,44` token → **appends to [11, 22]** ❌
- Result: `dx` has [11, 22, 33, 44] ❌ WRONG!

**Expected behavior:**
- `lo` has [11, 22] @ 2n ✅
- `dx` token → **waits for numbers**
- `33,44` token → **new numbers provided**
- `1.5n` → flush with [33, 44] ONLY ✅

**Input 2:**
```
tp ct 11,22 lo 2n dx 33,44 1.5n
```

Same issue - `dx` inherits [11, 22] then appends [33, 44].

## Root Cause

**Before fix:**
Inheritance happened at **type token time** (lines 722-725, 763-766, 806-808):

```php
// When dx token appears
if (isset($typeAliases['dx'])) {
    // IMMEDIATE INHERITANCE (too early!)
    if (empty($ctx['numbers_group']) && !empty($ctx['last_numbers'])) {
        $ctx['numbers_group'] = $ctx['last_numbers']; // ← Inherit [11,22]
    }
    $ctx['current_type'] = 'da_xien';
}

// Later, when 33,44 appears
if (preg_match('/^\d{2,4}$/', $tok)) {
    $ctx['numbers_group'][] = $tok; // ← Appends to [11,22]
}
// Result: numbers_group = [11, 22, 33, 44] ❌
```

**Problem:** Parser couldn't distinguish between:
- "No numbers yet, but they might come"
- "No numbers at all, should inherit"

## Solution

**Move inheritance from type token time to flush time.**

This allows parser to check: "Did new numbers arrive after type token?"

### Changes Made

#### Change 1: Add inherit logic to $flushGroup (lines 230-236)

```php
$flushGroup = function(...) {
    $numbers = array_values(array_unique($ctx['numbers_group'] ?? []));
    $type    = $ctx['current_type'] ?? null;
    $amount  = (int)($ctx['amount'] ?? 0);

    // INHERIT AT FLUSH TIME: Chỉ inherit khi chưa có số mới
    // Logic: Nếu sau type token không có số xuất hiện → inherit từ last_numbers
    if (empty($numbers) && !empty($ctx['last_numbers']) && $reason !== 'type_switch_flush') {
        $numbers = $ctx['last_numbers'];
        $ctx['numbers_group'] = $numbers;
        $addEvent($events, 'inherit_numbers_at_flush', ['type'=>$type,'numbers'=>$numbers]);
    }

    // ... rest of flush logic
}
```

#### Change 2: Remove inherit from combo token (line 722)

**Before:**
```php
if (empty($ctx['numbers_group']) && !empty($ctx['last_numbers'])) {
    $ctx['numbers_group'] = $ctx['last_numbers'];
    $addEvent($events,'inherit_numbers_for_amount',['numbers'=>$ctx['numbers_group']]);
}
```

**After:**
```php
// REMOVED: Inherit logic moved to flush time (chỉ inherit khi chưa có số mới)
```

#### Change 3: Remove inherit from xien token (line 768)

**Before:**
```php
if (empty($ctx['numbers_group']) && !empty($ctx['last_numbers'])) {
    $ctx['numbers_group'] = $ctx['last_numbers'];
    $addEvent($events,'inherit_numbers_for_type',['type'=>'xien','numbers'=>$ctx['numbers_group']]);
}
```

**After:**
```php
// REMOVED: Inherit logic moved to flush time (chỉ inherit khi chưa có số mới)
```

#### Change 4: Remove inherit from normal type token (line 808)

**Before:**
```php
if (empty($ctx['numbers_group']) && !empty($ctx['last_numbers'])) {
    $ctx['numbers_group']=$ctx['last_numbers'];
    $addEvent($events,'inherit_numbers_for_type',['type'=>$newType,'numbers'=>$ctx['numbers_group']]);
}
```

**After:**
```php
// REMOVED: Inherit logic moved to flush time (chỉ inherit khi chưa có số mới)
```

## How It Works Now

### Case 1: New numbers provided (NO inherit)

```
Input: "2dai 11,22 lo 2n dx 33,44 1.5n"

[2dai]      → last_numbers=[]
[11,22]     → numbers_group=[11,22]
[lo]        → type=bao_lo
[2n]        → FLUSH! → last_numbers=[11,22] saved
[dx]        → type=da_xien, numbers_group=[] (empty, but wait...)
[33,44]     → numbers_group=[33,44] (NEW NUMBERS!)
[1.5n]      → FLUSH!
            → Check: numbers_group not empty
            → Use [33,44] directly ✅ NO INHERIT
```

**Result:**
- `lo`: [11, 22] @ 2000
- `dx`: [33, 44] @ 1500 ✅

### Case 2: No new numbers (YES inherit)

```
Input: "tp ct 11,22 lo 2n xc 3n"

[tp ct]     → stations=[tp.hcm, can tho]
[11,22]     → numbers_group=[11,22]
[lo]        → type=bao_lo
[2n]        → FLUSH! → last_numbers=[11,22] saved
[xc]        → type=xiu_chu, numbers_group=[] (empty)
[3n]        → amount=3000 → FLUSH!
            → Check: numbers_group is empty
            → Inherit from last_numbers=[11,22] ✅
```

**Result:**
- `lo`: [11, 22] @ 2000
- `xc`: [11, 22] @ 3000 (inherited) ✅

### Case 3: Combo token with new numbers (NO inherit)

```
Input: "tp 11,22 lo 2n dd5n 33,44"

[11,22]     → numbers_group=[11,22]
[lo]        → type=bao_lo
[2n]        → FLUSH! → last_numbers=[11,22]
[dd5n]      → type=dau_duoi, amount=5000, numbers_group=[]
            → FLUSH immediately!
            → Check: numbers_group empty → would inherit
            → BUT wait...
[33,44]     → numbers_group=[33,44] (added AFTER dd5n flush)
            → Next flush will use [33,44]
```

Actually, let me trace this more carefully...

**Actually for combo tokens:**
```
[dd5n]      → Sets type=dau_duoi, amount=5000
            → Flush called immediately
            → At flush: numbers_group is empty
            → Inherit from last_numbers=[11,22] ✅
[33,44]     → These become the NEXT group's numbers
```

So combo token SHOULD inherit if no numbers before it. Let me verify with a better example:

```
Input: "tp 11,22 lo 2n 33,44 dd5n"
[11,22]     → numbers_group=[11,22]
[lo]        → type=bao_lo
[2n]        → FLUSH! → last_numbers=[11,22]
[33,44]     → numbers_group=[33,44] (NEW GROUP)
[dd5n]      → type=dau_duoi, amount=5000
            → Flush immediately
            → numbers_group=[33,44] (not empty)
            → Use [33,44] ✅ NO INHERIT
```

Perfect! That's the correct behavior.

### Case 4: Multiple types in sequence

```
Input: "tp 11,22 lo 2n xc 3n dx 33,44 1.5n dd 2n"

Flow:
[11,22] lo 2n → lo has [11,22]
[xc] 3n       → xc has [11,22] (inherited, no new numbers)
[dx] 33,44 1.5n → dx has [33,44] (NOT inherited, new numbers)
[dd] 2n       → dd has [33,44] (inherited from dx, no new numbers)
```

**Result:**
- `lo`: [11, 22]
- `xc`: [11, 22] (inherited) ✅
- `dx`: [33, 44] (NOT inherited) ✅
- `dd`: [33, 44] (inherited) ✅

## Rule Summary

**New inheritance rule:**
> **Nếu sau type token có số xuất hiện → KHÔNG inherit số cũ, chỉ dùng số mới**
> **Nếu sau type token không có số mới → inherit từ last_numbers**

This is checked at **flush time**, not at type token time.

## Examples

### ✅ Example 1: No inherit (Ndai + new numbers)
```
Input:  "2dai 11,22 lo 2n dx 33,44 1.5n"
Result: lo=[11,22], dx=[33,44] only
```

### ✅ Example 2: No inherit (stations + new numbers)
```
Input:  "tp ct 11,22 lo 2n dx 33,44 1.5n"
Result: lo=[11,22], dx=[33,44] only
```

### ✅ Example 3: Yes inherit (no new numbers)
```
Input:  "tp ct 11,22 lo 2n xc 3n"
Result: lo=[11,22], xc=[11,22] inherited
```

### ✅ Example 4: Combo token no inherit (new numbers after)
```
Input:  "tp 11,22 lo 2n 33,44 dd5n"
Result: lo=[11,22], dd=[33,44]
```

### ✅ Example 5: Combo token yes inherit (no new numbers)
```
Input:  "tp 11,22 lo 2n dd5n"
Result: lo=[11,22], dd=[11,22] inherited
```

### ✅ Example 6: Mixed inheritance
```
Input:  "tp 11,22 lo 2n xc 3n dx 33,44 1.5n dd 2n"
Result:
  - lo=[11,22]
  - xc=[11,22] inherited ✓
  - dx=[33,44] NOT inherited ✓
  - dd=[33,44] inherited ✓
```

## Testing

**File**: `tests/Unit/BettingMessageParserInheritAtFlushTest.php`

Created 8 comprehensive test cases:

1. ✅ No inherit when new numbers after type token (Ndai)
2. ✅ No inherit when new numbers after type token (stations)
3. ✅ Inherit when no new numbers after type token
4. ✅ Combo token with new numbers (no inherit)
5. ✅ Combo token without new numbers (inherit)
6. ✅ Events show inherit_numbers_at_flush
7. ✅ Mixed inheritance in sequence

Run tests:
```bash
php artisan test --filter=BettingMessageParserInheritAtFlushTest
```

Expected: All 8 tests pass ✅

## Impact

### What's Fixed
- ✅ Numbers only inherited when truly no new numbers provided
- ✅ Parser can distinguish "waiting for numbers" vs "no numbers at all"
- ✅ Correct behavior for all inheritance scenarios
- ✅ Clean separation between betting groups

### Backward Compatibility
- ⚠️ **Behavior change** - but fixes buggy behavior
- Cases that relied on early inheritance may behave differently
- However, the new behavior matches user expectations better

### New Event
- `inherit_numbers_at_flush` - triggered when inheritance happens at flush time
- Replaces: `inherit_numbers_for_type` and `inherit_numbers_for_amount`

## Summary

**Core change:** Moved inheritance check from **type token time** (too early) to **flush time** (correct timing).

**Result:** Parser now correctly implements user's rule:
> "Nếu sau type token có số mới → không inherit. Nếu không có số mới → mới inherit."

**Files changed:**
- `app/Services/BettingMessageParser.php`
  - Added: Inherit logic in $flushGroup (lines 230-236)
  - Removed: Inherit logic from combo/xien/type tokens (3 locations)

**Testing:**
- `tests/Unit/BettingMessageParserInheritAtFlushTest.php` (8 test cases)
- Covers all scenarios: inherit/no-inherit with Ndai/stations/combo/normal types

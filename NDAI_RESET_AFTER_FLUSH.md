# Fix: Ndai Mode Resets After Flush

## Problem

After flushing a bet group with Ndai directive (via amount token), `dai_count` and `dai_capture_remaining` were NOT reset. This caused the next explicit stations to be incorrectly captured as if still in Ndai mode.

### Example Bug

**Input:**
```
3dai - 53,19 đax 2.1n 2dai 47,57 đax 1.4n ag bth 89,15 đax 2.1n
```

**Expected behavior:**
1. Đá xiên [53,19] with **3 auto-resolved** stations @ 2100
2. Đá xiên [47,57] with **2 auto-resolved** stations @ 1400
3. Đá xiên [89,15] with **explicit [ag, bth]** stations @ 2100 ✅

**Actual behavior (BUG):**
```
Flow:
[3dai]  → dai_count=3, dai_capture_remaining=3
[53,19 đax 2.1n] → FLUSH with 3 auto-resolved stations
                 → But dai_count STILL = 3 ❌
[2dai]  → dai_count=2, dai_capture_remaining=2 (override)
[47,57 đax 1.4n] → FLUSH with 2 auto-resolved stations
                 → But dai_capture_remaining STILL = 2 ❌
[ag]    → dai_capture_remaining > 0 → CAPTURE MODE! ❌
        → Capture ag → stations=[ag], dai_capture_remaining=1
[bth]   → Capture bth → stations=[ag,bth], dai_capture_remaining=0
[89,15 đax 2.1n] → FLUSH with captured [ag, bth]
```

**Result:** Cược 3 có 2 đài (ag + bth) ✅ đúng stations, nhưng qua **capture mode** thay vì **explicit mode** ❌

### Why This Is Wrong

User's rule:
> **Ndai directive chỉ apply cho 1 cược duy nhất.**
> **Sau khi flush cược đó, explicit stations phải hoạt động bình thường.**

When user types `ag bth` after a completed Ndai bet, they mean:
- "Set explicit stations to [ag, bth]"
- NOT "Capture these for Ndai mode"

## Root Cause

After flushing a bet via amount token, `dai_count` and `dai_capture_remaining` were NOT reset.

**Before fix:**
```php
// After flush via amount token
$ctx['numbers_group'] = [];  // ✅ Reset
$ctx['amount'] = null;        // ✅ Reset
$ctx['current_type'] = null;  // ✅ Reset
$ctx['dai_count'] = ???;      // ❌ NOT reset
$ctx['dai_capture_remaining'] = ???; // ❌ NOT reset
```

**Result:** Next station tokens still entered capture mode.

## Solution

Reset `dai_count` and `dai_capture_remaining` after **complete flush** (when amount > 0).

### File: `app/Services/BettingMessageParser.php`

**Change:** Lines 245-253 in `$flushGroup` function

```php
// RESET NDAI MODE sau khi flush hoàn chỉnh (có amount)
// Ndai directive chỉ apply cho 1 cược duy nhất
// Sau đó explicit stations sẽ không bị capture mode
$completeFlushReasons = ['amount_delimiter_flush', 'combo_token_auto_flush', 'final_flush', 'ndai_group_complete_flush'];
if (in_array($reason, $completeFlushReasons, true) && $amount > 0) {
    $ctx['dai_count'] = null;
    $ctx['dai_capture_remaining'] = 0;
    $addEvent($events, 'ndai_mode_reset_after_flush', ['reason'=>$reason]);
}
```

### Logic

Reset Ndai mode when:
1. **amount_delimiter_flush** - Amount token triggers flush
2. **combo_token_auto_flush** - Combo token (đax1n) triggers flush
3. **final_flush** - End of message
4. **ndai_group_complete_flush** - Ndai group complete

Do NOT reset when:
- **type_switch_flush** - Type changed but group not complete yet
- **station_switch_flush** - Station changed but group not complete yet

## How It Works Now

### Case 1: Ndai → flush → explicit stations (FIXED)

**Input:**
```
3dai - 53,19 đax 2.1n 2dai 47,57 đax 1.4n ag bth 89,15 đax 2.1n
```

**Flow after fix:**
```
[3dai]  → dai_count=3, dai_capture_remaining=3
[53,19] → numbers=[53,19]
[đax]   → type=da_xien
[2.1n]  → FLUSH! (amount_delimiter_flush)
          ✅ Reset: dai_count=null, dai_capture_remaining=0
          Auto-resolve 3 stations → emit bet

[2dai]  → dai_count=2, dai_capture_remaining=2
[47,57] → numbers=[47,57]
[đax]   → type=da_xien
[1.4n]  → FLUSH! (amount_delimiter_flush)
          ✅ Reset: dai_count=null, dai_capture_remaining=0
          Auto-resolve 2 stations → emit bet

[ag]    → dai_capture_remaining = 0 → NOT capture mode ✅
          → Set stations=[ag] (explicit mode)
[bth]   → Add to stations → stations=[ag,bth]
[89,15] → numbers=[89,15]
[đax]   → type=da_xien
[2.1n]  → FLUSH with explicit stations=[ag,bth] ✅
```

**Result:**
1. ✅ Đá xiên [53,19] with 3 auto-resolved stations @ 2100
2. ✅ Đá xiên [47,57] with 2 auto-resolved stations @ 1400
3. ✅ Đá xiên [89,15] with explicit [ag, bth] @ 2100

### Case 2: Ndai with explicit stations immediately

**Input:**
```
2dai ag bth 11,22 đax 1.5n
```

**Flow:**
```
[2dai]  → dai_count=2, dai_capture_remaining=2
[ag]    → Capture mode: stations=[ag], dai_capture_remaining=1
[bth]   → Capture mode: stations=[ag,bth], dai_capture_remaining=0
[11,22] → numbers=[11,22]
[đax]   → type=da_xien
[1.5n]  → FLUSH with explicit stations=[ag,bth] ✅
```

**Result:** Uses explicit [ag, bth] stations ✅

### Case 3: Combo token also triggers reset

**Input:**
```
2dai 11,22 đax1n ag 33,44 đax 2n
```

**Flow:**
```
[2dai]  → dai_count=2, dai_capture_remaining=2
[11,22] → numbers=[11,22]
[đax1n] → Combo: type=da_xien, amount=1000
          → FLUSH! (combo_token_auto_flush)
          ✅ Reset: dai_count=null, dai_capture_remaining=0
[ag]    → dai_capture_remaining = 0 → NOT capture mode ✅
          → Set stations=[ag]
[33,44] → numbers=[33,44]
[đax]   → type=da_xien
[2n]    → FLUSH with stations=[ag] ✅
```

**Result:** Second bet uses explicit [ag] station ✅

## Rule Summary

**Ndai Directive Lifecycle:**

1. **Set:** When Ndai token appears (2dai, 3dai, 4dai)
   - `dai_count` = N
   - `dai_capture_remaining` = N

2. **Active:** During station capture or bet building
   - Stations captured OR auto-resolved at flush

3. **Reset:** After complete flush (amount > 0)
   - `dai_count` = null
   - `dai_capture_remaining` = 0
   - Next stations work as explicit, NOT capture

**User's Rule (Confirmed):**
> Cược sẽ có:
> - **2dai hoặc 3dai hoặc 4dai** → auto-resolve
> - **Số đài cụ thể** → dùng explicit stations
> - **Ndai + đài cụ thể** → ưu tiên đài cụ thể (ignore Ndai count)

## Examples

### ✅ Example 1: Multiple Ndai with explicit stations between

```
Input: "2dai 11,22 đax 1n tp ct 33,44 đax 2n 3dai 55,66 đax 3n"

Result:
1. [11,22] with 2 auto-resolved stations (TN, AG)
2. [33,44] with explicit [tp, ct] ✅
3. [55,66] with 3 auto-resolved stations (TN, AG, BTH)
```

### ✅ Example 2: User's original bug case

```
Input: "3dai - 53,19 đax 2.1n 2dai 47,57 đax 1.4n ag bth 89,15 đax 2.1n"

Result:
1. [53,19] with 3 auto-resolved stations @ 2100
2. [47,57] with 2 auto-resolved stations @ 1400
3. [89,15] with explicit [ag, bth] @ 2100 ✅
```

### ✅ Example 3: Ndai immediately followed by explicit

```
Input: "2dai ag bth 11,22 đax 1.5n"

Result:
1. [11,22] with explicit [ag, bth] @ 1500 ✅
```

### ✅ Example 4: Combo token triggers reset

```
Input: "2dai 11,22 đax1n ag 33,44 đax 2n"

Result:
1. [11,22] with 2 auto-resolved stations @ 1000
2. [33,44] with explicit [ag] @ 2000 ✅
```

## Testing

**File:** `tests/Unit/BettingMessageParserNdaiResetTest.php`

Created 7 comprehensive test cases:

1. ✅ Ndai mode resets after flush, explicit stations work correctly
   - User's original bug case
   - Verifies explicit stations not captured

2. ✅ Ndai with explicit stations immediate

3. ✅ Multiple Ndai directives with explicit stations between

4. ✅ Ndai mode does not carry over messages

5. ✅ Events show ndai_mode_reset_after_flush

6. ✅ Combo token triggers Ndai reset

Run tests:
```bash
php artisan test --filter=BettingMessageParserNdaiResetTest
```

Expected: All 7 tests pass ✅

## Impact

### What's Fixed
- ✅ Ndai directive only applies to ONE bet
- ✅ Explicit stations work correctly after Ndai bet completes
- ✅ No more incorrect capture mode for explicit stations
- ✅ Clean lifecycle for Ndai mode

### Backward Compatibility
- ✅ **Fully backward compatible**
- ✅ Fixes buggy behavior where Ndai persisted across bets
- ✅ Makes Ndai semantics clean and predictable

### New Event
- `ndai_mode_reset_after_flush` - Triggered when Ndai mode is reset after complete flush

## Summary

**One simple fix with clear semantics:**

**Added 8 lines** to reset Ndai mode after complete flush:
```php
$completeFlushReasons = ['amount_delimiter_flush', 'combo_token_auto_flush', 'final_flush', 'ndai_group_complete_flush'];
if (in_array($reason, $completeFlushReasons, true) && $amount > 0) {
    $ctx['dai_count'] = null;
    $ctx['dai_capture_remaining'] = 0;
    $addEvent($events, 'ndai_mode_reset_after_flush', ['reason'=>$reason]);
}
```

**Result:**
- Ndai directive cleanly scoped to single bet ✅
- Explicit stations work as expected ✅
- User's bug fixed ✅

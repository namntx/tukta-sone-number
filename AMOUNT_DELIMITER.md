# Amount as Delimiter Feature

## Overview

Each bet is terminated by an amount token (e.g., `5n`, `3.5n`). When an amount is encountered, the current bet group is flushed immediately, and inheritance rules apply to the next bet if needed.

## What Changed

**Before:**
- Amount would set `ctx['amount']` but not trigger immediate flush
- Flush only happened when type changed or combo token was used

**After:**
- Amount triggers immediate flush if a group is pending
- After flush, next bet can inherit stations, numbers, or type according to rules
- Combo tokens (lo5n, dd3n) no longer clear `last_numbers` to enable inheritance

## Implementation

### File: `app/Services/BettingMessageParser.php`

#### Change 1: Amount triggers flush (lines 687-690)
```php
// Amount kết thúc phiếu → flush ngay nếu group pending
if ($isGroupPending($ctx)) {
    $flushGroup($outBets, $ctx, $events, 'amount_delimiter_flush');
}
```

#### Change 2: Combo tokens preserve last_numbers (line 741)
```php
// Không clear last_numbers - cho phép kế thừa số sang phiếu tiếp theo nếu cần
// (Comment replacing: $ctx['last_numbers'] = [];)
```

## How It Works

### Flow Diagram
```
User input: "TN AG 13,21 lo 5n xc 3n"

Tokens: [TN] [AG] [13,21] [lo] [5n] [xc] [3n]
         ↓    ↓     ↓      ↓    ↓    ↓    ↓
Step 1:  stations=[TN,AG]
Step 2:  numbers=[13,21]
Step 3:  type=bao_lo
Step 4:  amount=5000 → FLUSH! (emit 4 bets: 2 numbers x 2 stations)
         ↓ Save: last_numbers=[13,21], stations=[TN,AG]
Step 5:  type=xiu_chu
Step 6:  amount=3000 → FLUSH! (emit 8 bets: 2 numbers x 2 types x 2 stations)
         ↑ Inherit: numbers from last_numbers, stations preserved
```

### Inheritance Rules

After amount triggers flush:

1. **Stations**: Preserved within same group (until new station token or Ndai directive)
2. **Numbers**: Inherited if next type has no new numbers
3. **Type**: Must be explicitly specified or combo token

## Examples

### Example 1: Stations inherited
```
Input:  "TN AG 13,21 lo 5n xc 3n"

Output:
✓ 13 Bao lô TN 5n
✓ 21 Bao lô TN 5n
✓ 13 Bao lô AG 5n
✓ 21 Bao lô AG 5n
✓ 13 Xỉu chủ đầu TN 3n (inherited numbers + stations)
✓ 13 Xỉu chủ đuôi TN 3n
✓ 21 Xỉu chủ đầu TN 3n
✓ 21 Xỉu chủ đuôi TN 3n
✓ 13 Xỉu chủ đầu AG 3n
✓ 13 Xỉu chủ đuôi AG 3n
✓ 21 Xỉu chủ đầu AG 3n
✓ 21 Xỉu chủ đuôi AG 3n
```

### Example 2: Station changes between groups
```
Input:  "TN 13,21 lo 5n AG 31,41 xc 3n"

Output:
✓ 13 Bao lô TN 5n
✓ 21 Bao lô TN 5n
✓ 31 Xỉu chủ đầu AG 3n (new station, new numbers)
✓ 31 Xỉu chủ đuôi AG 3n
✓ 41 Xỉu chủ đầu AG 3n
✓ 41 Xỉu chủ đuôi AG 3n
```

### Example 3: Numbers inherited
```
Input:  "tp, 13,21 lo 5n xc 3n"

Output:
✓ 13 Bao lô TP 5n
✓ 21 Bao lô TP 5n
✓ 13 Xỉu chủ đầu TP 3n (inherited numbers)
✓ 13 Xỉu chủ đuôi TP 3n
✓ 21 Xỉu chủ đầu TP 3n
✓ 21 Xỉu chủ đuôi TP 3n
```

### Example 4: Multiple flushes in sequence
```
Input:  "tp, 13 lo 5n 21 dd 3n 31 xc 2n"

Output:
✓ 13 Bao lô TP 5n
✓ 21 Đầu TP 3n
✓ 21 Đuôi TP 3n
✓ 31 Xỉu chủ đầu TP 2n
✓ 31 Xỉu chủ đuôi TP 2n
```

### Example 5: Combo token (lo5n) also triggers flush
```
Input:  "tp, 13,21 lo5n xc 3n"

Output:
✓ 13 Bao lô TP 5n
✓ 21 Bao lô TP 5n
✓ 13 Xỉu chủ đầu TP 3n (inherited after combo flush)
✓ 13 Xỉu chủ đuôi TP 3n
✓ 21 Xỉu chủ đầu TP 3n
✓ 21 Xỉu chủ đuôi TP 3n
```

### Example 6: Decimal amounts work the same
```
Input:  "tp, 13,21 lo 3.5n xc 2.5n"

Output:
✓ 13 Bao lô TP 3500 (3.5n)
✓ 21 Bao lô TP 3500
✓ 13 Xỉu chủ đầu TP 2500 (2.5n, inherited numbers)
✓ 13 Xỉu chủ đuôi TP 2500
✓ 21 Xỉu chủ đầu TP 2500
✓ 21 Xỉu chủ đuôi TP 2500
```

## Context Clearing Behavior

After flush, context is selectively cleared:

| Field | Cleared? | Note |
|-------|----------|------|
| `numbers_group` | ✅ Yes | Always cleared after flush |
| `amount` | ✅ Yes | Always cleared after flush |
| `current_type` | ✅ Yes | Always cleared after flush |
| `meta` | ✅ Yes | Always cleared after flush |
| `last_numbers` | ❌ No | Preserved for inheritance (saved during flush) |
| `stations` | ❌ No | Preserved within group (only reset on new station token) |

## Debug Events

New event added:
- `amount_delimiter_flush`: Triggered when amount token causes flush

## Testing

Run test suite:
```bash
php artisan test --filter=BettingMessageParserAmountDelimiterTest
```

Expected: All tests pass ✅

## Backward Compatibility

✅ **Fully backward compatible**:
- Existing behavior preserved (combo tokens still flush)
- Inheritance rules already existed, just extended
- No breaking changes to existing bet parsing

## Related Features

- **Station inheritance**: Stations now persist across flushes within same group
- **Number inheritance**: Numbers can be inherited when no new numbers specified
- **Combo tokens**: lo5n, dd3.5n, d7n trigger flush but preserve last_numbers
- **Decimal amounts**: 3.5n, 7.5n supported (see DECIMAL_AMOUNT.md)

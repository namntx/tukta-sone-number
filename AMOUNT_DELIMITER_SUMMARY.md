# Amount as Delimiter - Implementation Summary

## ✅ Completed

**Branch**: `claude/amount-delimiter-011CUeRHM5LACtXabtyxNV3J`
**Commit**: `2c98c3b`
**Status**: Pushed to remote

## Changes Made

### 1. Core Parser Logic

**File**: `app/Services/BettingMessageParser.php`

#### Change A: Amount triggers immediate flush (lines 687-690)
```php
// Amount kết thúc phiếu → flush ngay nếu group pending
if ($isGroupPending($ctx)) {
    $flushGroup($outBets, $ctx, $events, 'amount_delimiter_flush');
}
```

#### Change B: Preserve last_numbers for inheritance (line 741)
```php
// Không clear last_numbers - cho phép kế thừa số sang phiếu tiếp theo nếu cần
// (Previously: $ctx['last_numbers'] = [];)
```

### 2. Test Suite

**File**: `tests/Unit/BettingMessageParserAmountDelimiterTest.php`

Created 7 comprehensive test cases:
1. ✅ Amount triggers flush and inherits stations
2. ✅ Amount flush with station change
3. ✅ Amount flush inherits numbers to next type
4. ✅ Multiple amount flushes in sequence
5. ✅ Combo token amount delimiter
6. ✅ Events show amount_delimiter_flush
7. ✅ Decimal amounts also trigger flush

### 3. Documentation

**File**: `AMOUNT_DELIMITER.md`

Complete feature documentation including:
- Overview and flow diagrams
- Inheritance rules
- 6 detailed examples
- Context clearing behavior
- Debug events
- Backward compatibility notes

## How It Works

### Core Concept

**Amount = End of Bet**

When parser encounters an amount token (5n, 3.5n, etc.):
1. If there's a pending bet group → **FLUSH immediately**
2. Save `last_numbers` and preserve `stations`
3. Next token can inherit according to rules

### Inheritance Rules

| Element | Rule |
|---------|------|
| **Stations** | Preserved within same group until new station token appears |
| **Numbers** | Inherited if next type has no new numbers specified |
| **Type** | Must be explicitly specified (lo, xc, dx, etc.) |

## Examples

### Example 1: Basic inheritance
```
Input:  "TN AG 13,21 lo 5n xc 3n"
```

**Parse flow:**
```
[TN] [AG] → stations = [TN, AG]
[13,21]   → numbers = [13, 21]
[lo]      → type = bao_lo
[5n]      → amount = 5000 → FLUSH!
            ↓ Emit: 4 bets (2 numbers × 2 stations)
            ↓ Save: last_numbers=[13,21], stations=[TN,AG]
[xc]      → type = xiu_chu
            ↓ Inherit: numbers from last_numbers (no new numbers)
[3n]      → amount = 3000 → FLUSH!
            ↓ Emit: 8 bets (2 numbers × 2 types × 2 stations)
```

**Output:** 12 bets total
- 4 bao_lo bets (13,21 × TN,AG)
- 8 xiu_chu bets (13,21 × dau/duoi × TN,AG)

### Example 2: Station changes between groups
```
Input:  "TN 13,21 lo 5n AG 31,41 xc 3n"
```

**Parse flow:**
```
[TN]      → stations = [TN]
[13,21]   → numbers = [13, 21]
[lo]      → type = bao_lo
[5n]      → FLUSH! (emit 2 bets: 13,21 × TN)
[AG]      → NEW STATION → stations = [AG] (reset)
[31,41]   → numbers = [31, 41] (new numbers)
[xc]      → type = xiu_chu
[3n]      → FLUSH! (emit 4 bets: 31,41 × dau/duoi × AG)
```

**Output:** 6 bets total
- 2 bao_lo bets (13,21 × TN)
- 4 xiu_chu bets (31,41 × dau/duoi × AG)

### Example 3: Multiple flushes
```
Input:  "tp, 13 lo 5n 21 dd 3n 31 xc 2n"
```

**Output:** 5 bets total
- 1 bao_lo (13 @ 5n)
- 2 dau_duoi (21 @ 3n → splits to dau + duoi)
- 2 xiu_chu (31 @ 2n → splits to dau + duoi)

### Example 4: Combo tokens
```
Input:  "tp, 13,21 lo5n xc 3n"
```

Combo token `lo5n` = `lo` + `5n` → triggers flush after setting type+amount

**Output:** 6 bets total
- 2 bao_lo (13,21 @ 5n)
- 4 xiu_chu (13,21 inherited × dau/duoi @ 3n)

## Testing

To verify the implementation:

```bash
# Run the test suite
php artisan test --filter=BettingMessageParserAmountDelimiterTest

# Expected output: All 7 tests pass ✅
```

## Backward Compatibility

✅ **Fully backward compatible**:
- All existing tests still pass
- Inheritance rules were already present, just extended
- No breaking changes to parser behavior
- Combo tokens still work as before

## Debug Events

New event added to debug output:

```php
[
    'kind' => 'amount_delimiter_flush',
    // Triggered when amount token causes flush
]
```

Use this to trace when amount-triggered flushes occur:

```php
$result = $parser->parse($input, $context);
$events = $result['debug']['events'];

foreach ($events as $event) {
    if ($event['kind'] === 'amount_delimiter_flush') {
        // Amount flush occurred
    }
}
```

## Related Features

This feature builds on:
1. **Station inheritance fix** (commit `58fd12a`) - Stations persist within group
2. **Xỉu chủ default split** (commit `b0f7272`) - XC splits to dau+duoi
3. **Đá xiên explicit stations** (commit `237bfa0`) - DX accepts explicit stations
4. **Decimal amount support** (commit `9fe58af`) - 3.5n, 7.5n amounts

## Next Steps

1. ✅ Implementation complete
2. ✅ Tests written
3. ✅ Documentation created
4. ✅ Changes committed
5. ✅ Pushed to remote

**Ready for review and testing!**

## Manual Testing Suggestions

Try these inputs to verify behavior:

```php
// Test 1: Basic inheritance
$parser->parse('TN AG 13,21 lo 5n xc 3n', $context);
// Expected: 4 bao_lo + 8 xiu_chu (stations inherited)

// Test 2: Station changes
$parser->parse('TN 13,21 lo 5n AG 31,41 xc 3n', $context);
// Expected: 2 bao_lo (TN) + 4 xiu_chu (AG, no inheritance)

// Test 3: Number inheritance
$parser->parse('tp, 13,21 lo 5n xc 3n', $context);
// Expected: 2 bao_lo + 4 xiu_chu (numbers inherited)

// Test 4: Decimal amounts
$parser->parse('tp, 13,21 lo 3.5n xc 2.5n', $context);
// Expected: 2 bao_lo @ 3500 + 4 xiu_chu @ 2500

// Test 5: Combo tokens
$parser->parse('tp, 13,21 lo5n xc 3n', $context);
// Expected: 2 bao_lo + 4 xiu_chu (inheritance after combo)
```

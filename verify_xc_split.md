# Xỉu Chủ (XC) Default Split Verification

## What Changed

**Before Fix:**
```
Input: "xc 903.361.121.204. 3n"
Output: 4 bets with type "Xỉu chủ"
- 903 xc 3n
- 361 xc 3n
- 121 xc 3n
- 204 xc 3n
```

**After Fix:**
```
Input: "xc 903.361.121.204. 3n"
Output: 8 bets (4 numbers x 2 types)
- 903 xiu_chu_dau 3n
- 903 xiu_chu_duoi 3n
- 361 xiu_chu_dau 3n
- 361 xiu_chu_duoi 3n
- 121 xiu_chu_dau 3n
- 121 xiu_chu_duoi 3n
- 204 xiu_chu_dau 3n
- 204 xiu_chu_duoi 3n
```

## Code Change

**File:** `app/Services/BettingMessageParser.php` (lines 265-272)

**Before:**
```php
} else {
    foreach ($numbers as $n) {
        $emitBet($outBets, $ctx, ['numbers'=>[$n],'type'=>'xiu_chu','amount'=>$amount]);
    }
    $addEvent($events, 'emit_xc_split_per_number', ['amount'=>$amount,'numbers'=>$numbers]);
}
```

**After:**
```php
} else {
    // Mặc định: tách thành xỉu chủ đầu + xỉu chủ đuôi
    foreach ($numbers as $n) {
        $emitBet($outBets, $ctx, ['numbers'=>[$n],'type'=>'xiu_chu_dau','amount'=>$amount]);
        $emitBet($outBets, $ctx, ['numbers'=>[$n],'type'=>'xiu_chu_duoi','amount'=>$amount]);
    }
    $addEvent($events, 'emit_xc_split_per_number_default', ['amount'=>$amount,'numbers'=>$numbers,'note'=>'Default split to dau+duoi']);
}
```

## Logic Flow

Xỉu chủ (xc) has 3 cases:

### Case 1: Explicit DD amount (dd5n)
```
Input: "xc 92 dd5n"
Result: 92 xiu_chu_dau 5n + 92 xiu_chu_duoi 5n
```

### Case 2: Explicit D sequence (d5n d3n)
```
Input: "xc 92 d5n d3n"
Result: 92 xiu_chu_dau 5n + 92 xiu_chu_duoi 3n
```

### Case 3: Default amount (3n) ← CHANGED!
```
Input: "xc 92 3n"
Before: 92 xiu_chu 3n (single bet)
After: 92 xiu_chu_dau 3n + 92 xiu_chu_duoi 3n (split to 2 bets)
```

## Impact

- **Only affects xiu_chu**: Other bet types (bao_lo, da_xien, etc.) unchanged
- **Backward compatible**: Cases 1 and 2 still work the same
- **New behavior**: Case 3 now auto-splits (doubles bet count for xc)

## Examples

### Example 1: Single number
```
Input: "tp, xc 92 5n"
Output: 2 bets
- 92 Xỉu chủ đầu 5n
- 92 Xỉu chủ đuôi 5n
```

### Example 2: Multiple numbers
```
Input: "xc 903.361.121.204. 3n"
Output: 8 bets
- 903 Xỉu chủ đầu 3n
- 903 Xỉu chủ đuôi 3n
- 361 Xỉu chủ đầu 3n
- 361 Xỉu chủ đuôi 3n
- 121 Xỉu chủ đầu 3n
- 121 Xỉu chủ đuôi 3n
- 204 Xỉu chủ đầu 3n
- 204 Xỉu chủ đuôi 3n
```

### Example 3: Decimal amount
```
Input: "xc 92 3.5n"
Output: 2 bets
- 92 Xỉu chủ đầu 3500
- 92 Xỉu chủ đuôi 3500
```

## Testing

Run test suite:
```bash
php artisan test --filter=BettingMessageParserXiuChuDefaultSplitTest
```

Expected: All tests pass ✅

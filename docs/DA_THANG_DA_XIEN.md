# Đá Thẳng và Đá Xiên Implementation

## Overview

This document describes the implementation of two specialized betting types in the Vietnamese lottery system:
- **Đá thẳng (dt)**: Single-station sequential pairing
- **Đá xiên (dx)**: Multi-station combinatorial betting

## Đá Thẳng (dt)

### Specification

**Station Requirements**: Exactly 1 station

**Number Pairing**: Sequential 2-2 pairing in order
- Input: `n1 n2 n3 n4 n5 n6`
- Pairs: `(n1,n2), (n3,n4), (n5,n6)`

**Odd Numbers**: If odd count, drop the last number and emit warning

### Examples

#### Example 1: Even number count
```
Input: tg 11 22 33 44 dt 10n

Output:
- 2 bets created
- Bet 1: numbers=[11,22], station="tien giang", amount=10000
- Bet 2: numbers=[33,44], station="tien giang", amount=10000
```

#### Example 2: Odd number count (warning)
```
Input: bt 11 22 33 dt 5n

Output:
- 1 bet created
- Bet 1: numbers=[11,22], station="ben tre", amount=5000
- Warning: "Số lẻ, bỏ số cuối: 33"
```

#### Example 3: Multiple stations (error)
```
Input: tg bt 11 22 dt 10n

Output:
- 0 bets created
- Error: "Đá thẳng yêu cầu đúng 1 đài" (expected=1, got=2)
```

### Implementation Details

Location: `app/Services/BettingMessageParser.php:309-352`

```php
if ($type === 'da_thang') {
    // 1. Validate exactly 1 station
    if (count($ctx['stations']) !== 1) {
        // Emit error event
        return;
    }

    // 2. Create pairs sequentially (2-2)
    $pairs = [];
    for ($i = 0; $i < count($numbers) - 1; $i += 2) {
        $pairs[] = [$numbers[$i], $numbers[$i + 1]];
    }

    // 3. Warn if odd count
    if (count($numbers) % 2 !== 0) {
        // Emit warning event
    }

    // 4. Emit one bet per pair
    foreach ($pairs as $pair) {
        $emitBet($outBets, $ctx, [
            'numbers' => $pair,
            'type'    => 'da_thang',
            'amount'  => $amount,
            'station' => $ctx['stations'][0]
        ]);
    }
}
```

## Đá Xiên (dx)

### Specification

**Station Requirements**: Minimum 2 stations (for cross-station betting)

**Number Combinations**: Generate all C(n,2) pairs from input numbers

**Station Pairs**: Generate all C(m,2) pairs from input stations

**Settlement Logic**: A bet wins if either:
1. Both numbers appear on the same station (either station), OR
2. Each station shows one of the numbers

### Combination Formulas

**C(n,2) = n × (n-1) / 2**

| Numbers | Pairs | Example |
|---------|-------|---------|
| 2 | 1 | (11,22) |
| 3 | 3 | (11,22), (11,33), (22,33) |
| 4 | 6 | (11,22), (11,33), (11,44), (22,33), (22,44), (33,44) |
| 5 | 10 | ... |

**Station Pairs**

| Stations | Pairs | Example |
|----------|-------|---------|
| 2 | 1 | (TN, BT) |
| 3 | 3 | (TG, BT), (TG, AG), (BT, AG) |
| 4 | 6 | (TG, BT), (TG, AG), (TG, TN), (BT, AG), (BT, TN), (AG, TN) |

### Examples

#### Example 1: 2 stations, 3 numbers
```
Input: tn bt 11 22 33 dx 1n

Output:
- 3 bets created (C(3,2) = 3)
- Bet 1: numbers=[11,22], amount=1000, station=null
  meta: {
    station_mode: 'across',
    station_pairs: [['tay ninh', 'ben tre']],
    dai_count: 2
  }
- Bet 2: numbers=[11,33], amount=1000, station=null
  meta: (same)
- Bet 3: numbers=[22,33], amount=1000, station=null
  meta: (same)
```

#### Example 2: 3 stations, 2 numbers
```
Input: tg bt ag 11 22 dx 5n

Output:
- 1 bet created (C(2,2) = 1)
- Bet 1: numbers=[11,22], amount=5000, station=null
  meta: {
    station_mode: 'across',
    station_pairs: [
      ['tien giang', 'ben tre'],
      ['tien giang', 'an giang'],
      ['ben tre', 'an giang']
    ],
    dai_count: 3
  }
```

#### Example 3: Single station (error)
```
Input: tg 11 22 dx 10n

Output:
- 0 bets created
- Error: "Đá xiên yêu cầu tối thiểu 2 đài" (expected>=2, got=1)
```

#### Example 4: 2 stations, 4 numbers
```
Input: tn bt 11 22 33 44 dx 2n

Output:
- 6 bets created (C(4,2) = 6)
- All bets have same meta.station_pairs: [['tay ninh', 'ben tre']]
- Number pairs: (11,22), (11,33), (11,44), (22,33), (22,44), (33,44)
```

### Implementation Details

Location: `app/Services/BettingMessageParser.php:354-408`

```php
if ($type === 'da_xien') {
    $stations = $ctx['stations'];

    // 1. Validate minimum 2 stations
    if (count($stations) < 2) {
        // Emit error event
        return;
    }

    // 2. Generate C(n,2) number pairs
    $numberPairs = [];
    for ($i = 0; $i < count($numbers); $i++) {
        for ($j = $i + 1; $j < count($numbers); $j++) {
            $numberPairs[] = [$numbers[$i], $numbers[$j]];
        }
    }

    // 3. Generate C(m,2) station pairs
    $stationPairs = [];
    for ($i = 0; $i < count($stations); $i++) {
        for ($j = $i + 1; $j < count($stations); $j++) {
            $stationPairs[] = [$stations[$i], $stations[$j]];
        }
    }

    // 4. Emit one bet per number pair
    foreach ($numberPairs as $pair) {
        $emitBet($outBets, $ctx, [
            'numbers' => $pair,
            'type'    => 'da_xien',
            'amount'  => $amount,
            'station' => null,  // Multi-station
            'meta'    => [
                'station_mode' => 'across',
                'station_pairs' => $stationPairs,
                'dai_count' => count($stations)
            ]
        ]);
    }
}
```

## Cost Calculation Formulas

### Đá Thẳng (1 station)
- **Miền Bắc**: `amount × số_cặp × 27 × buy_rate`
- **Miền Trung/Nam**: `amount × số_cặp × 2 × 18 × buy_rate`

Example:
```
tg 11 22 33 44 dt 10n (buy_rate=1)
= 10,000 × 2 (cặp) × 2 × 18 × 1
= 720,000 VND
```

### Đá Xiên (≥2 stations)

Cost multiplier based on station count:
- **2 stations**: `4 × 18 = 72` per bet
- **3 stations**: `4 × 3 × 18 = 216` per bet (4 × C(3,2))
- **4 stations**: `4 × 6 × 18 = 432` per bet (4 × C(4,2))

Example:
```
tn bt 11 22 33 dx 1n (buy_rate=1, 3 number pairs)
Each bet: 1,000 × 4 × 18 × 1 = 72,000 VND
Total: 3 × 72,000 = 216,000 VND
```

## Settlement Logic (Đá Xiên)

For a single bet with numbers (a, b) and station pair (X, Y):

**Winning conditions** (ANY of these):
1. ✅ Station X shows both a and b
2. ✅ Station Y shows both a and b
3. ✅ Station X shows a, Station Y shows b
4. ✅ Station X shows b, Station Y shows a

**Example**: Bet (11, 22) with stations (TN, BT)
- ✅ Win: TN shows 11, BT shows 22
- ✅ Win: TN shows 22, BT shows 11
- ✅ Win: TN shows both 11 and 22
- ✅ Win: BT shows both 11 and 22
- ❌ Loss: TN shows 11, BT shows neither

**For 3 stations** (TG, BT, AG) with 3 station pairs:
The bet wins if **ANY** of the 3 station pairs satisfy the winning conditions:
- Check pair (TG, BT)
- Check pair (TG, AG)
- Check pair (BT, AG)

## Testing

### Test Files Created

1. **Manual test descriptions**: `test_da_thang_da_xien.php`
   - Shows expected behavior for all test cases
   - Includes formulas and settlement logic

2. **PHPUnit tests**: `tests/Unit/BettingParser/DaThangDaXienParserTest.php`
   - 8 comprehensive test cases
   - Validates correct pairing, errors, and warnings

### Running Tests

```bash
# Run unit tests (requires database setup)
php artisan test --filter=DaThangDaXienParserTest
```

## Related Files

- **Parser**: `app/Services/BettingMessageParser.php`
- **Pricing**: `app/Services/BetPricingService.php`
- **Settlement**: `app/Services/BettingSettlementService.php`
- **Tests**: `tests/Unit/BettingParser/DaThangDaXienParserTest.php`
- **Models**: `app/Models/BettingTicket.php`

## Summary

| Feature | Đá Thẳng (dt) | Đá Xiên (dx) |
|---------|---------------|--------------|
| Stations | Exactly 1 | Minimum 2 |
| Pairing | Sequential 2-2 | C(n,2) combinations |
| Station Pairs | N/A | C(m,2) combinations |
| Bet Count | ⌊numbers/2⌋ | C(numbers,2) |
| Meta | Standard | station_pairs, dai_count, station_mode |
| Cost Multiplier | Based on region | Based on station count × 4 × 18 |

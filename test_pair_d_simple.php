<?php

// Mock classes to avoid database dependency
class BettingType {
    public static function aliasMap() {
        return [];
    }
}

class LotterySchedule {
    public static function forDateRegion($date, $region) {
        return null;
    }
}

namespace App\Services {
    class BetPricingService {
        public function calculateCostXac($type, $amount, $meta, $region, $station, $buyRate) {
            return $amount;
        }

        public function calculatePotentialWin($type, $amount, $meta, $region, $payout) {
            return 0;
        }
    }
}

namespace App\Models {
    class BettingType extends \BettingType {}
    class LotterySchedule extends \LotterySchedule {}
}

namespace {
    require __DIR__.'/app/Services/BettingMessageParser.php';

    $pricing = new App\Services\BetPricingService();
    $parser = new App\Services\BettingMessageParser($pricing);

    $input = "T,ninh  03 43 83 23 d35n d40n 27 65 05 69 85 d35n d70n 67 63 d0n d35n";

    echo "Input: $input\n\n";

    $result = $parser->parseMessage($input, ['region' => 'nam']);

    echo "Is Valid: " . ($result['is_valid'] ? 'true' : 'false') . "\n";
    echo "Total Bets: " . count($result['multiple_bets']) . "\n\n";

    // Group bets by their amount combination
    $groups = [];
    foreach ($result['multiple_bets'] as $bet) {
        $key = $bet['type'] . '_' . $bet['amount'];
        if (!isset($groups[$key])) {
            $groups[$key] = [];
        }
        $groups[$key][] = $bet['numbers'][0];
    }

    echo "=== Grouped Bets ===\n";
    foreach ($groups as $key => $numbers) {
        [$type, $amount] = explode('_', $key);
        $amountK = $amount / 1000;
        echo "  $type {$amountK}n: " . implode(', ', $numbers) . " (" . count($numbers) . " số)\n";
    }

    echo "\n=== Expected ===\n";
    echo "  Đầu 35n: 03, 43, 83, 23 (4 số)\n";
    echo "  Đuôi 40n: 03, 43, 83, 23 (4 số)\n";
    echo "  Đầu 35n: 27, 65, 05, 69, 85 (5 số)\n";
    echo "  Đuôi 70n: 27, 65, 05, 69, 85 (5 số)\n";
    echo "  Đuôi 35n: 67, 63 (2 số) [d0n bỏ qua]\n";
    echo "Total: 20 bets\n\n";

    echo "=== Debug Events ===\n";
    $autoFlushCount = 0;
    foreach ($result['debug']['events'] as $event) {
        if ($event['kind'] === 'pair_d_auto_flush') {
            $autoFlushCount++;
            echo "✓ Auto flush #$autoFlushCount triggered\n";
        }
        if ($event['kind'] === 'emit_pair_d') {
            $dauK = $event['dau'] / 1000;
            $duoiK = ($event['duoi'] ?? 0) / 1000;
            echo "✓ Emit pair_d: " . count($event['numbers']) . " numbers, đầu={$dauK}n, đuôi={$duoiK}n\n";
            echo "  Numbers: " . implode(', ', $event['numbers']) . "\n";
        }
    }

    if ($autoFlushCount === 3) {
        echo "\n✅ SUCCESS: Auto flush triggered 3 times as expected!\n";
    } else {
        echo "\n❌ FAILED: Expected 3 auto flushes, got $autoFlushCount\n";
    }
}

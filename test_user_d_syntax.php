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

    class LotteryScheduleService {
        public function getNStations($count, $date, $region) {
            return [];
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
    $scheduleService = new App\Services\LotteryScheduleService();
    $parser = new App\Services\BettingMessageParser($pricing, $scheduleService);

$input = "10 50 90 30 d150n d100n";

echo "Input: $input\n\n";

$result = $parser->parseMessage($input, ['region' => 'nam']);

echo "Is Valid: " . ($result['is_valid'] ? 'true' : 'false') . "\n";
echo "Total Bets: " . count($result['multiple_bets']) . "\n\n";

echo "=== Bets ===\n";
foreach ($result['multiple_bets'] as $idx => $bet) {
    $amountK = $bet['amount'] / 1000;
    echo sprintf("#%d: %s %s %sn @ %s\n",
        $idx + 1,
        $bet['numbers'][0],
        $bet['type'],
        $amountK,
        $bet['station']
    );
}

echo "\n=== Expected ===\n";
echo "Group: 10, 50, 90, 30\n";
echo "  - đầu 150n for each number (4 bets)\n";
echo "  - đuôi 100n for each number (4 bets)\n";
echo "Total: 8 bets\n\n";

echo "=== Debug Events (pair_d related) ===\n";
foreach ($result['debug']['events'] as $event) {
    if (strpos($event['kind'], 'pair_d') !== false ||
        strpos($event['kind'], 'combo') !== false ||
        strpos($event['kind'], 'emit') !== false) {
        echo json_encode($event, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    }
}
}

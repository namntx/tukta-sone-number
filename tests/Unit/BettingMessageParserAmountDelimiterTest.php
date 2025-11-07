<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\BettingMessageParser;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BettingMessageParserAmountDelimiterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\BettingTypesSeeder::class);
    }

    /**
     * Test: Amount triggers flush and next type inherits stations
     * Input: "TN AG 13,21 lo 5n xc 3n"
     * Expected:
     * - 2 bao_lo bets (13, 21) with TN station
     * - 2 bao_lo bets (13, 21) with AG station
     * - 2x2 xiu_chu bets (13, 21) dau+duoi with TN station
     * - 2x2 xiu_chu bets (13, 21) dau+duoi with AG station
     */
    public function test_amount_triggers_flush_and_inherits_stations()
    {
        $parser = app(BettingMessageParser::class);

        $input = 'TN AG 13,21 lo 5n xc 3n';

        $result = $parser->parse($input, [
            'date' => '2025-11-03',
            'region' => 'nam'
        ]);

        // Should be valid
        $this->assertTrue($result['is_valid'], 'Should be valid');

        // Filter bao_lo bets
        $baoloBets = array_values(array_filter(
            $result['multiple_bets'],
            fn($b) => $b['type'] === 'Bao lô 2 số'
        ));

        // Filter xiu_chu bets
        $xcBets = array_values(array_filter(
            $result['multiple_bets'],
            fn($b) => in_array($b['type'], ['Xỉu chủ đầu', 'Xỉu chủ đuôi'])
        ));

        // Should have 4 bao_lo bets (2 numbers x 2 stations)
        $this->assertCount(4, $baoloBets, 'Should have 4 bao_lo bets');

        // Should have 8 xiu_chu bets (2 numbers x 2 types x 2 stations)
        $this->assertCount(8, $xcBets, 'Should have 8 xiu_chu bets (inherited stations)');

        // Check bao_lo stations
        $baoloStations = array_unique(array_map(fn($b) => $b['station'], $baoloBets));
        $this->assertContains('tay ninh', $baoloStations);
        $this->assertContains('an giang', $baoloStations);

        // Check xiu_chu stations (should also have TN and AG)
        $xcStations = array_unique(array_map(fn($b) => $b['station'], $xcBets));
        $this->assertContains('tay ninh', $xcStations);
        $this->assertContains('an giang', $xcStations);

        // Check that xiu_chu inherited the numbers (13, 21)
        $xcNumbers = array_unique(array_merge(...array_map(fn($b) => $b['numbers'], $xcBets)));
        $this->assertContains('13', $xcNumbers);
        $this->assertContains('21', $xcNumbers);
    }

    /**
     * Test: Amount triggers flush, next group with new stations
     * Input: "TN 13,21 lo 5n AG 31,41 xc 3n"
     * Expected:
     * - 2 bao_lo bets with TN (13, 21)
     * - 4 xiu_chu bets (31, 41) dau+duoi with AG
     */
    public function test_amount_flush_with_station_change()
    {
        $parser = app(BettingMessageParser::class);

        $input = 'TN 13,21 lo 5n AG 31,41 xc 3n';

        $result = $parser->parse($input, [
            'date' => '2025-11-03',
            'region' => 'nam'
        ]);

        $this->assertTrue($result['is_valid'], 'Should be valid');

        // Filter bao_lo bets
        $baoloBets = array_values(array_filter(
            $result['multiple_bets'],
            fn($b) => $b['type'] === 'Bao lô 2 số'
        ));

        // Filter xiu_chu bets
        $xcBets = array_values(array_filter(
            $result['multiple_bets'],
            fn($b) => in_array($b['type'], ['Xỉu chủ đầu', 'Xỉu chủ đuôi'])
        ));

        // Should have 2 bao_lo bets with TN
        $this->assertCount(2, $baoloBets, 'Should have 2 bao_lo bets');
        foreach ($baoloBets as $bet) {
            $this->assertEquals('tay ninh', $bet['station']);
        }

        // Should have 4 xiu_chu bets with AG (31, 41 x dau+duoi)
        $this->assertCount(4, $xcBets, 'Should have 4 xiu_chu bets');
        foreach ($xcBets as $bet) {
            $this->assertEquals('an giang', $bet['station']);
        }

        // Check numbers
        $xcNumbers = array_unique(array_merge(...array_map(fn($b) => $b['numbers'], $xcBets)));
        $this->assertContains('31', $xcNumbers);
        $this->assertContains('41', $xcNumbers);
    }

    /**
     * Test: Amount flush with number inheritance (no new numbers)
     * Input: "13,21 lo 5n xc 3n"
     * Expected:
     * - 2 bao_lo bets (13, 21)
     * - 4 xiu_chu bets (13, 21) dau+duoi (inherited)
     */
    public function test_amount_flush_inherits_numbers_to_next_type()
    {
        $parser = app(BettingMessageParser::class);

        $input = 'tp, 13,21 lo 5n xc 3n';

        $result = $parser->parse($input, [
            'date' => '2025-11-03',
            'region' => 'nam'
        ]);

        $this->assertTrue($result['is_valid'], 'Should be valid');

        // Filter bao_lo bets
        $baoloBets = array_values(array_filter(
            $result['multiple_bets'],
            fn($b) => $b['type'] === 'Bao lô 2 số'
        ));

        // Filter xiu_chu bets
        $xcBets = array_values(array_filter(
            $result['multiple_bets'],
            fn($b) => in_array($b['type'], ['Xỉu chủ đầu', 'Xỉu chủ đuôi'])
        ));

        // Should have 2 bao_lo bets
        $this->assertCount(2, $baoloBets);

        // Should have 4 xiu_chu bets (inherited numbers)
        $this->assertCount(4, $xcBets);

        // Check that xc inherited the same numbers
        $baoloNumbers = array_values(array_unique(array_merge(...array_map(fn($b) => $b['numbers'], $baoloBets))));
        $xcNumbers = array_values(array_unique(array_merge(...array_map(fn($b) => $b['numbers'], $xcBets))));

        sort($baoloNumbers);
        sort($xcNumbers);

        $this->assertEquals($baoloNumbers, $xcNumbers, 'XC should inherit numbers from bao_lo');
    }

    /**
     * Test: Multiple amount flushes in sequence
     * Input: "tp, 13 lo 5n 21 dd 3n 31 xc 2n"
     * Expected: 3 separate bet groups
     */
    public function test_multiple_amount_flushes_in_sequence()
    {
        $parser = app(BettingMessageParser::class);

        $input = 'tp, 13 lo 5n 21 dd 3n 31 xc 2n';

        $result = $parser->parse($input, [
            'date' => '2025-11-03',
            'region' => 'nam'
        ]);

        $this->assertTrue($result['is_valid'], 'Should be valid');

        // Filter by type
        $baoloBets = array_filter($result['multiple_bets'], fn($b) => $b['type'] === 'Bao lô 2 số');
        $dauBets = array_filter($result['multiple_bets'], fn($b) => $b['type'] === 'Đầu');
        $duoiBets = array_filter($result['multiple_bets'], fn($b) => $b['type'] === 'Đuôi');
        $xcBets = array_filter($result['multiple_bets'], fn($b) => in_array($b['type'], ['Xỉu chủ đầu', 'Xỉu chủ đuôi']));

        // Should have separate bets for each group
        $this->assertCount(1, $baoloBets, 'Should have 1 bao_lo bet (13)');
        $this->assertCount(1, $dauBets, 'Should have 1 dau bet (21)');
        $this->assertCount(1, $duoiBets, 'Should have 1 duoi bet (21)');
        $this->assertCount(2, $xcBets, 'Should have 2 xiu_chu bets (31 dau+duoi)');

        // Check amounts
        $this->assertEquals(5000, array_values($baoloBets)[0]['amount']);
        $this->assertEquals(3000, array_values($dauBets)[0]['amount']);
        $this->assertEquals(2000, array_values($xcBets)[0]['amount']);
    }

    /**
     * Test: Amount delimiter with combo tokens
     * Input: "tp, 13,21 lo5n xc 3n"
     * Expected: Combo token lo5n triggers flush, xc inherits numbers
     */
    public function test_combo_token_amount_delimiter()
    {
        $parser = app(BettingMessageParser::class);

        $input = 'tp, 13,21 lo5n xc 3n';

        $result = $parser->parse($input, [
            'date' => '2025-11-03',
            'region' => 'nam'
        ]);

        $this->assertTrue($result['is_valid'], 'Should be valid');

        // Filter by type
        $baoloBets = array_filter($result['multiple_bets'], fn($b) => $b['type'] === 'Bao lô 2 số');
        $xcBets = array_filter($result['multiple_bets'], fn($b) => in_array($b['type'], ['Xỉu chủ đầu', 'Xỉu chủ đuôi']));

        // Should have 2 bao_lo bets
        $this->assertCount(2, $baoloBets);

        // Should have 4 xiu_chu bets (inherited)
        $this->assertCount(4, $xcBets);

        // Check inheritance
        $baoloNumbers = array_values(array_unique(array_merge(...array_map(fn($b) => $b['numbers'], $baoloBets))));
        $xcNumbers = array_values(array_unique(array_merge(...array_map(fn($b) => $b['numbers'], $xcBets))));

        sort($baoloNumbers);
        sort($xcNumbers);

        $this->assertEquals($baoloNumbers, $xcNumbers, 'XC should inherit numbers after combo token flush');
    }

    /**
     * Test: Events show amount_delimiter_flush
     */
    public function test_events_show_amount_delimiter_flush()
    {
        $parser = app(BettingMessageParser::class);

        $input = 'tp, 13,21 lo 5n xc 3n';

        $result = $parser->parse($input, [
            'date' => '2025-11-03',
            'region' => 'nam'
        ]);

        // Check events for amount_delimiter_flush
        $events = $result['debug']['events'] ?? [];
        $hasAmountFlush = false;

        foreach ($events as $event) {
            if (($event['kind'] ?? '') === 'amount_delimiter_flush') {
                $hasAmountFlush = true;
                break;
            }
        }

        $this->assertTrue($hasAmountFlush, 'Should have amount_delimiter_flush event');
    }

    /**
     * Test: Decimal amounts also trigger flush
     * Input: "tp, 13,21 lo 3.5n xc 2.5n"
     */
    public function test_decimal_amount_also_triggers_flush()
    {
        $parser = app(BettingMessageParser::class);

        $input = 'tp, 13,21 lo 3.5n xc 2.5n';

        $result = $parser->parse($input, [
            'date' => '2025-11-03',
            'region' => 'nam'
        ]);

        $this->assertTrue($result['is_valid'], 'Should be valid');

        // Filter by type
        $baoloBets = array_filter($result['multiple_bets'], fn($b) => $b['type'] === 'Bao lô 2 số');
        $xcBets = array_filter($result['multiple_bets'], fn($b) => in_array($b['type'], ['Xỉu chủ đầu', 'Xỉu chủ đuôi']));

        // Check amounts
        $this->assertEquals(3500, array_values($baoloBets)[0]['amount']);
        $this->assertEquals(2500, array_values($xcBets)[0]['amount']);

        // XC should inherit numbers
        $baoloNumbers = array_values(array_unique(array_merge(...array_map(fn($b) => $b['numbers'], $baoloBets))));
        $xcNumbers = array_values(array_unique(array_merge(...array_map(fn($b) => $b['numbers'], $xcBets))));

        sort($baoloNumbers);
        sort($xcNumbers);

        $this->assertEquals($baoloNumbers, $xcNumbers);
    }
}

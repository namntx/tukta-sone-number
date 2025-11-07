<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\BettingMessageParser;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BettingMessageParserNdaiResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\BettingTypesSeeder::class);
    }

    /**
     * Test: Ndai mode resets after flush, explicit stations work correctly
     *
     * Input: "3dai - 53,19 đax 2.1n 2dai 47,57 đax 1.4n ag bth 89,15 đax 2.1n"
     *
     * Expected:
     * 1. Đá xiên [53,19] with 3 auto-resolved stations @ 2100
     * 2. Đá xiên [47,57] with 2 auto-resolved stations @ 1400
     * 3. Đá xiên [89,15] with EXPLICIT stations [ag, bth] @ 2100 ✅
     *
     * Bug was: Cược 3 vẫn capture ag/bth thay vì dùng explicit stations
     * Fix: Reset dai_count và dai_capture_remaining sau mỗi flush
     */
    public function test_ndai_mode_resets_after_flush_explicit_stations_work()
    {
        $parser = app(BettingMessageParser::class);

        $input = '3dai - 53,19 đax 2.1n 2dai 47,57 đax 1.4n ag bth 89,15 đax 2.1n';

        $result = $parser->parse($input, [
            'date' => '2025-11-03',
            'region' => 'nam'
        ]);

        $this->assertTrue($result['is_valid'], 'Should be valid');

        // Filter da_xien bets
        $dxBets = array_values(array_filter(
            $result['multiple_bets'],
            fn($b) => $b['type'] === 'Đá xiên'
        ));

        // Should have exactly 3 da_xien bets
        $this->assertCount(3, $dxBets, 'Should have 3 da_xien bets');

        // Bet 1: [53,19] with 3 auto-resolved stations (TN, AG, BTH)
        $bet1 = $dxBets[0];
        $bet1Numbers = $bet1['numbers'];
        sort($bet1Numbers);
        $this->assertEquals(['19', '53'], $bet1Numbers);
        $this->assertEquals(2100, $bet1['amount']);

        // Check for 3 stations (auto-resolved)
        $bet1Stations = explode(' + ', $bet1['station']);
        $this->assertCount(3, $bet1Stations, 'Bet 1 should have 3 auto-resolved stations');

        // Bet 2: [47,57] with 2 auto-resolved stations (TN, AG)
        $bet2 = $dxBets[1];
        $bet2Numbers = $bet2['numbers'];
        sort($bet2Numbers);
        $this->assertEquals(['47', '57'], $bet2Numbers);
        $this->assertEquals(1400, $bet2['amount']);

        // Check for 2 stations (auto-resolved)
        $bet2Stations = explode(' + ', $bet2['station']);
        $this->assertCount(2, $bet2Stations, 'Bet 2 should have 2 auto-resolved stations');

        // Bet 3: [89,15] with EXPLICIT stations [ag, bth] ONLY
        $bet3 = $dxBets[2];
        $bet3Numbers = $bet3['numbers'];
        sort($bet3Numbers);
        $this->assertEquals(['15', '89'], $bet3Numbers);
        $this->assertEquals(2100, $bet3['amount']);

        // CRITICAL: Should have EXACTLY 2 explicit stations [ag, bth]
        $bet3Stations = explode(' + ', $bet3['station']);
        $this->assertCount(2, $bet3Stations, 'Bet 3 should have 2 explicit stations');
        $this->assertContains('an giang', $bet3Stations, 'Bet 3 should have an giang');
        $this->assertContains('binh thuan', $bet3Stations, 'Bet 3 should have binh thuan');

        // Should NOT have auto-resolved 3rd station
        $this->assertNotContains('tay ninh', $bet3Stations, 'Bet 3 should NOT have tay ninh (not in explicit list)');
    }

    /**
     * Test: Ndai followed immediately by explicit stations
     * Input: "2dai ag bth 11,22 đax 1.5n"
     *
     * Expected: Use explicit stations [ag, bth], NOT auto-resolve
     */
    public function test_ndai_with_explicit_stations_immediate()
    {
        $parser = app(BettingMessageParser::class);

        $input = '2dai ag bth 11,22 đax 1.5n';

        $result = $parser->parse($input, [
            'date' => '2025-11-03',
            'region' => 'nam'
        ]);

        $this->assertTrue($result['is_valid'], 'Should be valid');

        $dxBets = array_filter($result['multiple_bets'], fn($b) => $b['type'] === 'Đá xiên');
        $this->assertCount(1, $dxBets, 'Should have 1 da_xien bet');

        $bet = array_values($dxBets)[0];

        // Check stations - should be explicit [ag, bth]
        $stations = explode(' + ', $bet['station']);
        $this->assertCount(2, $stations);
        $this->assertContains('an giang', $stations);
        $this->assertContains('binh thuan', $stations);
    }

    /**
     * Test: Multiple Ndai directives with explicit stations between
     * Input: "2dai 11,22 đax 1n tp ct 33,44 đax 2n 3dai 55,66 đax 3n"
     *
     * Expected:
     * 1. [11,22] with 2 auto-resolved stations
     * 2. [33,44] with explicit [tp, ct]
     * 3. [55,66] with 3 auto-resolved stations
     */
    public function test_multiple_ndai_with_explicit_stations_between()
    {
        $parser = app(BettingMessageParser::class);

        $input = '2dai 11,22 đax 1n tp ct 33,44 đax 2n 3dai 55,66 đax 3n';

        $result = $parser->parse($input, [
            'date' => '2025-11-03',
            'region' => 'nam'
        ]);

        $this->assertTrue($result['is_valid'], 'Should be valid');

        $dxBets = array_values(array_filter($result['multiple_bets'], fn($b) => $b['type'] === 'Đá xiên'));
        $this->assertCount(3, $dxBets, 'Should have 3 da_xien bets');

        // Bet 1: auto-resolved 2 stations
        $bet1Stations = explode(' + ', $dxBets[0]['station']);
        $this->assertCount(2, $bet1Stations, 'Bet 1 should have 2 auto-resolved stations');

        // Bet 2: explicit [tp, ct]
        $bet2Stations = explode(' + ', $dxBets[1]['station']);
        $this->assertCount(2, $bet2Stations, 'Bet 2 should have 2 explicit stations');
        $this->assertContains('tp.hcm', $bet2Stations);
        $this->assertContains('can tho', $bet2Stations);

        // Bet 3: auto-resolved 3 stations
        $bet3Stations = explode(' + ', $dxBets[2]['station']);
        $this->assertCount(3, $bet3Stations, 'Bet 3 should have 3 auto-resolved stations');
    }

    /**
     * Test: Ndai mode does NOT carry over after final_flush
     * Input: Two separate messages with Ndai
     */
    public function test_ndai_mode_does_not_carry_over_messages()
    {
        $parser = app(BettingMessageParser::class);

        // First message with 2dai
        $input1 = '2dai 11,22 đax 1n';
        $result1 = $parser->parse($input1, ['date' => '2025-11-03', 'region' => 'nam']);

        $dxBets1 = array_filter($result1['multiple_bets'], fn($b) => $b['type'] === 'Đá xiên');
        $this->assertCount(1, $dxBets1);

        $bet1Stations = explode(' + ', array_values($dxBets1)[0]['station']);
        $this->assertCount(2, $bet1Stations, 'First message should have 2 auto-resolved stations');

        // Second message with explicit stations (should NOT capture from previous Ndai)
        $input2 = 'ag bth 33,44 đax 2n';
        $result2 = $parser->parse($input2, ['date' => '2025-11-03', 'region' => 'nam']);

        $dxBets2 = array_filter($result2['multiple_bets'], fn($b) => $b['type'] === 'Đá xiên');
        $this->assertCount(1, $dxBets2);

        $bet2Stations = explode(' + ', array_values($dxBets2)[0]['station']);
        $this->assertCount(2, $bet2Stations, 'Second message should have 2 explicit stations');
        $this->assertContains('an giang', $bet2Stations);
        $this->assertContains('binh thuan', $bet2Stations);
    }

    /**
     * Test: Events show ndai_mode_reset_after_flush
     */
    public function test_events_show_ndai_reset_after_flush()
    {
        $parser = app(BettingMessageParser::class);

        $input = '2dai 11,22 đax 1n ag bth 33,44 đax 2n';

        $result = $parser->parse($input, [
            'date' => '2025-11-03',
            'region' => 'nam'
        ]);

        $events = $result['debug']['events'] ?? [];

        // Should have ndai_mode_reset_after_flush event after first flush
        $hasReset = false;
        foreach ($events as $event) {
            if (($event['kind'] ?? '') === 'ndai_mode_reset_after_flush') {
                $hasReset = true;
                break;
            }
        }

        $this->assertTrue($hasReset, 'Should have ndai_mode_reset_after_flush event');
    }

    /**
     * Test: Combo token also triggers Ndai reset
     * Input: "2dai 11,22 đax1n ag 33,44 đax 2n"
     */
    public function test_combo_token_triggers_ndai_reset()
    {
        $parser = app(BettingMessageParser::class);

        $input = '2dai 11,22 đax1n ag 33,44 đax 2n';

        $result = $parser->parse($input, [
            'date' => '2025-11-03',
            'region' => 'nam'
        ]);

        $this->assertTrue($result['is_valid'], 'Should be valid');

        $dxBets = array_values(array_filter($result['multiple_bets'], fn($b) => $b['type'] === 'Đá xiên'));
        $this->assertCount(2, $dxBets);

        // Bet 1: auto-resolved 2 stations
        $bet1Stations = explode(' + ', $dxBets[0]['station']);
        $this->assertCount(2, $bet1Stations);

        // Bet 2: should have ag explicitly (not captured)
        // Since only ag is specified, it should be just ag
        $bet2Station = $dxBets[1]['station'];
        $this->assertEquals('an giang', $bet2Station, 'Bet 2 should have an giang as explicit station');
    }
}

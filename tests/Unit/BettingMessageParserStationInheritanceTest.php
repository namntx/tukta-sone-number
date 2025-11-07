<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\BettingMessageParser;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BettingMessageParserStationInheritanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\BettingTypesSeeder::class);
    }

    /**
     * Test: Input 1 - "TN AG 13,21 lo 5n dx 5n"
     * Expected: 1 đá xiên + 2 lô TN + 2 lô AG (5 bets total)
     */
    public function test_input_1_lo_before_dx_works()
    {
        $parser = app(BettingMessageParser::class);

        // Input: TN AG 13,21 lo 5n dx 5n
        $input = 'TN AG 13,21 lo 5n dx 5n';

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

        // Filter da_xien bets
        $daxienBets = array_values(array_filter(
            $result['multiple_bets'],
            fn($b) => $b['type'] === 'Đá xiên'
        ));

        // Should have 4 bao_lo bets (2 numbers x 2 stations)
        $this->assertCount(4, $baoloBets, 'Should have 4 bao_lo bets (2 numbers x 2 stations)');

        // Should have 1 da_xien bet
        $this->assertCount(1, $daxienBets, 'Should have 1 da_xien bet');

        // Check bao_lo stations
        $baoloStations = array_map(fn($b) => $b['station'], $baoloBets);
        $this->assertContains('tay ninh', $baoloStations, 'Should have tay ninh bao_lo bets');
        $this->assertContains('an giang', $baoloStations, 'Should have an giang bao_lo bets');

        // Count per station
        $tnCount = count(array_filter($baoloStations, fn($s) => $s === 'tay ninh'));
        $agCount = count(array_filter($baoloStations, fn($s) => $s === 'an giang'));

        $this->assertEquals(2, $tnCount, 'Should have 2 TN bao_lo bets');
        $this->assertEquals(2, $agCount, 'Should have 2 AG bao_lo bets');
    }

    /**
     * Test: Input 2 - "TN AG 13,21 dx 5n lo 5n"
     * Expected: 1 đá xiên + 2 lô TN + 2 lô AG (5 bets total)
     * Bug: Chỉ có 1 đá xiên + 2 lô TN, bỏ qua 2 lô AG
     */
    public function test_input_2_dx_before_lo_should_also_work()
    {
        $parser = app(BettingMessageParser::class);

        // Input: TN AG 13,21 dx 5n lo 5n
        $input = 'TN AG 13,21 dx 5n lo 5n';

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

        // Filter da_xien bets
        $daxienBets = array_values(array_filter(
            $result['multiple_bets'],
            fn($b) => $b['type'] === 'Đá xiên'
        ));

        // Should have 4 bao_lo bets (2 numbers x 2 stations)
        $this->assertCount(4, $baoloBets, 'Should have 4 bao_lo bets (2 numbers x 2 stations)');

        // Should have 1 da_xien bet
        $this->assertCount(1, $daxienBets, 'Should have 1 da_xien bet');

        // Check bao_lo stations
        $baoloStations = array_map(fn($b) => $b['station'], $baoloBets);
        $this->assertContains('tay ninh', $baoloStations, 'Should have tay ninh bao_lo bets');
        $this->assertContains('an giang', $baoloStations, 'Should have an giang bao_lo bets');

        // Count per station
        $tnCount = count(array_filter($baoloStations, fn($s) => $s === 'tay ninh'));
        $agCount = count(array_filter($baoloStations, fn($s) => $s === 'an giang'));

        $this->assertEquals(2, $tnCount, 'Should have 2 TN bao_lo bets');
        $this->assertEquals(2, $agCount, 'Should have 2 AG bao_lo bets');
    }

    /**
     * Test: Stations should reset when new station token appears
     */
    public function test_stations_reset_on_new_station_token()
    {
        $parser = app(BettingMessageParser::class);

        // Input: TN AG 13,21 dx 5n... VT BL 79,29 lo 5n
        // Two separate groups with different stations
        $input = 'TN AG 13,21 dx 5n...   VT BL 79,29 lo 5n';

        $result = $parser->parse($input, [
            'date' => '2025-11-03',
            'region' => 'nam'
        ]);

        // Filter bao_lo bets
        $baoloBets = array_values(array_filter(
            $result['multiple_bets'],
            fn($b) => $b['type'] === 'Bao lô 2 số'
        ));

        // Check that second group has VT and BL, NOT TN and AG
        $baoloStations = array_map(fn($b) => $b['station'], $baoloBets);

        $this->assertContains('vung tau', $baoloStations, 'Should have vung tau bao_lo bets');
        $this->assertContains('bac lieu', $baoloStations, 'Should have bac lieu bao_lo bets');

        // Should NOT have TN/AG contamination in bao_lo (only in da_xien)
        // Bao_lo numbers are 79,29 (second group), not 13,21
        $baoloNumbers = array_unique(array_merge(...array_map(fn($b) => $b['numbers'], $baoloBets)));
        $this->assertContains('79', $baoloNumbers);
        $this->assertContains('29', $baoloNumbers);
    }

    /**
     * Test: Same stations should be shared within same group
     */
    public function test_stations_shared_within_same_group()
    {
        $parser = app(BettingMessageParser::class);

        // Input: TN AG 13 lo 5n 21 xc 3n
        // Same stations for both lo and xc
        $input = 'TN AG 13 lo 5n 21 xc 3n';

        $result = $parser->parse($input, [
            'date' => '2025-11-03',
            'region' => 'nam'
        ]);

        // Filter bao_lo bets
        $baoloBets = array_values(array_filter(
            $result['multiple_bets'],
            fn($b) => $b['type'] === 'Bao lô 2 số'
        ));

        // Filter xiu_chu bets (dau + duoi)
        $xcBets = array_values(array_filter(
            $result['multiple_bets'],
            fn($b) => in_array($b['type'], ['Xỉu chủ đầu', 'Xỉu chủ đuôi'])
        ));

        // Both should have TN and AG stations
        $baoloStations = array_unique(array_map(fn($b) => $b['station'], $baoloBets));
        $xcStations = array_unique(array_map(fn($b) => $b['station'], $xcBets));

        $this->assertContains('tay ninh', $baoloStations);
        $this->assertContains('an giang', $baoloStations);
        $this->assertContains('tay ninh', $xcStations);
        $this->assertContains('an giang', $xcStations);
    }
}

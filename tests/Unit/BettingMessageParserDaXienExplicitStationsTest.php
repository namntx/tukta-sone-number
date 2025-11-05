<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\BettingMessageParser;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BettingMessageParserDaXienExplicitStationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\BettingTypesSeeder::class);
    }

    /**
     * Test: Đá xiên với explicit stations (không cần 2d/3d directive)
     * Fix: User có thể nhập trực tiếp các đài mà không cần 2d/3d
     */
    public function test_da_xien_accepts_explicit_stations_without_ndai_directive()
    {
        $parser = app(BettingMessageParser::class);

        // Input: vt bt 22,29 dx 1.4n (2 đài explicit: vung tau, ben tre)
        $input = 'vt bt 22,29 dx 1.4n';

        $result = $parser->parse($input, [
            'date' => '2025-11-03',
            'region' => 'nam'
        ]);

        // Should succeed (not error)
        $this->assertTrue($result['is_valid'], 'Should be valid with 2 explicit stations');
        $this->assertNotEmpty($result['multiple_bets'], 'Should have bets');

        // Check that da_xien bets were created
        $daXienBets = array_filter($result['multiple_bets'], fn($b) => $b['type'] === 'da_xien');
        $this->assertNotEmpty($daXienBets, 'Should have da_xien bets');

        // Check that stations are correct
        $firstBet = array_values($daXienBets)[0];
        $this->assertStringContainsString('vung tau', $firstBet['station'], 'Should include vung tau');
        $this->assertStringContainsString('ben tre', $firstBet['station'], 'Should include ben tre');

        // Check amount
        $this->assertEquals(1400, $firstBet['amount'], 'Amount should be 1400 (1.4n)');
    }

    /**
     * Test: Multiple da_xien groups with different explicit stations
     */
    public function test_multiple_da_xien_groups_with_different_stations()
    {
        $parser = app(BettingMessageParser::class);

        // Input: First group (vt bt) and second group (vt bl)
        $input = 'vt bt 22,29 dx 1.4n...   vt bl 79,29 dx 0.7n';

        $result = $parser->parse($input, [
            'date' => '2025-11-03',
            'region' => 'nam'
        ]);

        // Should succeed
        $this->assertTrue($result['is_valid'], 'Should be valid with multiple groups');
        $this->assertEmpty($result['errors'], 'Should have no errors');

        // Should have bets from both groups
        $daXienBets = array_filter($result['multiple_bets'], fn($b) => $b['type'] === 'da_xien');
        $this->assertNotEmpty($daXienBets, 'Should have da_xien bets');

        // Check amounts (should have different amounts for each group)
        $amounts = array_unique(array_map(fn($b) => $b['amount'], $daXienBets));
        $this->assertContains(1400, $amounts, 'Should have 1.4n amount');
        $this->assertContains(700, $amounts, 'Should have 0.7n amount');

        // Check stations (should have different station pairs)
        $stations = array_map(fn($b) => $b['station'], $daXienBets);
        $hasVtBt = false;
        $hasVtBl = false;
        foreach ($stations as $station) {
            if (str_contains($station, 'vung tau') && str_contains($station, 'ben tre')) {
                $hasVtBt = true;
            }
            if (str_contains($station, 'vung tau') && str_contains($station, 'bac lieu')) {
                $hasVtBl = true;
            }
        }
        $this->assertTrue($hasVtBt, 'Should have vung tau + ben tre group');
        $this->assertTrue($hasVtBl, 'Should have vung tau + bac lieu group');
    }

    /**
     * Test: Đá xiên with 3 explicit stations
     */
    public function test_da_xien_with_three_explicit_stations()
    {
        $parser = app(BettingMessageParser::class);

        // Input: 3 stations (vt bt cm)
        $input = 'vt bt cm 22,29 dx 1n';

        $result = $parser->parse($input, [
            'date' => '2025-11-03',
            'region' => 'nam'
        ]);

        // Should succeed
        $this->assertTrue($result['is_valid'], 'Should be valid with 3 explicit stations');

        $daXienBets = array_filter($result['multiple_bets'], fn($b) => $b['type'] === 'da_xien');
        $this->assertNotEmpty($daXienBets, 'Should have da_xien bets');

        // With 3 stations, should have C(3,2) = 3 station pairs per number pair
        $firstBet = array_values($daXienBets)[0];
        $stationPairs = $firstBet['meta']['station_pairs'] ?? [];
        $this->assertCount(3, $stationPairs, 'Should have 3 station pairs for 3 stations');
    }

    /**
     * Test: Đá xiên should still reject with only 1 station
     */
    public function test_da_xien_rejects_single_station()
    {
        $parser = app(BettingMessageParser::class);

        // Input: Only 1 station
        $input = 'vt 22,29 dx 1n';

        $result = $parser->parse($input, [
            'date' => '2025-11-03',
            'region' => 'nam'
        ]);

        // Should NOT succeed (needs at least 2 stations)
        $this->assertFalse($result['is_valid'], 'Should be invalid with only 1 station');

        // Check events for error
        $events = $result['debug']['events'] ?? [];
        $hasError = false;
        foreach ($events as $event) {
            if (($event['kind'] ?? '') === 'error_da_xien_min_stations') {
                $hasError = true;
                break;
            }
        }
        $this->assertTrue($hasError, 'Should have error_da_xien_min_stations event');
    }

    /**
     * Test: Đá xiên with 2d directive still works (backward compatibility)
     */
    public function test_da_xien_with_2d_directive_still_works()
    {
        $parser = app(BettingMessageParser::class);

        // Input: With 2d directive (old style)
        $input = '2d 22,29 dx 1n';

        $result = $parser->parse($input, [
            'date' => '2025-11-03',
            'region' => 'nam'
        ]);

        // Should succeed (auto-resolve 2 stations)
        $this->assertTrue($result['is_valid'], 'Should be valid with 2d directive');

        $daXienBets = array_filter($result['multiple_bets'], fn($b) => $b['type'] === 'da_xien');
        $this->assertNotEmpty($daXienBets, 'Should have da_xien bets with auto-resolved stations');
    }
}

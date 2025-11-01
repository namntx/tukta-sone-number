<?php

namespace Tests\Feature\BettingParser;

use Tests\TestCase;
use App\Services\BettingMessageParser;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DaThangDaXienTest extends TestCase
{
    use RefreshDatabase;

    private BettingMessageParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(); // Seed necessary data (BettingTypes, LotterySchedules)
        $this->parser = app(BettingMessageParser::class);
    }

    /** @test */
    public function test_da_thang_with_even_numbers()
    {
        $input = "tg 11 22 33 44 dt 10n";
        $result = $this->parser->parse($input);

        $this->assertCount(2, $result['bets'], 'Should create 2 bets for 4 numbers');

        // Check first bet
        $this->assertEquals('da_thang', $result['bets'][0]['type']);
        $this->assertEquals([11, 22], $result['bets'][0]['numbers']);
        $this->assertEquals(10000, $result['bets'][0]['amount']);
        $this->assertEquals('tien giang', $result['bets'][0]['station']);

        // Check second bet
        $this->assertEquals('da_thang', $result['bets'][1]['type']);
        $this->assertEquals([33, 44], $result['bets'][1]['numbers']);
        $this->assertEquals(10000, $result['bets'][1]['amount']);
        $this->assertEquals('tien giang', $result['bets'][1]['station']);
    }

    /** @test */
    public function test_da_thang_with_odd_numbers_drops_last()
    {
        $input = "bt 11 22 33 dt 5n";
        $result = $this->parser->parse($input);

        $this->assertCount(1, $result['bets'], 'Should create 1 bet, dropping the last number');

        $this->assertEquals('da_thang', $result['bets'][0]['type']);
        $this->assertEquals([11, 22], $result['bets'][0]['numbers']);
        $this->assertEquals(5000, $result['bets'][0]['amount']);

        // Check for warning event
        $warningEvents = array_filter($result['events'], fn($e) => $e['type'] === 'warning_da_thang_odd_numbers');
        $this->assertNotEmpty($warningEvents, 'Should have warning about odd numbers');
    }

    /** @test */
    public function test_da_thang_with_multiple_stations_errors()
    {
        $input = "tg bt 11 22 dt 10n";
        $result = $this->parser->parse($input);

        $this->assertCount(0, $result['bets'], 'Should create 0 bets due to error');

        // Check for error event
        $errorEvents = array_filter($result['events'], fn($e) => $e['type'] === 'error_da_thang_wrong_station_count');
        $this->assertNotEmpty($errorEvents, 'Should have error about station count');
    }

    /** @test */
    public function test_da_xien_2_stations_3_numbers()
    {
        $input = "tn bt 11 22 33 dx 1n";
        $result = $this->parser->parse($input);

        // C(3,2) = 3 pairs
        $this->assertCount(3, $result['bets'], 'Should create 3 bets for C(3,2)');

        // Check all pairs
        $expectedPairs = [[11, 22], [11, 33], [22, 33]];
        foreach ($result['bets'] as $idx => $bet) {
            $this->assertEquals('da_xien', $bet['type']);
            $this->assertEquals($expectedPairs[$idx], $bet['numbers']);
            $this->assertEquals(1000, $bet['amount']);
            $this->assertNull($bet['station'], 'Station should be null for multi-station');

            // Check meta
            $this->assertEquals(2, $bet['meta']['dai_count']);
            $this->assertEquals('across', $bet['meta']['station_mode']);
            $this->assertCount(1, $bet['meta']['station_pairs'], 'Should have C(2,2)=1 station pair');
            $this->assertEquals([['tay ninh', 'ben tre']], $bet['meta']['station_pairs']);
        }
    }

    /** @test */
    public function test_da_xien_3_stations_2_numbers()
    {
        $input = "tg bt ag 11 22 dx 5n";
        $result = $this->parser->parse($input);

        // C(2,2) = 1 pair
        $this->assertCount(1, $result['bets'], 'Should create 1 bet for C(2,2)');

        $bet = $result['bets'][0];
        $this->assertEquals('da_xien', $bet['type']);
        $this->assertEquals([11, 22], $bet['numbers']);
        $this->assertEquals(5000, $bet['amount']);
        $this->assertNull($bet['station']);

        // Check meta for 3 stations
        $this->assertEquals(3, $bet['meta']['dai_count']);
        $this->assertCount(3, $bet['meta']['station_pairs'], 'Should have C(3,2)=3 station pairs');

        $expectedStationPairs = [
            ['tien giang', 'ben tre'],
            ['tien giang', 'an giang'],
            ['ben tre', 'an giang']
        ];
        $this->assertEquals($expectedStationPairs, $bet['meta']['station_pairs']);
    }

    /** @test */
    public function test_da_xien_with_single_station_errors()
    {
        $input = "tg 11 22 dx 10n";
        $result = $this->parser->parse($input);

        $this->assertCount(0, $result['bets'], 'Should create 0 bets due to error');

        // Check for error event
        $errorEvents = array_filter($result['events'], fn($e) => $e['type'] === 'error_da_xien_min_stations');
        $this->assertNotEmpty($errorEvents, 'Should have error about minimum stations');
    }

    /** @test */
    public function test_da_xien_4_numbers_generates_6_pairs()
    {
        $input = "tn bt 11 22 33 44 dx 2n";
        $result = $this->parser->parse($input);

        // C(4,2) = 6 pairs
        $this->assertCount(6, $result['bets'], 'Should create 6 bets for C(4,2)');

        $expectedPairs = [
            [11, 22], [11, 33], [11, 44],
            [22, 33], [22, 44],
            [33, 44]
        ];

        foreach ($result['bets'] as $idx => $bet) {
            $this->assertEquals($expectedPairs[$idx], $bet['numbers']);
        }
    }
}

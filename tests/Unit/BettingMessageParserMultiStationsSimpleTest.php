<?php

namespace Tests\Unit;

use App\Services\BettingMessageParser;
use PHPUnit\Framework\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;

class BettingMessageParserMultiStationsSimpleTest extends TestCase
{
    use RefreshDatabase;

    protected BettingMessageParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock session
        Session::shouldReceive('get')
            ->with('global_date')
            ->andReturn('2024-01-15'); // Thứ 2
        
        Session::shouldReceive('get')
            ->with('global_region')
            ->andReturn('nam');
        
        $this->parser = new BettingMessageParser();
    }

    /**
     * Test 2d multi-stations
     */
    public function test_2d_multi_stations()
    {
        $message = "2d 80 50n";
        
        $result = $this->parser->parseMessage($message);
        
        $this->assertTrue($result['is_valid'], "2d should be valid");
        $this->assertGreaterThan(0, count($result['multiple_bets']), "Should have bets");
        
        // Group by station
        $stations = [];
        foreach ($result['multiple_bets'] as $bet) {
            $station = $bet['station'];
            if (!isset($stations[$station])) {
                $stations[$station] = 0;
            }
            $stations[$station]++;
        }
        
        // Should have 2 different stations
        $this->assertCount(2, $stations, "Should have 2 different stations");
        
        // Each station should have 1 bet
        foreach ($stations as $station => $count) {
            $this->assertEquals(1, $count, "Station $station should have 1 bet");
        }
    }

    /**
     * Test 2d with region
     */
    public function test_2d_with_region()
    {
        $message = "2d bac 80 50n";
        
        $result = $this->parser->parseMessage($message);
        
        $this->assertTrue($result['is_valid'], "2d bac should be valid");
        $this->assertGreaterThan(0, count($result['multiple_bets']), "Should have bets");
        
        // Group by station
        $stations = [];
        foreach ($result['multiple_bets'] as $bet) {
            $station = $bet['station'];
            if (!isset($stations[$station])) {
                $stations[$station] = 0;
            }
            $stations[$station]++;
        }
        
        // Should have 2 different stations
        $this->assertCount(2, $stations, "Should have 2 different stations");
    }
}

<?php

namespace Tests\Unit;

use App\Services\BettingMessageParser;
use PHPUnit\Framework\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;

class BettingMessageParserMultiStationsFixTest extends TestCase
{
    use RefreshDatabase;

    protected BettingMessageParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock session
        Session::shouldReceive('has')
            ->with('global_date')
            ->andReturn(false);
        
        Session::shouldReceive('has')
            ->with('global_region')
            ->andReturn(false);
        
        $this->parser = new BettingMessageParser();
    }

    /**
     * Test 2dai with implicit betting
     */
    public function test_2dai_with_implicit_betting()
    {
        $message = "2dai 89.98dd140n lo10n";
        
        $result = $this->parser->parseMessage($message);
        
        $this->assertTrue($result['is_valid'], "2dai with implicit betting should be valid");
        $this->assertGreaterThan(0, count($result['multiple_bets']), "Should have bets");
        
        // Group by station and type
        $grouped = [];
        foreach ($result['multiple_bets'] as $bet) {
            $key = $bet['station'] . '|' . $bet['type'];
            if (!isset($grouped[$key])) {
                $grouped[$key] = ['station' => $bet['station'], 'type' => $bet['type'], 'count' => 0, 'amount' => $bet['amount']];
            }
            $grouped[$key]['count']++;
        }
        
        // Should have bets for multiple stations
        $this->assertGreaterThan(1, count($grouped), "Should have bets for multiple stations");
        
        // Check that implicit betting (lo10n) creates bets for all selected stations
        $loBets = array_filter($grouped, fn($info) => $info['type'] === 'bao_lo' && $info['amount'] > 0);
        $this->assertGreaterThan(1, count($loBets), "Implicit betting should create bets for multiple stations");
    }

    /**
     * Test 2d with implicit betting
     */
    public function test_2d_with_implicit_betting()
    {
        $message = "2d 80 50n lo20n";
        
        $result = $this->parser->parseMessage($message);
        
        $this->assertTrue($result['is_valid'], "2d with implicit betting should be valid");
        $this->assertGreaterThan(0, count($result['multiple_bets']), "Should have bets");
        
        // Group by station and type
        $grouped = [];
        foreach ($result['multiple_bets'] as $bet) {
            $key = $bet['station'] . '|' . $bet['type'];
            if (!isset($grouped[$key])) {
                $grouped[$key] = ['station' => $bet['station'], 'type' => $bet['type'], 'count' => 0, 'amount' => $bet['amount']];
            }
            $grouped[$key]['count']++;
        }
        
        // Should have bets for multiple stations
        $this->assertGreaterThan(1, count($grouped), "Should have bets for multiple stations");
    }
}

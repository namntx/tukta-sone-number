<?php

namespace Tests\Unit;

use App\Services\BettingMessageParser;
use PHPUnit\Framework\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BettingMessageParserStationTest extends TestCase
{
    use RefreshDatabase;

    protected BettingMessageParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new BettingMessageParser();
    }

    /**
     * Test station abbreviations with dots and commas
     */
    public function test_station_abbreviations_with_dots_commas()
    {
        $testCases = [
            'L.an' => 'long an',
            'l.an' => 'long an', 
            'L,an' => 'long an',
            'l,an' => 'long an',
            'lan' => 'long an',
            'LA' => 'long an',
            'la' => 'long an'
        ];

        foreach ($testCases as $input => $expectedStation) {
            $message = "$input lo 80 50n";
            $result = $this->parser->parseMessage($message);
            
            $this->assertTrue($result['is_valid'], "Station '$input' should be valid");
            $this->assertGreaterThan(0, count($result['multiple_bets']), "Should have bets for station '$input'");
            
            $actualStation = $result['multiple_bets'][0]['station'] ?? 'unknown';
            $this->assertEquals($expectedStation, $actualStation, "Station '$input' should map to '$expectedStation'");
        }
    }

    /**
     * Test complex message with L.an station
     */
    public function test_complex_message_with_l_an_station()
    {
        $message = "TP xc 271 272 30n L.an lo 80 50n";
        
        $result = $this->parser->parseMessage($message);
        
        $this->assertTrue($result['is_valid'], "Complex message should be valid");
        $this->assertGreaterThan(0, count($result['multiple_bets']), "Should have multiple bets");
        
        // Check that we have both XC bets and LO bets
        $xcBets = array_filter($result['multiple_bets'], fn($bet) => $bet['type'] === 'xiu_chu');
        $loBets = array_filter($result['multiple_bets'], fn($bet) => $bet['type'] === 'bao_lo');
        
        $this->assertGreaterThan(0, count($xcBets), "Should have XC bets");
        $this->assertGreaterThan(0, count($loBets), "Should have LO bets");
        
        // Check station assignments
        foreach ($xcBets as $bet) {
            $this->assertEquals('tp.hcm', $bet['station'], "XC bets should be on TP station");
        }
        
        foreach ($loBets as $bet) {
            $this->assertEquals('long an', $bet['station'], "LO bets should be on Long An station");
        }
    }
}

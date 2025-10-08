<?php

namespace Tests\Unit;

use App\Services\BettingMessageParser;
use PHPUnit\Framework\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BettingMessageParserImplicitTest extends TestCase
{
    use RefreshDatabase;

    protected BettingMessageParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new BettingMessageParser();
    }

    /**
     * Test implicit betting: số + amount without betting type
     */
    public function test_implicit_betting()
    {
        $message = "l.an lo 80 50n 82 30n";
        
        $result = $this->parser->parseMessage($message);
        
        $this->assertTrue($result['is_valid'], "Implicit betting should be valid");
        $this->assertCount(2, $result['multiple_bets'], "Should have 2 bets");
        
        // Check first bet
        $bet1 = $result['multiple_bets'][0];
        $this->assertEquals('bao_lo', $bet1['type']);
        $this->assertEquals(['80'], $bet1['numbers']);
        $this->assertEquals(50000, $bet1['amount']);
        $this->assertEquals('long an', $bet1['station']);
        
        // Check second bet (implicit)
        $bet2 = $result['multiple_bets'][1];
        $this->assertEquals('bao_lo', $bet2['type']);
        $this->assertEquals(['82'], $bet2['numbers']);
        $this->assertEquals(30000, $bet2['amount']);
        $this->assertEquals('long an', $bet2['station']);
    }

    /**
     * Test implicit betting with different betting types
     */
    public function test_implicit_betting_xc()
    {
        $message = "l.an xc 271 30n 272 20n";
        
        $result = $this->parser->parseMessage($message);
        
        $this->assertTrue($result['is_valid'], "XC implicit betting should be valid");
        $this->assertCount(2, $result['multiple_bets'], "Should have 2 XC bets");
        
        // Check first bet
        $bet1 = $result['multiple_bets'][0];
        $this->assertEquals('xiu_chu', $bet1['type']);
        $this->assertEquals(['271'], $bet1['numbers']);
        $this->assertEquals(30000, $bet1['amount']);
        
        // Check second bet (implicit)
        $bet2 = $result['multiple_bets'][1];
        $this->assertEquals('xiu_chu', $bet2['type']);
        $this->assertEquals(['272'], $bet2['numbers']);
        $this->assertEquals(20000, $bet2['amount']);
    }
}

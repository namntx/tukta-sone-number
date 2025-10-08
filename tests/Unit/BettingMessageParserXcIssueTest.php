<?php

namespace Tests\Unit;

use App\Services\BettingMessageParser;
use PHPUnit\Framework\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BettingMessageParserXcIssueTest extends TestCase
{
    use RefreshDatabase;

    protected BettingMessageParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new BettingMessageParser();
    }

    /**
     * Test xỉu chủ với nhiều số
     */
    public function test_xiu_chu_multiple_numbers()
    {
        $message = "xc 271 272 274 168 252 751 773 939 979 915 616 353 323 464 322 115 476 763 30n";
        
        $result = $this->parser->parseMessage($message);
        
        $this->assertTrue($result['is_valid'], "XC parsing should be valid");
        
        // Should have multiple bets (one for each number)
        $this->assertGreaterThan(10, count($result['multiple_bets']), "Should have multiple XC bets");
        
        // Check that all numbers are included
        $numbers = [];
        foreach ($result['multiple_bets'] as $bet) {
            $this->assertEquals('xiu_chu', $bet['type']);
            $this->assertEquals(30000, $bet['amount']); // 30n = 30,000
            $numbers[] = $bet['number'];
        }
        
        // Should include all the numbers from input
        $expectedNumbers = ['271', '272', '274', '168', '252', '751', '773', '939', '979', '915', '616', '353', '323', '464', '322', '115', '476', '763'];
        
        foreach ($expectedNumbers as $expectedNumber) {
            $this->assertContains($expectedNumber, $numbers, "Should include number $expectedNumber");
        }
    }

    /**
     * Test xỉu chủ với station
     */
    public function test_xiu_chu_with_station()
    {
        $message = "TP xc 271 272 274 30n";
        
        $result = $this->parser->parseMessage($message);
        
        $this->assertTrue($result['is_valid'], "XC with station should be valid");
        $this->assertCount(3, $result['multiple_bets'], "Should have 3 XC bets");
        
        foreach ($result['multiple_bets'] as $bet) {
            $this->assertEquals('xiu_chu', $bet['type']);
            $this->assertEquals(30000, $bet['amount']);
            $this->assertEquals('tp.hcm', $bet['station']);
        }
    }
}

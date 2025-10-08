<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\BettingMessageParser;

class BettingMessageParserSessionTest extends TestCase
{
    private BettingMessageParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new BettingMessageParser();
    }

    /**
     * Test global_date and global_region from session
     */
    public function test_session_global_date_and_region()
    {
        // Test với session global_date và global_region
        session(['global_date' => '2024-01-15', 'global_region' => 'nam']); // Monday
        $message = "89.98dd140n lo10n";
        $result = $this->parser->parseMessage($message);
        
        $this->assertTrue($result['is_valid']);
        $this->assertCount(6, $result['multiple_bets']);
        
        // Monday Nam should be TP.HCM
        $this->assertEquals('tp.hcm', $result['multiple_bets'][0]['station']);
    }

    /**
     * Test different regions with session
     */
    public function test_session_different_regions()
    {
        // Test Bac region
        session(['global_date' => '2024-01-16', 'global_region' => 'bac']); // Tuesday
        $message = "89.98dd140n lo10n";
        $result = $this->parser->parseMessage($message);
        
        $this->assertTrue($result['is_valid']);
        // Tuesday Bac should be Quảng Ninh
        $this->assertEquals('quảng ninh', $result['multiple_bets'][0]['station']);
    }

    /**
     * Test Trung region with session
     */
    public function test_session_trung_region()
    {
        // Test Trung region
        session(['global_date' => '2024-01-17', 'global_region' => 'trung']); // Wednesday
        $message = "89.98dd140n lo10n";
        $result = $this->parser->parseMessage($message);
        
        $this->assertTrue($result['is_valid']);
        // Wednesday Trung should be Khánh Hòa
        $this->assertEquals('khánh hòa', $result['multiple_bets'][0]['station']);
    }

    /**
     * Test fallback when no session
     */
    public function test_fallback_without_session()
    {
        // Clear session
        session()->forget(['global_date', 'global_region']);
        
        $message = "89.98dd140n lo10n";
        $result = $this->parser->parseMessage($message);
        
        $this->assertTrue($result['is_valid']);
        // Should use current date and default region (nam)
        $this->assertNotEmpty($result['multiple_bets'][0]['station']);
    }
}

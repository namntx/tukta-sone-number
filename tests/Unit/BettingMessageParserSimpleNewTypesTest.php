<?php

namespace Tests\Unit;

use App\Services\BettingMessageParser;
use PHPUnit\Framework\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BettingMessageParserSimpleNewTypesTest extends TestCase
{
    use RefreshDatabase;

    protected BettingMessageParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new BettingMessageParser();
    }

    /**
     * Test bảy lô, tám lô (đã hoạt động)
     */
    public function test_bay_lo_tam_lo()
    {
        // Bảy lô
        $result = $this->parser->parseMessage("BT 042 baylo 10");
        $this->assertTrue($result['is_valid']);
        $this->assertGreaterThan(0, count($result['multiple_bets']));
        
        // Tám lô
        $result = $this->parser->parseMessage("MB 448 tamlo 15");
        $this->assertTrue($result['is_valid']);
        $this->assertGreaterThan(0, count($result['multiple_bets']));
    }

    /**
     * Test xỉu chủ đảo (đã hoạt động)
     */
    public function test_xiu_chu_dao()
    {
        $result = $this->parser->parseMessage("BT 042 xcddau 10");
        $this->assertTrue($result['is_valid']);
        $this->assertGreaterThan(0, count($result['multiple_bets']));
    }

    /**
     * Test các loại cược đơn giản khác
     */
    public function test_simple_betting_types()
    {
        // Test với station để tránh session issue
        $testCases = [
            "TP baylo 10",
            "TP tamlo 15", 
            "TP xcddau 10",
            "TP xien2 11 22 x100n",
            "TP dan05cokep x100n",
            "TP giapty x100n",
            "TP tongto x100n",
            "TP kepbang x100n"
        ];

        foreach ($testCases as $message) {
            $result = $this->parser->parseMessage($message);
            $this->assertTrue($result['is_valid'], "Failed for: $message");
            $this->assertGreaterThan(0, count($result['multiple_bets']), "No bets found for: $message");
        }
    }
}

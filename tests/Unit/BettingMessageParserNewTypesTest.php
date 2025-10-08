<?php

namespace Tests\Unit;

use App\Services\BettingMessageParser;
use PHPUnit\Framework\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BettingMessageParserNewTypesTest extends TestCase
{
    use RefreshDatabase;

    protected BettingMessageParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new BettingMessageParser();
    }

    /**
     * Test bảy lô, tám lô
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
     * Test xỉu chủ đảo
     */
    public function test_xiu_chu_dao()
    {
        $result = $this->parser->parseMessage("BT 042 xcddau 10");
        $this->assertTrue($result['is_valid']);
        $this->assertGreaterThan(0, count($result['multiple_bets']));
    }

    /**
     * Test đề đặc biệt
     */
    public function test_de_dac_biet()
    {
        $result = $this->parser->parseMessage("de dau dac biet 00 x100n");
        $this->assertTrue($result['is_valid']);
        $this->assertGreaterThan(0, count($result['multiple_bets']));
    }

    /**
     * Test xiên
     */
    public function test_xien()
    {
        $result = $this->parser->parseMessage("xien2 11 22 x100n");
        $this->assertTrue($result['is_valid']);
        $this->assertGreaterThan(0, count($result['multiple_bets']));
    }

    /**
     * Test dàn số
     */
    public function test_dan_so()
    {
        $result = $this->parser->parseMessage("de dan 05 co kep x100n");
        $this->assertTrue($result['is_valid']);
        $this->assertGreaterThan(0, count($result['multiple_bets']));
    }

    /**
     * Test giáp
     */
    public function test_giap()
    {
        $result = $this->parser->parseMessage("de giap ty x100n");
        $this->assertTrue($result['is_valid']);
        $this->assertGreaterThan(0, count($result['multiple_bets']));
    }

    /**
     * Test tổng
     */
    public function test_tong()
    {
        $result = $this->parser->parseMessage("de tongto x100n");
        $this->assertTrue($result['is_valid']);
        $this->assertGreaterThan(0, count($result['multiple_bets']));
    }

    /**
     * Test kép
     */
    public function test_kep()
    {
        $result = $this->parser->parseMessage("de kepbang x100n");
        $this->assertTrue($result['is_valid']);
        $this->assertGreaterThan(0, count($result['multiple_bets']));
    }
}

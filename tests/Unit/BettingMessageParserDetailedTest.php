<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\BettingMessageParser;

class BettingMessageParserDetailedTest extends TestCase
{
    private BettingMessageParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new BettingMessageParser();
    }

    public function test_da_xeo_51_90_5k_3d()
    {
        $result = $this->parser->parseMessage('Da xeo 51.90.5k.3d');
        
        $this->assertTrue($result['is_valid']);
        $this->assertCount(1, $result['multiple_bets']);
        
        $bet = $result['multiple_bets'][0];
        $this->assertNull($bet['station']);
        $this->assertEquals('da_xien', $bet['type']);
        $this->assertEquals(['51', '90'], $bet['numbers']);
        $this->assertEquals(5000, $bet['amount']);
        $this->assertEquals(['xien_size' => 2, 'multi_stations' => 3, 'region' => null], $bet['meta']);
    }

    public function test_n_input()
    {
        $result = $this->parser->parseMessage('N');
        
        $this->assertTrue($result['is_valid']);
        $this->assertCount(0, $result['multiple_bets']);
    }

    public function test_tn_complex_input()
    {
        $result = $this->parser->parseMessage('TN21.22.84. lô 10 .21 22.84 đđ20. 121xc25n. 084xc40n');
        
        $this->assertTrue($result['is_valid']);
        $this->assertCount(5, $result['multiple_bets']);
        
        // Test first bet: lô 10k
        $bet1 = $result['multiple_bets'][0];
        $this->assertEquals('tây ninh', $bet1['station']);
        $this->assertEquals('bao_lo', $bet1['type']);
        $this->assertEquals(['21', '22', '84'], $bet1['numbers']);
        $this->assertEquals(10000, $bet1['amount']);
        
        // Test second bet: đđ20 (đầu đuôi)
        $bet2 = $result['multiple_bets'][1];
        $this->assertEquals('tây ninh', $bet2['station']);
        $this->assertEquals('dau', $bet2['type']);
        $this->assertEquals(['21', '22', '84'], $bet2['numbers']);
        $this->assertEquals(20000, $bet2['amount']);
        
        // Test third bet: đđ20 (đầu đuôi)
        $bet3 = $result['multiple_bets'][2];
        $this->assertEquals('tây ninh', $bet3['station']);
        $this->assertEquals('duoi', $bet3['type']);
        $this->assertEquals(['21', '22', '84'], $bet3['numbers']);
        $this->assertEquals(20000, $bet3['amount']);
        
        // Test fourth bet: xỉu chủ 121
        $bet4 = $result['multiple_bets'][3];
        $this->assertEquals('tây ninh', $bet4['station']);
        $this->assertEquals('xiu_chu', $bet4['type']);
        $this->assertEquals(['121'], $bet4['numbers']);
        $this->assertEquals(25000, $bet4['amount']);
        
        // Test fifth bet: xỉu chủ 084
        $bet5 = $result['multiple_bets'][4];
        $this->assertEquals('tây ninh', $bet5['station']);
        $this->assertEquals('xiu_chu', $bet5['type']);
        $this->assertEquals(['084'], $bet5['numbers']);
        $this->assertEquals(40000, $bet5['amount']);
    }

    public function test_ag_lo_input()
    {
        $result = $this->parser->parseMessage('AG2298.0898.0998.1598lo2,5n');
        
        $this->assertTrue($result['is_valid']);
        $this->assertCount(1, $result['multiple_bets']);
        
        $bet = $result['multiple_bets'][0];
        $this->assertEquals('an giang', $bet['station']);
        $this->assertEquals('bao4_lo', $bet['type']);
        $this->assertEquals(['2298', '0898', '0998', '1598'], $bet['numbers']);
        $this->assertEquals(2500, $bet['amount']);
    }

    public function test_complex_tn_ag_input()
    {
        $result = $this->parser->parseMessage('T,ninh 11 đâu 20n 03 kéo 93 dd 20n. 28 đâu 30n 51 đâu 15n. A,Giang lo 98 30n');
        
        $this->assertTrue($result['is_valid']);
        $this->assertCount(6, $result['multiple_bets']);
        
        // Test first bet: 11 đâu 20n
        $bet1 = $result['multiple_bets'][0];
        $this->assertEquals('tây ninh', $bet1['station']);
        $this->assertEquals('dau', $bet1['type']);
        $this->assertEquals(['11'], $bet1['numbers']);
        $this->assertEquals(20000, $bet1['amount']);
        
        // Test second bet: 03 kéo 93 dd 20n (đầu)
        $bet2 = $result['multiple_bets'][1];
        $this->assertEquals('tây ninh', $bet2['station']);
        $this->assertEquals('dau', $bet2['type']);
        $this->assertEquals(['03', '93'], $bet2['numbers']);
        $this->assertEquals(20000, $bet2['amount']);
        
        // Test third bet: 03 kéo 93 dd 20n (đuôi)
        $bet3 = $result['multiple_bets'][2];
        $this->assertEquals('tây ninh', $bet3['station']);
        $this->assertEquals('duoi', $bet3['type']);
        $this->assertEquals(['03', '93'], $bet3['numbers']);
        $this->assertEquals(20000, $bet3['amount']);
        
        // Test fourth bet: 28 đâu 30n
        $bet4 = $result['multiple_bets'][3];
        $this->assertEquals('tây ninh', $bet4['station']);
        $this->assertEquals('dau', $bet4['type']);
        $this->assertEquals(['28'], $bet4['numbers']);
        $this->assertEquals(30000, $bet4['amount']);
        
        // Test fifth bet: 51 đâu 15n
        $bet5 = $result['multiple_bets'][4];
        $this->assertEquals('tây ninh', $bet5['station']);
        $this->assertEquals('dau', $bet5['type']);
        $this->assertEquals(['51'], $bet5['numbers']);
        $this->assertEquals(15000, $bet5['amount']);
        
        // Test sixth bet: A,Giang lo 98 30n
        $bet6 = $result['multiple_bets'][5];
        $this->assertEquals('an giang', $bet6['station']);
        $this->assertEquals('bao_lo', $bet6['type']);
        $this->assertEquals(['98'], $bet6['numbers']);
        $this->assertEquals(30000, $bet6['amount']);
    }

    public function test_90_d130k_d0k_51d0k_d130k()
    {
        $result = $this->parser->parseMessage('90.d130k.d0k.51d0k.d130k.');
        
        $this->assertTrue($result['is_valid']);
        $this->assertCount(4, $result['multiple_bets']);
        
        // Test first bet: 90 đầu 130k
        $bet1 = $result['multiple_bets'][0];
        $this->assertNull($bet1['station']);
        $this->assertEquals('dau', $bet1['type']);
        $this->assertEquals(['90'], $bet1['numbers']);
        $this->assertEquals(130000, $bet1['amount']);
        
        // Test second bet: 90 đuôi 0k
        $bet2 = $result['multiple_bets'][1];
        $this->assertNull($bet2['station']);
        $this->assertEquals('duoi', $bet2['type']);
        $this->assertEquals(['90'], $bet2['numbers']);
        $this->assertEquals(0, $bet2['amount']);
        
        // Test third bet: 51 đầu 0k
        $bet3 = $result['multiple_bets'][2];
        $this->assertNull($bet3['station']);
        $this->assertEquals('dau', $bet3['type']);
        $this->assertEquals(['51'], $bet3['numbers']);
        $this->assertEquals(0, $bet3['amount']);
        
        // Test fourth bet: 51 đuôi 130k
        $bet4 = $result['multiple_bets'][3];
        $this->assertNull($bet4['station']);
        $this->assertEquals('duoi', $bet4['type']);
        $this->assertEquals(['51'], $bet4['numbers']);
        $this->assertEquals(130000, $bet4['amount']);
    }

    public function test_ag319xc60n()
    {
        $result = $this->parser->parseMessage('ag319xc60n');
        
        $this->assertTrue($result['is_valid']);
        $this->assertCount(1, $result['multiple_bets']);
        
        $bet = $result['multiple_bets'][0];
        $this->assertEquals('an giang', $bet['station']);
        $this->assertEquals('xiu_chu', $bet['type']);
        $this->assertEquals(['319'], $bet['numbers']);
        $this->assertEquals(60000, $bet['amount']);
    }

    public function test_15_55_95_d30n_d10n_lo5n_xc_515_20n()
    {
        $result = $this->parser->parseMessage('15.55.95.d30n d10n lo5n.xc 515.20n');
        
        $this->assertTrue($result['is_valid']);
        $this->assertCount(4, $result['multiple_bets']);
        
        // Test first bet: 15,55,95 đầu 30k
        $bet1 = $result['multiple_bets'][0];
        $this->assertNull($bet1['station']);
        $this->assertEquals('dau', $bet1['type']);
        $this->assertEquals(['15', '55', '95'], $bet1['numbers']);
        $this->assertEquals(30000, $bet1['amount']);
        
        // Test second bet: 15,55,95 đuôi 10k
        $bet2 = $result['multiple_bets'][1];
        $this->assertNull($bet2['station']);
        $this->assertEquals('duoi', $bet2['type']);
        $this->assertEquals(['15', '55', '95'], $bet2['numbers']);
        $this->assertEquals(10000, $bet2['amount']);
        
        // Test third bet: 15,55,95 lô 5k
        $bet3 = $result['multiple_bets'][2];
        $this->assertNull($bet3['station']);
        $this->assertEquals('bao_lo', $bet3['type']);
        $this->assertEquals(['15', '55', '95'], $bet3['numbers']);
        $this->assertEquals(5000, $bet3['amount']);
        
        // Test fourth bet: 515 xỉu chủ 20k
        $bet4 = $result['multiple_bets'][3];
        $this->assertNull($bet4['station']);
        $this->assertEquals('xiu_chu', $bet4['type']);
        $this->assertEquals(['515'], $bet4['numbers']);
        $this->assertEquals(20000, $bet4['amount']);
    }
}

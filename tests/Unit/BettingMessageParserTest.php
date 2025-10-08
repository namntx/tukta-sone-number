<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\BettingMessageParser;

class BettingMessageParserTest extends TestCase
{
    private BettingMessageParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new BettingMessageParser();
    }

    public function test_simple_ag_lo()
    {
        $input = 'AG 98 lo70n';
        $result = $this->parser->parseMessage($input);
        
        $this->assertTrue($result['is_valid'], "Should be valid");
        $this->assertCount(1, $result['multiple_bets'], "Should have 1 bet");
        
        $bet = $result['multiple_bets'][0];
        $this->assertEquals('an giang', $bet['station']);
        $this->assertEquals('bao_lo', $bet['type']);
        $this->assertEquals(['98'], $bet['numbers']);
        $this->assertEquals(70000, $bet['amount']);
    }

    public function test_simple_ag_xiu_chu()
    {
        $input = 'ag319xc60n';
        $result = $this->parser->parseMessage($input);
        
        $this->assertTrue($result['is_valid'], "Should be valid");
        $this->assertCount(1, $result['multiple_bets'], "Should have 1 bet");
        
        $bet = $result['multiple_bets'][0];
        $this->assertEquals('an giang', $bet['station']);
        $this->assertEquals('xiu_chu', $bet['type']);
        $this->assertEquals(['319'], $bet['numbers']);
        $this->assertEquals(60000, $bet['amount']);
    }

    public function test_simple_dau_duoi_mixed()
    {
        $input = '90.d130k.d0k.51d0k.d130k.';
        $result = $this->parser->parseMessage($input);
        
        $this->assertTrue($result['is_valid'], "Should be valid");
        $this->assertCount(4, $result['multiple_bets'], "Should have 4 bets");
        
        // Kiểm tra bet đầu 90
        $dau90 = null;
        foreach ($result['multiple_bets'] as $bet) {
            if ($bet['type'] === 'dau' && in_array('90', $bet['numbers']) && $bet['amount'] === 130000) {
                $dau90 = $bet;
                break;
            }
        }
        $this->assertNotNull($dau90, "Should have dau 90 with amount 130000");
        
        // Kiểm tra bet đuôi 90
        $duoi90 = null;
        foreach ($result['multiple_bets'] as $bet) {
            if ($bet['type'] === 'duoi' && in_array('90', $bet['numbers']) && $bet['amount'] === 0) {
                $duoi90 = $bet;
                break;
            }
        }
        $this->assertNotNull($duoi90, "Should have duoi 90 with amount 0");
    }

    public function test_simple_3_so_mixed()
    {
        $input = '15.55.95.d30n d10n lo5n.xc 515.20n';
        $result = $this->parser->parseMessage($input);
        
        $this->assertTrue($result['is_valid'], "Should be valid");
        $this->assertGreaterThan(5, count($result['multiple_bets']), "Should have more than 5 bets");
        
        // Kiểm tra có bet đầu với amount 30000
        $hasDau30k = false;
        foreach ($result['multiple_bets'] as $bet) {
            if ($bet['type'] === 'dau' && $bet['amount'] === 30000) {
                $hasDau30k = true;
                break;
            }
        }
        $this->assertTrue($hasDau30k, "Should have dau bets with 30000 amount");
        
        // Kiểm tra có bet đuôi với amount 10000
        $hasDuoi10k = false;
        foreach ($result['multiple_bets'] as $bet) {
            if ($bet['type'] === 'duoi' && $bet['amount'] === 10000) {
                $hasDuoi10k = true;
                break;
            }
        }
        $this->assertTrue($hasDuoi10k, "Should have duoi bets with 10000 amount");
        
        // Kiểm tra có bet lô với amount 5000
        $hasLo5k = false;
        foreach ($result['multiple_bets'] as $bet) {
            if ($bet['type'] === 'bao_lo' && $bet['amount'] === 5000) {
                $hasLo5k = true;
                break;
            }
        }
        $this->assertTrue($hasLo5k, "Should have lo bets with 5000 amount");
        
        // Kiểm tra có bet xỉu chủ với amount 20000
        $hasXc20k = false;
        foreach ($result['multiple_bets'] as $bet) {
            if ($bet['type'] === 'xiu_chu' && $bet['amount'] === 20000) {
                $hasXc20k = true;
                break;
            }
        }
        $this->assertTrue($hasXc20k, "Should have xiu_chu bets with 20000 amount");
    }

    public function test_tn_lo_dau_duoi()
    {
        $input = 'TN21.22.84. lô 10 .21 22.84 đđ20. 121xc25n. 084xc40n';
        $result = $this->parser->parseMessage($input);
        
        $this->assertTrue($result['is_valid'], "Should be valid");
        $this->assertGreaterThan(5, count($result['multiple_bets']), "Should have more than 5 bets");
        
        // Kiểm tra có bet lô với amount 10000
        $hasLo10k = false;
        foreach ($result['multiple_bets'] as $bet) {
            if ($bet['type'] === 'bao_lo' && $bet['amount'] === 10000) {
                $hasLo10k = true;
                break;
            }
        }
        $this->assertTrue($hasLo10k, "Should have lo bets with 10000 amount");
        
        // Kiểm tra có bet đầu với amount 20000
        $hasDau20k = false;
        foreach ($result['multiple_bets'] as $bet) {
            if ($bet['type'] === 'dau' && $bet['amount'] === 20000) {
                $hasDau20k = true;
                break;
            }
        }
        $this->assertTrue($hasDau20k, "Should have dau bets with 20000 amount");
        
        // Kiểm tra có bet đuôi với amount 20000
        $hasDuoi20k = false;
        foreach ($result['multiple_bets'] as $bet) {
            if ($bet['type'] === 'duoi' && $bet['amount'] === 20000) {
                $hasDuoi20k = true;
                break;
            }
        }
        $this->assertTrue($hasDuoi20k, "Should have duoi bets with 20000 amount");
        
        // Kiểm tra có bet xỉu chủ với amount 25000
        $hasXc25k = false;
        foreach ($result['multiple_bets'] as $bet) {
            if ($bet['type'] === 'xiu_chu' && $bet['amount'] === 25000) {
                $hasXc25k = true;
                break;
            }
        }
        $this->assertTrue($hasXc25k, "Should have xiu_chu bets with 25000 amount");
        
        // Kiểm tra có bet xỉu chủ với amount 40000
        $hasXc40k = false;
        foreach ($result['multiple_bets'] as $bet) {
            if ($bet['type'] === 'xiu_chu' && $bet['amount'] === 40000) {
                $hasXc40k = true;
                break;
            }
        }
        $this->assertTrue($hasXc40k, "Should have xiu_chu bets with 40000 amount");
    }

    public function test_ag_4_so_lo()
    {
        $input = 'AG2298.0898.0998.1598lo2,5n';
        $result = $this->parser->parseMessage($input);
        
        $this->assertTrue($result['is_valid'], "Should be valid");
        $this->assertGreaterThan(0, count($result['multiple_bets']), "Should have at least 1 bet");
        
        // Kiểm tra có bet 4 số với amount 2500
        $has4So2500 = false;
        foreach ($result['multiple_bets'] as $bet) {
            if ($bet['type'] === 'bao4_lo' && $bet['amount'] === 2500) {
                $has4So2500 = true;
                break;
            }
        }
        $this->assertTrue($has4So2500, "Should have bao4_lo bets with 2500 amount");
    }

    public function test_complex_dau_duoi_sequence()
    {
        $input = '32.72.d100n d15n..33.31.53.28.d100n d10n..55.66.42.86.46.d30n d10n..10.14.68.82.18.58.62.65.d10n d50n..26.06.d10n d50n';
        $result = $this->parser->parseMessage($input);
        
        $this->assertTrue($result['is_valid'], "Should be valid");
        $this->assertGreaterThan(20, count($result['multiple_bets']), "Should have more than 20 bets");
        
        // Kiểm tra có bet đầu với amount 100000
        $hasDau100k = false;
        foreach ($result['multiple_bets'] as $bet) {
            if ($bet['type'] === 'dau' && $bet['amount'] === 100000) {
                $hasDau100k = true;
                break;
            }
        }
        $this->assertTrue($hasDau100k, "Should have dau bets with 100000 amount");
        
        // Kiểm tra có bet đuôi với amount 15000
        $hasDuoi15k = false;
        foreach ($result['multiple_bets'] as $bet) {
            if ($bet['type'] === 'duoi' && $bet['amount'] === 15000) {
                $hasDuoi15k = true;
                break;
            }
        }
        $this->assertTrue($hasDuoi15k, "Should have duoi bets with 15000 amount");
        
        // Kiểm tra có bet đuôi với amount 50000
        $hasDuoi50k = false;
        foreach ($result['multiple_bets'] as $bet) {
            if ($bet['type'] === 'duoi' && $bet['amount'] === 50000) {
                $hasDuoi50k = true;
                break;
            }
        }
        $this->assertTrue($hasDuoi50k, "Should have duoi bets with 50000 amount");
    }

    public function test_tn_xiu_chu_multiple()
    {
        $input = 'Tn 359.539.411.952xc13n.359xc60n.532xc75n.319xc60n..439.432.332xc20n.';
        $result = $this->parser->parseMessage($input);
        
        $this->assertTrue($result['is_valid'], "Should be valid");
        $this->assertGreaterThan(5, count($result['multiple_bets']), "Should have more than 5 bets");
        
        // Kiểm tra có bet xỉu chủ với amount 13000
        $hasXc13k = false;
        foreach ($result['multiple_bets'] as $bet) {
            if ($bet['type'] === 'xiu_chu' && $bet['amount'] === 13000) {
                $hasXc13k = true;
                break;
            }
        }
        $this->assertTrue($hasXc13k, "Should have xiu_chu bets with 13000 amount");
        
        // Kiểm tra có bet xỉu chủ với amount 60000
        $hasXc60k = false;
        foreach ($result['multiple_bets'] as $bet) {
            if ($bet['type'] === 'xiu_chu' && $bet['amount'] === 60000) {
                $hasXc60k = true;
                break;
            }
        }
        $this->assertTrue($hasXc60k, "Should have xiu_chu bets with 60000 amount");
        
        // Kiểm tra có bet xỉu chủ với amount 75000
        $hasXc75k = false;
        foreach ($result['multiple_bets'] as $bet) {
            if ($bet['type'] === 'xiu_chu' && $bet['amount'] === 75000) {
                $hasXc75k = true;
                break;
            }
        }
        $this->assertTrue($hasXc75k, "Should have xiu_chu bets with 75000 amount");
        
        // Kiểm tra có bet xỉu chủ với amount 20000
        $hasXc20k = false;
        foreach ($result['multiple_bets'] as $bet) {
            if ($bet['type'] === 'xiu_chu' && $bet['amount'] === 20000) {
                $hasXc20k = true;
                break;
            }
        }
        $this->assertTrue($hasXc20k, "Should have xiu_chu bets with 20000 amount");
    }

    public function test_2dai_89_98()
    {
        $input = '2dai 89.98dd140n lo10n';
        $result = $this->parser->parseMessage($input);
        
        $this->assertTrue($result['is_valid'], "Should be valid");
        $this->assertCount(4, $result['multiple_bets'], "Should have 4 bets (2 multi-stations + 2 lo bets)");
        
        // Kiểm tra có bet lô với amount 10000 cho số 89
        $hasLo89 = false;
        foreach ($result['multiple_bets'] as $bet) {
            if ($bet['type'] === 'bao_lo' && in_array('89', $bet['numbers']) && $bet['amount'] === 10000) {
                $hasLo89 = true;
                break;
            }
        }
        $this->assertTrue($hasLo89, "Should have lo bet for 89 with 10000 amount");
        
        // Kiểm tra có bet lô với amount 10000 cho số 98
        $hasLo98 = false;
        foreach ($result['multiple_bets'] as $bet) {
            if ($bet['type'] === 'bao_lo' && in_array('98', $bet['numbers']) && $bet['amount'] === 10000) {
                $hasLo98 = true;
                break;
            }
        }
        $this->assertTrue($hasLo98, "Should have lo bet for 98 with 10000 amount");
    }

    public function test_tn_mixed_bets()
    {
        $input = 'T,ninh 11 đâu 20n 03 kéo 93 dd20n. 28 đâu 30n 51 đâu 15n. A,Giang lo 98 30n';
        $result = $this->parser->parseMessage($input);
        
        $this->assertTrue($result['is_valid'], "Should be valid");
        $this->assertGreaterThan(3, count($result['multiple_bets']), "Should have more than 3 bets");
        
        // Kiểm tra có bet đầu với amount 20000
        $hasDau20k = false;
        foreach ($result['multiple_bets'] as $bet) {
            if ($bet['type'] === 'dau' && $bet['amount'] === 20000) {
                $hasDau20k = true;
                break;
            }
        }
        $this->assertTrue($hasDau20k, "Should have dau bets with 20000 amount");
        
        // Kiểm tra có bet đầu với amount 30000
        $hasDau30k = false;
        foreach ($result['multiple_bets'] as $bet) {
            if ($bet['type'] === 'dau' && $bet['amount'] === 30000) {
                $hasDau30k = true;
                break;
            }
        }
        $this->assertTrue($hasDau30k, "Should have dau bets with 30000 amount");
        
        // Kiểm tra có bet đầu với amount 15000
        $hasDau15k = false;
        foreach ($result['multiple_bets'] as $bet) {
            if ($bet['type'] === 'dau' && $bet['amount'] === 15000) {
                $hasDau15k = true;
                break;
            }
        }
        $this->assertTrue($hasDau15k, "Should have dau bets with 15000 amount");
        
        // Kiểm tra có bet lô với amount 30000
        $hasLo30k = false;
        foreach ($result['multiple_bets'] as $bet) {
            if ($bet['type'] === 'bao_lo' && $bet['amount'] === 30000) {
                $hasLo30k = true;
                break;
            }
        }
        $this->assertTrue($hasLo30k, "Should have lo bets with 30000 amount");
    }

    public function test_tn_complex_final()
    {
        $input = 'T,ninh 54 d100n d30n 48 d80n d70n 75 17 70 19 59 dd10n 20 d50n d25n 40 d90n d10n .25 lo20n';
        $result = $this->parser->parseMessage($input);
        
        $this->assertTrue($result['is_valid'], "Should be valid");
        $this->assertGreaterThan(10, count($result['multiple_bets']), "Should have more than 10 bets");
        
        // Kiểm tra có bet đầu với amount 100000
        $hasDau100k = false;
        foreach ($result['multiple_bets'] as $bet) {
            if ($bet['type'] === 'dau' && $bet['amount'] === 100000) {
                $hasDau100k = true;
                break;
            }
        }
        $this->assertTrue($hasDau100k, "Should have dau bets with 100000 amount");
        
        // Kiểm tra có bet đuôi với amount 30000
        $hasDuoi30k = false;
        foreach ($result['multiple_bets'] as $bet) {
            if ($bet['type'] === 'duoi' && $bet['amount'] === 30000) {
                $hasDuoi30k = true;
                break;
            }
        }
        $this->assertTrue($hasDuoi30k, "Should have duoi bets with 30000 amount");
        
        // Kiểm tra có bet đầu với amount 80000
        $hasDau80k = false;
        foreach ($result['multiple_bets'] as $bet) {
            if ($bet['type'] === 'dau' && $bet['amount'] === 80000) {
                $hasDau80k = true;
                break;
            }
        }
        $this->assertTrue($hasDau80k, "Should have dau bets with 80000 amount");
        
        // Kiểm tra có bet đuôi với amount 70000
        $hasDuoi70k = false;
        foreach ($result['multiple_bets'] as $bet) {
            if ($bet['type'] === 'duoi' && $bet['amount'] === 70000) {
                $hasDuoi70k = true;
                break;
            }
        }
        $this->assertTrue($hasDuoi70k, "Should have duoi bets with 70000 amount");
        
        // Kiểm tra có bet đầu/đuôi với amount 10000
        $hasDd10k = false;
        foreach ($result['multiple_bets'] as $bet) {
            if (($bet['type'] === 'dau' || $bet['type'] === 'duoi') && $bet['amount'] === 10000) {
                $hasDd10k = true;
                break;
            }
        }
        $this->assertTrue($hasDd10k, "Should have dau/duoi bets with 10000 amount");
        
        // Kiểm tra có bet đầu với amount 50000
        $hasDau50k = false;
        foreach ($result['multiple_bets'] as $bet) {
            if ($bet['type'] === 'dau' && $bet['amount'] === 50000) {
                $hasDau50k = true;
                break;
            }
        }
        $this->assertTrue($hasDau50k, "Should have dau bets with 50000 amount");
        
        // Kiểm tra có bet đuôi với amount 25000
        $hasDuoi25k = false;
        foreach ($result['multiple_bets'] as $bet) {
            if ($bet['type'] === 'duoi' && $bet['amount'] === 25000) {
                $hasDuoi25k = true;
                break;
            }
        }
        $this->assertTrue($hasDuoi25k, "Should have duoi bets with 25000 amount");
        
        // Kiểm tra có bet đầu với amount 90000
        $hasDau90k = false;
        foreach ($result['multiple_bets'] as $bet) {
            if ($bet['type'] === 'dau' && $bet['amount'] === 90000) {
                $hasDau90k = true;
                break;
            }
        }
        $this->assertTrue($hasDau90k, "Should have dau bets with 90000 amount");
        
        // Kiểm tra có bet đuôi với amount 10000
        $hasDuoi10k = false;
        foreach ($result['multiple_bets'] as $bet) {
            if ($bet['type'] === 'duoi' && $bet['amount'] === 10000) {
                $hasDuoi10k = true;
                break;
            }
        }
        $this->assertTrue($hasDuoi10k, "Should have duoi bets with 10000 amount");
        
        // Kiểm tra có bet lô với amount 20000
        $hasLo20k = false;
        foreach ($result['multiple_bets'] as $bet) {
            if ($bet['type'] === 'bao_lo' && $bet['amount'] === 20000) {
                $hasLo20k = true;
                break;
            }
        }
        $this->assertTrue($hasLo20k, "Should have lo bets with 20000 amount");
    }
}
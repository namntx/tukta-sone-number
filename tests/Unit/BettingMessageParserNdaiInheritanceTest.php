<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\BettingMessageParser;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BettingMessageParserNdaiInheritanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\BettingTypesSeeder::class);
    }

    /**
     * Test: Ndai directive clears last_numbers to prevent inheritance
     * Input: "2dai 129, 169, 269, 069 xc 3.5n 2dai đax ( 52,68)- 1.4n"
     *
     * Expected:
     * - Xỉu chủ: 4 numbers × 2 types (dau+duoi) = 8 bets with [129, 169, 269, 069]
     * - Đá xiên: 1 bet with ONLY [52, 68], NOT inheriting [129, 169, 269, 069]
     *
     * Bug was: da_xien was inheriting numbers from xiu_chu after amount flush
     * Fix: Ndai directive now clears last_numbers to prevent inheritance
     */
    public function test_ndai_directive_clears_last_numbers_prevents_inheritance()
    {
        $parser = app(BettingMessageParser::class);

        $input = '2dai 129, 169, 269, 069 xc 3.5n 2dai đax ( 52,68)- 1.4n';

        $result = $parser->parse($input, [
            'date' => '2025-11-03',
            'region' => 'nam'
        ]);

        // Should be valid
        $this->assertTrue($result['is_valid'], 'Should be valid');

        // Filter xiu_chu bets
        $xcBets = array_values(array_filter(
            $result['multiple_bets'],
            fn($b) => in_array($b['type'], ['Xỉu chủ đầu', 'Xỉu chủ đuôi'])
        ));

        // Filter da_xien bets
        $dxBets = array_values(array_filter(
            $result['multiple_bets'],
            fn($b) => $b['type'] === 'Đá xiên'
        ));

        // Should have 8 xiu_chu bets (4 numbers × 2 types)
        $this->assertCount(8, $xcBets, 'Should have 8 xiu_chu bets');

        // Check xiu_chu numbers
        $xcNumbers = array_unique(array_merge(...array_map(fn($b) => $b['numbers'], $xcBets)));
        sort($xcNumbers);
        $this->assertEquals(['069', '129', '169', '269'], $xcNumbers);

        // Check xiu_chu amounts
        foreach ($xcBets as $bet) {
            $this->assertEquals(3500, $bet['amount'], 'XC amount should be 3500 (3.5n)');
        }

        // Should have 1 da_xien bet (C(2,2) = 1 combination)
        $this->assertCount(1, $dxBets, 'Should have 1 da_xien bet');

        // CRITICAL: da_xien should ONLY have [52, 68], NOT inherit from xiu_chu
        $dxNumbers = $dxBets[0]['numbers'];
        sort($dxNumbers);
        $this->assertEquals(['52', '68'], $dxNumbers, 'Da_xien should only have [52, 68], not inherit from xiu_chu');

        // Check da_xien amount
        $this->assertEquals(1400, $dxBets[0]['amount'], 'DX amount should be 1400 (1.4n)');

        // Ensure NO overlap between xc and dx numbers
        foreach ($dxNumbers as $num) {
            $this->assertNotContains($num, $xcNumbers, "DX number {$num} should not be in XC numbers");
        }
    }

    /**
     * Test: Da_xien with Ndai only (no xiu_chu before)
     * Input: "2dai đax (52,68) - 1.4n"
     *
     * Expected: 1 da_xien bet with [52, 68]
     * This should work correctly (user confirmed)
     */
    public function test_ndai_dax_standalone_works_correctly()
    {
        $parser = app(BettingMessageParser::class);

        $input = '2dai đax (52,68) - 1.4n';

        $result = $parser->parse($input, [
            'date' => '2025-11-03',
            'region' => 'nam'
        ]);

        $this->assertTrue($result['is_valid'], 'Should be valid');

        // Filter da_xien bets
        $dxBets = array_values(array_filter(
            $result['multiple_bets'],
            fn($b) => $b['type'] === 'Đá xiên'
        ));

        // Should have exactly 1 da_xien bet
        $this->assertCount(1, $dxBets, 'Should have 1 da_xien bet');

        // Check numbers
        $dxNumbers = $dxBets[0]['numbers'];
        sort($dxNumbers);
        $this->assertEquals(['52', '68'], $dxNumbers);

        // Check amount
        $this->assertEquals(1400, $dxBets[0]['amount']);
    }

    /**
     * Test: Multiple Ndai directives in sequence
     * Input: "2dai 11,22 xc 2n 3dai 33,44,55 lo 3n"
     *
     * Expected:
     * - First group: 4 xiu_chu bets (11,22 × dau+duoi)
     * - Second group: 3 bao_lo bets (33,44,55), NOT inheriting [11,22]
     */
    public function test_multiple_ndai_directives_clear_last_numbers()
    {
        $parser = app(BettingMessageParser::class);

        $input = '2dai 11,22 xc 2n 3dai 33,44,55 lo 3n';

        $result = $parser->parse($input, [
            'date' => '2025-11-03',
            'region' => 'nam'
        ]);

        $this->assertTrue($result['is_valid'], 'Should be valid');

        // Filter by type
        $xcBets = array_filter($result['multiple_bets'], fn($b) => in_array($b['type'], ['Xỉu chủ đầu', 'Xỉu chủ đuôi']));
        $loBets = array_filter($result['multiple_bets'], fn($b) => $b['type'] === 'Bao lô 2 số');

        // XC should have [11, 22]
        $xcNumbers = array_unique(array_merge(...array_map(fn($b) => $b['numbers'], $xcBets)));
        sort($xcNumbers);
        $this->assertEquals(['11', '22'], $xcNumbers);

        // LO should have [33, 44, 55], NOT [11, 22]
        $loNumbers = array_unique(array_merge(...array_map(fn($b) => $b['numbers'], $loBets)));
        sort($loNumbers);
        $this->assertEquals(['33', '44', '55'], $loNumbers, 'Bao_lo should only have [33,44,55], not inherit from xiu_chu');
    }

    /**
     * Test: Ndai after amount delimiter should clear inheritance
     * Input: "tp, 11,22 lo 2n 2dai dx 33,44 1.5n"
     *
     * Expected:
     * - Bao_lo: [11, 22]
     * - Da_xien: [33, 44], NOT inheriting [11, 22]
     */
    public function test_ndai_after_amount_delimiter_clears_inheritance()
    {
        $parser = app(BettingMessageParser::class);

        $input = 'tp, 11,22 lo 2n 2dai dx 33,44 1.5n';

        $result = $parser->parse($input, [
            'date' => '2025-11-03',
            'region' => 'nam'
        ]);

        $this->assertTrue($result['is_valid'], 'Should be valid');

        // Filter by type
        $loBets = array_filter($result['multiple_bets'], fn($b) => $b['type'] === 'Bao lô 2 số');
        $dxBets = array_filter($result['multiple_bets'], fn($b) => $b['type'] === 'Đá xiên');

        // LO should have [11, 22]
        $loNumbers = array_unique(array_merge(...array_map(fn($b) => $b['numbers'], $loBets)));
        sort($loNumbers);
        $this->assertEquals(['11', '22'], $loNumbers);

        // DX should have [33, 44], NOT [11, 22]
        $dxNumbers = array_unique(array_merge(...array_map(fn($b) => array_values($b['numbers']), $dxBets)));
        sort($dxNumbers);
        $this->assertEquals(['33', '44'], $dxNumbers, 'Da_xien should only have [33,44], not inherit from bao_lo');
    }

    /**
     * Test: Events show that Ndai directive was triggered
     */
    public function test_events_show_ndai_directive()
    {
        $parser = app(BettingMessageParser::class);

        $input = '2dai 129, 169, 269, 069 xc 3.5n 2dai đax (52,68) - 1.4n';

        $result = $parser->parse($input, [
            'date' => '2025-11-03',
            'region' => 'nam'
        ]);

        $events = $result['debug']['events'] ?? [];

        // Should have 2 dai_count_set events (one for each "2dai")
        $daiCountEvents = array_filter($events, fn($e) => ($e['kind'] ?? '') === 'dai_count_set');
        $this->assertCount(2, $daiCountEvents, 'Should have 2 dai_count_set events');

        // Both should be count=2
        foreach ($daiCountEvents as $event) {
            $this->assertEquals(2, $event['count'] ?? null);
        }
    }
}

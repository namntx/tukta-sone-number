<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\BettingMessageParser;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BettingMessageParserInheritAtFlushTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\BettingTypesSeeder::class);
    }

    /**
     * Test: Nếu sau type token có số mới → KHÔNG inherit
     * Input: "2dai 11,22 lo 2n dx 33,44 1.5n"
     *
     * Expected:
     * - Bao_lo: [11, 22] @ 2000
     * - Da_xien: [33, 44] ONLY @ 1500 (không inherit [11,22])
     */
    public function test_no_inherit_when_new_numbers_after_type_token_ndai()
    {
        $parser = app(BettingMessageParser::class);

        $input = '2dai 11,22 lo 2n dx 33,44 1.5n';

        $result = $parser->parse($input, [
            'date' => '2025-11-03',
            'region' => 'nam'
        ]);

        $this->assertTrue($result['is_valid'], 'Should be valid');

        // Filter by type
        $loBets = array_filter($result['multiple_bets'], fn($b) => $b['type'] === 'Bao lô 2 số');
        $dxBets = array_filter($result['multiple_bets'], fn($b) => $b['type'] === 'Đá xiên');

        // Bao_lo should have [11, 22]
        $loNumbers = array_unique(array_merge(...array_map(fn($b) => $b['numbers'], $loBets)));
        sort($loNumbers);
        $this->assertEquals(['11', '22'], $loNumbers);

        // Check bao_lo amounts
        foreach ($loBets as $bet) {
            $this->assertEquals(2000, $bet['amount']);
        }

        // Da_xien should have [33, 44] ONLY (not inherit [11,22])
        $this->assertCount(1, $dxBets, 'Should have 1 da_xien bet');

        $dxNumbers = $dxBets[0]['numbers'];
        sort($dxNumbers);
        $this->assertEquals(['33', '44'], $dxNumbers, 'Da_xien should only have [33,44], NOT inherit [11,22]');

        // Check da_xien amount
        $this->assertEquals(1500, $dxBets[0]['amount']);

        // Ensure no overlap
        $this->assertNotContains('11', $dxNumbers, 'DX should not have 11 from bao_lo');
        $this->assertNotContains('22', $dxNumbers, 'DX should not have 22 from bao_lo');
    }

    /**
     * Test: Nếu sau type token có số mới → KHÔNG inherit (với explicit stations)
     * Input: "tp ct 11,22 lo 2n dx 33,44 1.5n"
     *
     * Expected:
     * - Bao_lo: [11, 22] @ 2000 with TP and CT stations
     * - Da_xien: [33, 44] ONLY @ 1500 (không inherit [11,22])
     */
    public function test_no_inherit_when_new_numbers_after_type_token_stations()
    {
        $parser = app(BettingMessageParser::class);

        $input = 'tp ct 11,22 lo 2n dx 33,44 1.5n';

        $result = $parser->parse($input, [
            'date' => '2025-11-03',
            'region' => 'nam'
        ]);

        $this->assertTrue($result['is_valid'], 'Should be valid');

        // Filter by type
        $loBets = array_filter($result['multiple_bets'], fn($b) => $b['type'] === 'Bao lô 2 số');
        $dxBets = array_filter($result['multiple_bets'], fn($b) => $b['type'] === 'Đá xiên');

        // Bao_lo should have [11, 22] with TP and CT stations
        $this->assertCount(4, $loBets, 'Should have 4 bao_lo bets (2 numbers × 2 stations)');

        $loNumbers = array_unique(array_merge(...array_map(fn($b) => $b['numbers'], $loBets)));
        sort($loNumbers);
        $this->assertEquals(['11', '22'], $loNumbers);

        $loStations = array_unique(array_map(fn($b) => $b['station'], $loBets));
        $this->assertContains('tp.hcm', $loStations);
        $this->assertContains('can tho', $loStations);

        // Da_xien should have [33, 44] ONLY
        $this->assertCount(1, $dxBets, 'Should have 1 da_xien bet');

        $dxNumbers = $dxBets[0]['numbers'];
        sort($dxNumbers);
        $this->assertEquals(['33', '44'], $dxNumbers, 'Da_xien should only have [33,44], NOT inherit [11,22]');

        // Check da_xien amount
        $this->assertEquals(1500, $dxBets[0]['amount']);
    }

    /**
     * Test: Nếu sau type token KHÔNG có số mới → inherit từ last_numbers
     * Input: "tp ct 11,22 lo 2n xc 3n"
     *
     * Expected:
     * - Bao_lo: [11, 22] @ 2000
     * - Xiu_chu: [11, 22] (inherited) @ 3000
     */
    public function test_inherit_when_no_new_numbers_after_type_token()
    {
        $parser = app(BettingMessageParser::class);

        $input = 'tp ct 11,22 lo 2n xc 3n';

        $result = $parser->parse($input, [
            'date' => '2025-11-03',
            'region' => 'nam'
        ]);

        $this->assertTrue($result['is_valid'], 'Should be valid');

        // Filter by type
        $loBets = array_filter($result['multiple_bets'], fn($b) => $b['type'] === 'Bao lô 2 số');
        $xcBets = array_filter($result['multiple_bets'], fn($b) => in_array($b['type'], ['Xỉu chủ đầu', 'Xỉu chủ đuôi']));

        // Bao_lo should have [11, 22]
        $loNumbers = array_unique(array_merge(...array_map(fn($b) => $b['numbers'], $loBets)));
        sort($loNumbers);
        $this->assertEquals(['11', '22'], $loNumbers);

        // Xiu_chu should INHERIT [11, 22] (no new numbers after xc token)
        $xcNumbers = array_unique(array_merge(...array_map(fn($b) => $b['numbers'], $xcBets)));
        sort($xcNumbers);
        $this->assertEquals(['11', '22'], $xcNumbers, 'Xiu_chu should inherit [11,22] because no new numbers');

        // Should have 8 xiu_chu bets (2 numbers × 2 types × 2 stations)
        $this->assertCount(8, $xcBets);

        // Check amounts
        foreach ($loBets as $bet) {
            $this->assertEquals(2000, $bet['amount']);
        }
        foreach ($xcBets as $bet) {
            $this->assertEquals(3000, $bet['amount']);
        }
    }

    /**
     * Test: Combo token với số mới → KHÔNG inherit
     * Input: "tp 11,22 lo 2n dd5n 33,44"
     *
     * Expected:
     * - Bao_lo: [11, 22] @ 2000
     * - Dau_duoi: [33, 44] @ 5000 (không inherit)
     */
    public function test_combo_token_with_new_numbers_no_inherit()
    {
        $parser = app(BettingMessageParser::class);

        $input = 'tp 11,22 lo 2n dd5n 33,44';

        $result = $parser->parse($input, [
            'date' => '2025-11-03',
            'region' => 'nam'
        ]);

        $this->assertTrue($result['is_valid'], 'Should be valid');

        // Filter by type
        $loBets = array_filter($result['multiple_bets'], fn($b) => $b['type'] === 'Bao lô 2 số');
        $dauBets = array_filter($result['multiple_bets'], fn($b) => $b['type'] === 'Đầu');
        $duoiBets = array_filter($result['multiple_bets'], fn($b) => $b['type'] === 'Đuôi');

        // Bao_lo: [11, 22]
        $loNumbers = array_unique(array_merge(...array_map(fn($b) => $b['numbers'], $loBets)));
        sort($loNumbers);
        $this->assertEquals(['11', '22'], $loNumbers);

        // Dau_duoi: [33, 44] ONLY (no inherit from [11,22])
        $ddNumbers = array_unique(array_merge(
            ...array_map(fn($b) => $b['numbers'], array_merge($dauBets, $duoiBets))
        ));
        sort($ddNumbers);
        $this->assertEquals(['33', '44'], $ddNumbers, 'Dau_duoi should only have [33,44], NOT inherit [11,22]');

        // Check amounts
        foreach ($loBets as $bet) {
            $this->assertEquals(2000, $bet['amount']);
        }
        foreach (array_merge($dauBets, $duoiBets) as $bet) {
            $this->assertEquals(5000, $bet['amount']);
        }
    }

    /**
     * Test: Combo token KHÔNG có số mới → inherit
     * Input: "tp 11,22 lo 2n dd5n"
     *
     * Expected:
     * - Bao_lo: [11, 22] @ 2000
     * - Dau_duoi: [11, 22] (inherited) @ 5000
     */
    public function test_combo_token_no_new_numbers_inherit()
    {
        $parser = app(BettingMessageParser::class);

        $input = 'tp 11,22 lo 2n dd5n';

        $result = $parser->parse($input, [
            'date' => '2025-11-03',
            'region' => 'nam'
        ]);

        $this->assertTrue($result['is_valid'], 'Should be valid');

        // Filter by type
        $loBets = array_filter($result['multiple_bets'], fn($b) => $b['type'] === 'Bao lô 2 số');
        $dauBets = array_filter($result['multiple_bets'], fn($b) => $b['type'] === 'Đầu');
        $duoiBets = array_filter($result['multiple_bets'], fn($b) => $b['type'] === 'Đuôi');

        // Bao_lo: [11, 22]
        $loNumbers = array_unique(array_merge(...array_map(fn($b) => $b['numbers'], $loBets)));
        sort($loNumbers);
        $this->assertEquals(['11', '22'], $loNumbers);

        // Dau_duoi should INHERIT [11, 22]
        $ddNumbers = array_unique(array_merge(
            ...array_map(fn($b) => $b['numbers'], array_merge($dauBets, $duoiBets))
        ));
        sort($ddNumbers);
        $this->assertEquals(['11', '22'], $ddNumbers, 'Dau_duoi should inherit [11,22]');

        // Check amounts
        foreach ($loBets as $bet) {
            $this->assertEquals(2000, $bet['amount']);
        }
        foreach (array_merge($dauBets, $duoiBets) as $bet) {
            $this->assertEquals(5000, $bet['amount']);
        }
    }

    /**
     * Test: Events show inherit_numbers_at_flush
     */
    public function test_events_show_inherit_at_flush()
    {
        $parser = app(BettingMessageParser::class);

        // Case with inheritance
        $input1 = 'tp 11,22 lo 2n xc 3n';
        $result1 = $parser->parse($input1, ['date' => '2025-11-03', 'region' => 'nam']);

        $events1 = $result1['debug']['events'] ?? [];
        $hasInherit = false;
        foreach ($events1 as $event) {
            if (($event['kind'] ?? '') === 'inherit_numbers_at_flush') {
                $hasInherit = true;
                $this->assertEquals('xiu_chu', $event['type'] ?? null);
                break;
            }
        }
        $this->assertTrue($hasInherit, 'Should have inherit_numbers_at_flush event for xc');

        // Case without inheritance (new numbers)
        $input2 = 'tp 11,22 lo 2n dx 33,44 1.5n';
        $result2 = $parser->parse($input2, ['date' => '2025-11-03', 'region' => 'nam']);

        $events2 = $result2['debug']['events'] ?? [];
        $inheritCount = 0;
        foreach ($events2 as $event) {
            if (($event['kind'] ?? '') === 'inherit_numbers_at_flush' && ($event['type'] ?? '') === 'da_xien') {
                $inheritCount++;
            }
        }
        $this->assertEquals(0, $inheritCount, 'Should NOT have inherit event for dx (has new numbers)');
    }

    /**
     * Test: Multiple types in sequence with mixed inherit/no-inherit
     * Input: "tp 11,22 lo 2n xc 3n dx 33,44 1.5n dd 2n"
     *
     * Expected:
     * - lo: [11, 22]
     * - xc: [11, 22] (inherited)
     * - dx: [33, 44] (NOT inherited)
     * - dd: [33, 44] (inherited from dx)
     */
    public function test_mixed_inheritance_in_sequence()
    {
        $parser = app(BettingMessageParser::class);

        $input = 'tp 11,22 lo 2n xc 3n dx 33,44 1.5n dd 2n';

        $result = $parser->parse($input, [
            'date' => '2025-11-03',
            'region' => 'nam'
        ]);

        $this->assertTrue($result['is_valid'], 'Should be valid');

        // Get bets by type
        $loBets = array_filter($result['multiple_bets'], fn($b) => $b['type'] === 'Bao lô 2 số');
        $xcBets = array_filter($result['multiple_bets'], fn($b) => in_array($b['type'], ['Xỉu chủ đầu', 'Xỉu chủ đuôi']));
        $dxBets = array_filter($result['multiple_bets'], fn($b) => $b['type'] === 'Đá xiên');
        $dauBets = array_filter($result['multiple_bets'], fn($b) => $b['type'] === 'Đầu');
        $duoiBets = array_filter($result['multiple_bets'], fn($b) => $b['type'] === 'Đuôi');

        // Check numbers for each type
        $loNumbers = array_unique(array_merge(...array_map(fn($b) => $b['numbers'], $loBets)));
        sort($loNumbers);
        $this->assertEquals(['11', '22'], $loNumbers, 'lo should have [11,22]');

        $xcNumbers = array_unique(array_merge(...array_map(fn($b) => $b['numbers'], $xcBets)));
        sort($xcNumbers);
        $this->assertEquals(['11', '22'], $xcNumbers, 'xc should inherit [11,22]');

        $dxNumbers = array_values($dxBets)[0]['numbers'];
        sort($dxNumbers);
        $this->assertEquals(['33', '44'], $dxNumbers, 'dx should have [33,44], NOT inherit [11,22]');

        $ddNumbers = array_unique(array_merge(
            ...array_map(fn($b) => $b['numbers'], array_merge($dauBets, $duoiBets))
        ));
        sort($ddNumbers);
        $this->assertEquals(['33', '44'], $ddNumbers, 'dd should inherit [33,44] from dx');
    }
}

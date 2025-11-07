<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\BettingMessageParser;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BettingMessageParserXiuChuDefaultSplitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\BettingTypesSeeder::class);
    }

    /**
     * Test: Xỉu chủ mặc định tách thành xỉu chủ đầu + xỉu chủ đuôi
     */
    public function test_xiu_chu_default_splits_to_dau_and_duoi()
    {
        $parser = app(BettingMessageParser::class);

        // Input: xc 903.361.121.204. 3n
        $input = 'tp, xc 903.361.121.204. 3n';

        $result = $parser->parse($input, [
            'date' => '2025-11-03',
            'region' => 'nam'
        ]);

        // Should be valid
        $this->assertTrue($result['is_valid'], 'Should be valid');

        // Filter xiu_chu_dau bets
        $dauBets = array_values(array_filter(
            $result['multiple_bets'],
            fn($b) => $b['type'] === 'Xỉu chủ đầu'
        ));

        // Filter xiu_chu_duoi bets
        $duoiBets = array_values(array_filter(
            $result['multiple_bets'],
            fn($b) => $b['type'] === 'Xỉu chủ đuôi'
        ));

        // Should have 4 numbers x 2 (dau + duoi) = 8 bets total
        $this->assertCount(4, $dauBets, 'Should have 4 xiu_chu_dau bets');
        $this->assertCount(4, $duoiBets, 'Should have 4 xiu_chu_duoi bets');

        // Check numbers
        $dauNumbers = array_map(fn($b) => $b['numbers'][0], $dauBets);
        $duoiNumbers = array_map(fn($b) => $b['numbers'][0], $duoiBets);

        $this->assertEquals(['903', '361', '121', '204'], $dauNumbers, 'Dau should have all 4 numbers');
        $this->assertEquals(['903', '361', '121', '204'], $duoiNumbers, 'Duoi should have all 4 numbers');

        // Check amounts (all should be 3000)
        foreach ($dauBets as $bet) {
            $this->assertEquals(3000, $bet['amount'], 'Dau amount should be 3000 (3n)');
        }
        foreach ($duoiBets as $bet) {
            $this->assertEquals(3000, $bet['amount'], 'Duoi amount should be 3000 (3n)');
        }

        // Should NOT have plain 'Xỉu chủ' type (must be split)
        $plainXiuChu = array_filter(
            $result['multiple_bets'],
            fn($b) => $b['type'] === 'Xỉu chủ'
        );
        $this->assertEmpty($plainXiuChu, 'Should NOT have plain Xỉu chủ bets (must be split to dau+duoi)');
    }

    /**
     * Test: Single number also splits
     */
    public function test_xiu_chu_single_number_also_splits()
    {
        $parser = app(BettingMessageParser::class);

        // Input: xc 92 5n
        $input = 'tp, xc 92 5n';

        $result = $parser->parse($input, [
            'date' => '2025-11-03',
            'region' => 'nam'
        ]);

        // Should have 1 number x 2 (dau + duoi) = 2 bets
        $dauBets = array_filter($result['multiple_bets'], fn($b) => $b['type'] === 'Xỉu chủ đầu');
        $duoiBets = array_filter($result['multiple_bets'], fn($b) => $b['type'] === 'Xỉu chủ đuôi');

        $this->assertCount(1, $dauBets, 'Should have 1 dau bet');
        $this->assertCount(1, $duoiBets, 'Should have 1 duoi bet');

        // Both should have number 92
        $this->assertEquals('92', array_values($dauBets)[0]['numbers'][0]);
        $this->assertEquals('92', array_values($duoiBets)[0]['numbers'][0]);
    }

    /**
     * Test: Explicit dd amount still works
     */
    public function test_xiu_chu_with_dd_amount_still_works()
    {
        $parser = app(BettingMessageParser::class);

        // Input: xc 92 dd5n
        $input = 'tp, xc 92 dd5n';

        $result = $parser->parse($input, [
            'date' => '2025-11-03',
            'region' => 'nam'
        ]);

        // Should also split to dau + duoi
        $dauBets = array_filter($result['multiple_bets'], fn($b) => $b['type'] === 'Xỉu chủ đầu');
        $duoiBets = array_filter($result['multiple_bets'], fn($b) => $b['type'] === 'Xỉu chủ đuôi');

        $this->assertCount(1, $dauBets, 'Should have 1 dau bet');
        $this->assertCount(1, $duoiBets, 'Should have 1 duoi bet');

        // Amount should be 5000
        $this->assertEquals(5000, array_values($dauBets)[0]['amount']);
        $this->assertEquals(5000, array_values($duoiBets)[0]['amount']);
    }

    /**
     * Test: Check events for default split
     */
    public function test_xiu_chu_events_show_default_split()
    {
        $parser = app(BettingMessageParser::class);

        $input = 'tp, xc 903 3n';

        $result = $parser->parse($input, [
            'date' => '2025-11-03',
            'region' => 'nam'
        ]);

        // Check events for default split
        $events = $result['debug']['events'] ?? [];
        $defaultSplitEvent = null;

        foreach ($events as $event) {
            if (($event['kind'] ?? '') === 'emit_xc_split_per_number_default') {
                $defaultSplitEvent = $event;
                break;
            }
        }

        $this->assertNotNull($defaultSplitEvent, 'Should have emit_xc_split_per_number_default event');
        $this->assertEquals(['903'], $defaultSplitEvent['numbers']);
        $this->assertEquals(3000, $defaultSplitEvent['amount']);
    }
}

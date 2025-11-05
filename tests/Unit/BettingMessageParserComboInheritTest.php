<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\BettingMessageParser;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BettingMessageParserComboInheritTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\BettingTypesSeeder::class);
    }

    /**
     * Test: Combo token (dd5n) không nên kế thừa số sang type khác (xc)
     * Bug: Sau dd5n flush, last_numbers vẫn còn và bị xc kế thừa
     * Fix: Clear last_numbers sau combo token auto flush
     */
    public function test_combo_token_should_not_inherit_numbers_to_next_type()
    {
        $parser = app(BettingMessageParser::class);

        // Input: lo + dd5n (combo) + xc với số mới
        $input = 'tp .lo 04.03.08.21.61. 5n dd5n..xc 903.361.121.204. 3n';

        $result = $parser->parse($input, [
            'date' => '2025-11-03',
            'region' => 'nam'
        ]);

        // Filter xỉu chủ bets
        $xiuChuBets = array_values(array_filter(
            $result['multiple_bets'],
            fn($b) => $b['type'] === 'Xỉu chủ'
        ));

        // Extract numbers
        $xiuChuNumbers = array_map(fn($b) => $b['numbers'][0], $xiuChuBets);

        // Assertions
        $this->assertCount(4, $xiuChuBets, 'Should have exactly 4 xỉu chủ bets');
        $this->assertEquals(['903', '361', '121', '204'], $xiuChuNumbers, 'Xỉu chủ should only have new numbers');

        // Ensure old numbers are NOT inherited
        $this->assertNotContains('04', $xiuChuNumbers, '04 should not be in xỉu chủ');
        $this->assertNotContains('03', $xiuChuNumbers, '03 should not be in xỉu chủ');
        $this->assertNotContains('08', $xiuChuNumbers, '08 should not be in xỉu chủ');
        $this->assertNotContains('21', $xiuChuNumbers, '21 should not be in xỉu chủ');
        $this->assertNotContains('61', $xiuChuNumbers, '61 should not be in xỉu chủ');
    }
}

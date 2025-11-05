<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\BettingMessageParser;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BettingMessageParserDecimalAmountTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\BettingTypesSeeder::class);
    }

    /**
     * Test: Amount token hỗ trợ số thập phân (3.5n, 7.5n)
     */
    public function test_amount_token_supports_decimal_numbers()
    {
        $parser = app(BettingMessageParser::class);

        // Input: 3đài, 92 blo 3.5n, 211 xc 7n
        $input = '3đài, 92 blo 3.5n, 211 xc 7n';

        $result = $parser->parse($input, [
            'date' => '2025-11-03',
            'region' => 'nam'
        ]);

        // Filter xỉu chủ bets
        $xiuChuBets = array_values(array_filter(
            $result['multiple_bets'],
            fn($b) => $b['type'] === 'Xỉu chủ'
        ));

        // Should have 2 numbers x 3 stations = 6 bets
        $this->assertCount(6, $xiuChuBets, 'Should have 6 xỉu chủ bets (2 numbers x 3 stations)');

        // Check amounts
        foreach ($xiuChuBets as $bet) {
            $number = $bet['numbers'][0];

            if ($number === '92') {
                // 92 blo 3.5n → amount should be 3500
                $this->assertEquals(3500, $bet['amount'], '92 should have amount 3500 (3.5n)');
            } elseif ($number === '211') {
                // 211 xc 7n → amount should be 7000
                $this->assertEquals(7000, $bet['amount'], '211 should have amount 7000 (7n)');
            }
        }
    }

    /**
     * Test: Combo token hỗ trợ số thập phân (lo3.5n, dd7.5n)
     */
    public function test_combo_token_supports_decimal_amounts()
    {
        $parser = app(BettingMessageParser::class);

        // Input: 92 lo3.5n, 45 dd7.5n
        $input = 'tp, 92 lo3.5n, 45 dd7.5n';

        $result = $parser->parse($input, [
            'date' => '2025-11-03',
            'region' => 'nam'
        ]);

        // Filter bao lô bets
        $baoloBets = array_values(array_filter(
            $result['multiple_bets'],
            fn($b) => $b['type'] === 'Bao lô 2 số'
        ));

        $this->assertCount(1, $baoloBets, 'Should have 1 bao lô bet');
        $this->assertEquals('92', $baoloBets[0]['numbers'][0]);
        $this->assertEquals(3500, $baoloBets[0]['amount'], '92 lo3.5n should have amount 3500');

        // Filter đầu đuôi bets
        $dauBets = array_values(array_filter(
            $result['multiple_bets'],
            fn($b) => $b['type'] === 'Đầu'
        ));

        $duoiBets = array_values(array_filter(
            $result['multiple_bets'],
            fn($b) => $b['type'] === 'Đuôi'
        ));

        $this->assertCount(1, $dauBets, 'Should have 1 đầu bet');
        $this->assertEquals('45', $dauBets[0]['numbers'][0]);
        $this->assertEquals(7500, $dauBets[0]['amount'], '45 dd7.5n (đầu) should have amount 7500');

        $this->assertCount(1, $duoiBets, 'Should have 1 đuôi bet');
        $this->assertEquals('45', $duoiBets[0]['numbers'][0]);
        $this->assertEquals(7500, $duoiBets[0]['amount'], '45 dd7.5n (đuôi) should have amount 7500');
    }

    /**
     * Test: Tokenizer không tách số thập phân trong amount
     */
    public function test_tokenizer_preserves_decimal_in_amount_tokens()
    {
        $parser = app(BettingMessageParser::class);

        $input = '92 blo 3.5n';

        $result = $parser->parse($input, [
            'date' => '2025-11-03',
            'region' => 'nam'
        ]);

        // Kiểm tra tokens
        $this->assertNotContains('3', $result['tokens'], 'Should not split 3.5n into separate tokens');
        $this->assertNotContains('5n', $result['tokens'], 'Should not split 3.5n into separate tokens');
        $this->assertContains('3.5n', $result['tokens'], 'Should preserve 3.5n as single token');
    }

    /**
     * Test: Nhiều amount thập phân khác nhau
     */
    public function test_various_decimal_amounts()
    {
        $parser = app(BettingMessageParser::class);

        $testCases = [
            ['input' => 'tp, 92 2.5n', 'expected_amount' => 2500],
            ['input' => 'tp, 92 4.8n', 'expected_amount' => 4800],
            ['input' => 'tp, 92 10.25n', 'expected_amount' => 10250],
            ['input' => 'tp, 92 0.5n', 'expected_amount' => 500],
        ];

        foreach ($testCases as $case) {
            $result = $parser->parse($case['input'], [
                'date' => '2025-11-03',
                'region' => 'nam'
            ]);

            $this->assertNotEmpty($result['multiple_bets'], "Should have bets for: {$case['input']}");
            $this->assertEquals(
                $case['expected_amount'],
                $result['multiple_bets'][0]['amount'],
                "Amount mismatch for: {$case['input']}"
            );
        }
    }
}

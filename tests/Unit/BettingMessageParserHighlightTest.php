<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\BettingMessageParser;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BettingMessageParserHighlightTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\BettingTypesSeeder::class);
    }

    /**
     * Test: Skipped tokens should be highlighted in red
     */
    public function test_skipped_tokens_are_highlighted()
    {
        $parser = app(BettingMessageParser::class);

        // Input with unknown token "xyz"
        $input = 'tp, 92 xyz 5n';

        $result = $parser->parse($input, [
            'date' => '2025-11-03',
            'region' => 'nam'
        ]);

        // Check if highlighted_message field exists
        $this->assertArrayHasKey('highlighted_message', $result, 'Should have highlighted_message field');

        // Check if "xyz" is wrapped in red span
        $highlighted = $result['highlighted_message'];
        $this->assertStringContainsString('xyz', $highlighted, 'Should contain xyz token');
        $this->assertStringContainsString('text-red-600', $highlighted, 'Should contain text-red-600 class');
        $this->assertStringContainsString('<span', $highlighted, 'Should contain span tag');
    }

    /**
     * Test: Multiple skipped tokens should all be highlighted
     */
    public function test_multiple_skipped_tokens_are_highlighted()
    {
        $parser = app(BettingMessageParser::class);

        // Input with multiple unknown tokens
        $input = 'tp, 92 abc def 5n';

        $result = $parser->parse($input, [
            'date' => '2025-11-03',
            'region' => 'nam'
        ]);

        $highlighted = $result['highlighted_message'];

        // Both "abc" and "def" should be highlighted
        $this->assertStringContainsString('abc', $highlighted, 'Should contain abc');
        $this->assertStringContainsString('def', $highlighted, 'Should contain def');

        // Count number of red spans
        $spanCount = substr_count($highlighted, 'text-red-600');
        $this->assertGreaterThanOrEqual(2, $spanCount, 'Should have at least 2 highlighted tokens');
    }

    /**
     * Test: No skipped tokens = no highlighting (plain HTML escaped text)
     */
    public function test_no_skipped_tokens_returns_plain_text()
    {
        $parser = app(BettingMessageParser::class);

        // Valid input with no skipped tokens
        $input = 'tp, 92 xc 5n';

        $result = $parser->parse($input, [
            'date' => '2025-11-03',
            'region' => 'nam'
        ]);

        $highlighted = $result['highlighted_message'];

        // Should not contain red highlighting
        $this->assertStringNotContainsString('text-red-600', $highlighted, 'Should not have any red highlights');
        $this->assertStringNotContainsString('<span', $highlighted, 'Should not have any span tags');
    }

    /**
     * Test: Vietnamese accents in skipped tokens are preserved
     */
    public function test_vietnamese_accents_preserved_in_highlighting()
    {
        $parser = app(BettingMessageParser::class);

        // Input with Vietnamese token
        $input = 'tp, 92 không_rõ 5n';

        $result = $parser->parse($input, [
            'date' => '2025-11-03',
            'region' => 'nam'
        ]);

        $highlighted = $result['highlighted_message'];

        // Original Vietnamese text should be preserved in output
        $this->assertStringContainsString('không', $highlighted, 'Should preserve Vietnamese accents');
    }

    /**
     * Test: HTML special characters are escaped
     */
    public function test_html_special_characters_are_escaped()
    {
        $parser = app(BettingMessageParser::class);

        // Input with HTML characters
        $input = 'tp, 92 <script> 5n';

        $result = $parser->parse($input, [
            'date' => '2025-11-03',
            'region' => 'nam'
        ]);

        $highlighted = $result['highlighted_message'];

        // HTML should be escaped
        $this->assertStringNotContainsString('<script>', $highlighted, 'Should escape <script> tag');
        $this->assertStringContainsString('&lt;script&gt;', $highlighted, 'Should contain escaped HTML');
    }

    /**
     * Test: Original message structure is preserved
     */
    public function test_original_message_structure_preserved()
    {
        $parser = app(BettingMessageParser::class);

        // Input with spaces and punctuation
        $input = 'tp, 92  unknown  5n';

        $result = $parser->parse($input, [
            'date' => '2025-11-03',
            'region' => 'nam'
        ]);

        $highlighted = $result['highlighted_message'];

        // Should preserve spaces (though might be HTML escaped)
        $this->assertStringContainsString('tp', $highlighted);
        $this->assertStringContainsString('92', $highlighted);
        $this->assertStringContainsString('5n', $highlighted);
    }
}

<?php

namespace Tests\Unit\Services;

use App\Services\Summarization\AnthropicSummarizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

/**
 * Extends the Laravel TestCase (not plain PHPUnit) so the Http facade
 * is available, but stubs the HTTP call entirely with Http::fake() --
 * no real network access or API key needed.
 */
class AnthropicSummarizerTest extends TestCase
{
    use RefreshDatabase;

    private function summarizer(): AnthropicSummarizer
    {
        return new AnthropicSummarizer(
            apiKey: 'test-key',
            model: 'claude-sonnet-4-5',
            endpoint: 'https://api.anthropic.com/v1/messages',
            version: '2023-06-01',
        );
    }

    public function test_it_parses_a_well_formed_json_response(): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [
                    ['type' => 'text', 'text' => '{"summary":"A quick recap.","action_items":["Bob: chase invoice"]}'],
                ],
            ]),
        ]);

        $result = $this->summarizer()->summarize('some transcript text');

        $this->assertSame('A quick recap.', $result->summary);
        $this->assertSame(['Bob: chase invoice'], $result->actionItems);
    }

    public function test_it_strips_markdown_code_fences_before_parsing(): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [
                    ['type' => 'text', 'text' => "```json\n{\"summary\":\"Recap.\",\"action_items\":[]}\n```"],
                ],
            ]),
        ]);

        $result = $this->summarizer()->summarize('some transcript text');

        $this->assertSame('Recap.', $result->summary);
        $this->assertSame([], $result->actionItems);
    }

    public function test_it_throws_on_an_error_response(): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response(['error' => 'bad request'], 400),
        ]);

        $this->expectException(RuntimeException::class);

        $this->summarizer()->summarize('some transcript text');
    }

    public function test_it_requires_an_api_key(): void
    {
        $summarizer = new AnthropicSummarizer(
            apiKey: '',
            model: 'claude-sonnet-4-5',
            endpoint: 'https://api.anthropic.com/v1/messages',
            version: '2023-06-01',
        );

        $this->expectException(RuntimeException::class);

        $summarizer->summarize('some transcript text');
    }
}

<?php

namespace App\Services\Summarization;

use Illuminate\Support\Facades\Http;
use JsonException;
use RuntimeException;

/**
 * Real LLM driver, calling Anthropic's Messages API and asking Claude to
 * return strict JSON so it can be parsed straight into a SummaryResult.
 */
class AnthropicSummarizer implements SummarizerInterface
{
    private const string SYSTEM_PROMPT = <<<'PROMPT'
        You summarize meeting/call transcripts for a busy professional.

        Respond with ONLY a single JSON object, no prose before or after it,
        matching exactly this shape:

        {"summary": string, "action_items": string[]}

        "summary" is 2-4 sentences capturing what was discussed and decided.
        "action_items" is a list of concrete follow-up tasks mentioned in the
        transcript, each written as "<owner>: <task>" when an owner is named,
        or just "<task>" when it isn't. Return an empty array if there are
        genuinely no action items.
        PROMPT;

    public function __construct(
        private readonly string $apiKey,
        private readonly string $model,
        private readonly string $endpoint,
        private readonly string $version,
    ) {
    }

    public function summarize(string $transcript): SummaryResult
    {
        if (blank($this->apiKey)) {
            throw new RuntimeException('ANTHROPIC_API_KEY is not configured; cannot use the anthropic summarizer driver.');
        }

        $response = Http::withHeaders([
            'x-api-key' => $this->apiKey,
            'anthropic-version' => $this->version,
        ])
            ->timeout(120)
            ->post($this->endpoint, [
                'model' => $this->model,
                'max_tokens' => 1024,
                'system' => self::SYSTEM_PROMPT,
                'messages' => [
                    ['role' => 'user', 'content' => "Transcript:\n\n{$transcript}"],
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException(
                "Anthropic summarization request failed ({$response->status()}): {$response->body()}"
            );
        }

        $textBlock = collect($response->json('content'))->firstWhere('type', 'text');
        $blockText = (string) ($textBlock['text'] ?? '');

        if (blank($blockText)) {
            throw new RuntimeException('Anthropic response did not contain a text content block.');
        }

        try {
            $decoded = json_decode($this->extractJson($blockText), associative: true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new RuntimeException("Could not parse JSON from Anthropic response: {$e->getMessage()}. Raw: {$blockText}");
        }

        return new SummaryResult(
            summary: (string) ($decoded['summary'] ?? ''),
            actionItems: array_values(array_map('strval', $decoded['action_items'] ?? [])),
        );
    }

    /**
     * Claude is instructed to return raw JSON, but strip any accidental
     * markdown code fences defensively so parsing doesn't break on them.
     */
    private function extractJson(string $text): string
    {
        $text = trim($text);
        $text = preg_replace('/^```(?:json)?/', '', $text);
        $text = preg_replace('/```$/', '', $text);

        return trim($text);
    }
}

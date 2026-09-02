<?php

namespace App\Services\Summarization;

/**
 * Deterministic, offline stand-in for a real LLM summarizer. Bound whenever
 * `recordings.summarizer` is "fake" (the default) -- see FakeTranscriber
 * for why this exists.
 *
 * It does not attempt real natural-language understanding: it just takes
 * the first couple of sentences as a "summary" and treats any sentence
 * containing a person's name + a verb like "will"/"is going to" as an
 * "action item". Good enough to exercise the full pipeline end to end.
 */
class FakeSummarizer implements SummarizerInterface
{
    public function summarize(string $transcript): SummaryResult
    {
        $sentences = preg_split('/(?<=[.!?])\s+/', trim($transcript)) ?: [];
        $sentences = array_values(array_filter($sentences, fn ($s) => trim($s) !== ''));

        $summary = implode(' ', array_slice($sentences, 1, 2)) ?: 'No summary available.';

        $actionItems = array_values(array_filter(
            $sentences,
            fn ($s) => preg_match('/\b(will|is going to|going to|agreed to)\b/i', $s) === 1,
        ));

        if ($actionItems === []) {
            $actionItems = ['No explicit action items were detected in this transcript.'];
        }

        return new SummaryResult(
            summary: trim($summary),
            actionItems: array_map(trim(...), $actionItems),
        );
    }
}

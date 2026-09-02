<?php

namespace Tests\Unit\Services;

use App\Services\Summarization\FakeSummarizer;
use PHPUnit\Framework\TestCase;

class FakeSummarizerTest extends TestCase
{
    public function test_it_extracts_sentences_that_look_like_action_items(): void
    {
        $transcript = 'We reviewed the roadmap. Sarah will email the client tomorrow. '.
            'Everyone agreed the design looks good. James is going to update the ticket.';

        $result = (new FakeSummarizer())->summarize($transcript);

        $this->assertNotEmpty($result->summary);
        $this->assertCount(2, $result->actionItems);
        $this->assertStringContainsString('Sarah will email the client tomorrow', $result->actionItems[0]);
        $this->assertStringContainsString('James is going to update the ticket', $result->actionItems[1]);
    }

    public function test_it_returns_a_placeholder_when_no_action_items_are_found(): void
    {
        $result = (new FakeSummarizer())->summarize('Just a short chat with no follow-ups.');

        $this->assertSame(
            ['No explicit action items were detected in this transcript.'],
            $result->actionItems,
        );
    }
}

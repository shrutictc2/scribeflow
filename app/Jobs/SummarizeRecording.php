<?php

namespace App\Jobs;

use App\Enums\RecordingStatus;
use App\Models\Recording;
use App\Services\Summarization\SummarizerInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class SummarizeRecording implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [10, 30, 60];

    public function __construct(
        public readonly Recording $recording,
    ) {
    }

    public function handle(SummarizerInterface $summarizer): void
    {
        if (blank($this->recording->transcript)) {
            throw new \RuntimeException('Cannot summarize a recording with no transcript.');
        }

        $this->recording->markStatus(RecordingStatus::Summarizing);

        $result = $summarizer->summarize($this->recording->transcript);

        $this->recording->markStatus(RecordingStatus::Completed, [
            'summary' => $result->summary,
            'action_items' => $result->actionItems,
        ]);
    }

    public function failed(Throwable $exception): void
    {
        $this->recording->markFailed("Summarization failed: {$exception->getMessage()}");
    }
}

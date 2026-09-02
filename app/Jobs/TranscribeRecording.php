<?php

namespace App\Jobs;

use App\Enums\RecordingStatus;
use App\Models\Recording;
use App\Services\Transcription\TranscriberInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class TranscribeRecording implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [10, 30, 60];

    public function __construct(
        public readonly Recording $recording,
    ) {
    }

    public function handle(TranscriberInterface $transcriber): void
    {
        $this->recording->markStatus(RecordingStatus::Transcribing);

        $result = $transcriber->transcribe($this->recording);

        $this->recording->markStatus(RecordingStatus::Transcribed, [
            'transcript' => $result->text,
        ]);

        SummarizeRecording::dispatch($this->recording);
    }

    public function failed(Throwable $exception): void
    {
        $this->recording->markFailed("Transcription failed: {$exception->getMessage()}");
    }
}

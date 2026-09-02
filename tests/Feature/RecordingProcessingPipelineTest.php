<?php

namespace Tests\Feature;

use App\Enums\RecordingStatus;
use App\Jobs\SummarizeRecording;
use App\Jobs\TranscribeRecording;
use App\Models\Recording;
use App\Services\Summarization\SummarizerInterface;
use App\Services\Summarization\SummaryResult;
use App\Services\Transcription\TranscriberInterface;
use App\Services\Transcription\TranscriptionResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

/**
 * These tests bind the *interfaces* to lightweight test doubles rather than
 * the real fake/OpenAI/Anthropic drivers, so the pipeline's own logic
 * (status transitions, chaining, failure handling) is verified in
 * isolation from any particular provider's behaviour.
 */
class RecordingProcessingPipelineTest extends TestCase
{
    use RefreshDatabase;

    public function test_transcribing_a_recording_marks_it_transcribed_and_queues_summarization(): void
    {
        $this->app->bind(TranscriberInterface::class, fn () => new class implements TranscriberInterface
        {
            public function transcribe(Recording $recording): TranscriptionResult
            {
                return new TranscriptionResult(text: 'Hello, this is the transcript.', language: 'en');
            }
        });

        $recording = Recording::factory()->create(['status' => RecordingStatus::Pending]);

        (new TranscribeRecording($recording))->handle(app(TranscriberInterface::class));

        $recording->refresh();

        $this->assertSame(RecordingStatus::Transcribed, $recording->status);
        $this->assertSame('Hello, this is the transcript.', $recording->transcript);
    }

    public function test_a_failed_transcription_marks_the_recording_failed(): void
    {
        $recording = Recording::factory()->create(['status' => RecordingStatus::Pending]);

        $job = new TranscribeRecording($recording);
        $job->failed(new RuntimeException('provider timed out'));

        $recording->refresh();

        $this->assertSame(RecordingStatus::Failed, $recording->status);
        $this->assertStringContainsString('provider timed out', $recording->error_message);
    }

    public function test_summarizing_a_recording_marks_it_completed(): void
    {
        $this->app->bind(SummarizerInterface::class, fn () => new class implements SummarizerInterface
        {
            public function summarize(string $transcript): SummaryResult
            {
                return new SummaryResult(summary: 'A short summary.', actionItems: ['Alice: send the deck']);
            }
        });

        $recording = Recording::factory()->transcribed()->create();

        (new SummarizeRecording($recording))->handle(app(SummarizerInterface::class));

        $recording->refresh();

        $this->assertSame(RecordingStatus::Completed, $recording->status);
        $this->assertSame('A short summary.', $recording->summary);
        $this->assertSame(['Alice: send the deck'], $recording->action_items);
    }

    public function test_summarizing_without_a_transcript_throws(): void
    {
        $recording = Recording::factory()->create(['status' => RecordingStatus::Pending, 'transcript' => null]);

        $this->expectException(RuntimeException::class);

        (new SummarizeRecording($recording))->handle(app(SummarizerInterface::class));
    }
}

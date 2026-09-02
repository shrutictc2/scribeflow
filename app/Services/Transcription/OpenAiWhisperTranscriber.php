<?php

namespace App\Services\Transcription;

use App\Models\Recording;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Real speech-to-text driver, calling OpenAI's audio transcription endpoint
 * (Whisper). Swap in a different provider (AssemblyAI, Deepgram, AWS
 * Transcribe, ...) by adding a sibling class that implements
 * TranscriberInterface and wiring it up in AppServiceProvider.
 */
class OpenAiWhisperTranscriber implements TranscriberInterface
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $model,
        private readonly string $endpoint,
    ) {
    }

    public function transcribe(Recording $recording): TranscriptionResult
    {
        if (blank($this->apiKey)) {
            throw new RuntimeException('OPENAI_API_KEY is not configured; cannot use the openai transcriber driver.');
        }

        $response = Http::withToken($this->apiKey)
            ->timeout(120)
            ->attach(
                'file',
                $recording->fileContents(),
                $recording->original_filename,
            )
            ->post($this->endpoint, [
                'model' => $this->model,
                'response_format' => 'json',
            ]);

        if ($response->failed()) {
            throw new RuntimeException(
                "OpenAI transcription request failed ({$response->status()}): {$response->body()}"
            );
        }

        $text = (string) $response->json('text');

        if (blank($text)) {
            throw new RuntimeException('OpenAI transcription response did not contain any text.');
        }

        return new TranscriptionResult(
            text: $text,
            language: $response->json('language'),
        );
    }
}

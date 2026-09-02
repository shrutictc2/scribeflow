<?php

namespace App\Providers;

use App\Services\Summarization\AnthropicSummarizer;
use App\Services\Summarization\FakeSummarizer;
use App\Services\Summarization\SummarizerInterface;
use App\Services\Transcription\FakeTranscriber;
use App\Services\Transcription\OpenAiWhisperTranscriber;
use App\Services\Transcription\TranscriberInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * Which concrete driver backs each interface is decided entirely by
     * config (see config/recordings.php), so the rest of the app --
     * controllers, jobs -- only ever depends on the interfaces.
     */
    public function register(): void
    {
        $this->app->bind(TranscriberInterface::class, function () {
            return match (config('recordings.transcriber')) {
                'openai' => new OpenAiWhisperTranscriber(
                    apiKey: (string) config('services.openai.key'),
                    model: (string) config('services.openai.transcription_model'),
                    endpoint: (string) config('services.openai.transcription_endpoint'),
                ),
                default => new FakeTranscriber(),
            };
        });

        $this->app->bind(SummarizerInterface::class, function () {
            return match (config('recordings.summarizer')) {
                'anthropic' => new AnthropicSummarizer(
                    apiKey: (string) config('services.anthropic.key'),
                    model: (string) config('services.anthropic.model'),
                    endpoint: (string) config('services.anthropic.endpoint'),
                    version: (string) config('services.anthropic.version'),
                ),
                default => new FakeSummarizer(),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}

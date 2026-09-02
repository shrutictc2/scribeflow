# ScribeFlow

Upload a call/meeting recording (audio or video) and ScribeFlow automatically transcribes it, then has an LLM produce a short summary and a list of action items — no manual note-taking. Built as a small, honest demonstration of a Laravel backend that talks to external AI APIs and processes files in the background, the way a real production feature would.

## Why this exists

I built this specifically to get hands-on with PHP/Laravel — MVC, the Eloquent ORM, queued jobs, scheduled commands, file storage abstractions — on top of skills I already had (LLM API integration, background processing pipelines, testing). Everything here is real, runnable code; nothing is copied from a tutorial.

## How it works

1. You upload an audio/video file through the web UI (`RecordingController@store`).
2. The file is saved through Laravel's `Storage` facade, onto whichever disk `config('recordings.disk')` points at (local disk by default, S3 with one config change — see below). A `Recording` row is created with `status = pending`.
3. A `TranscribeRecording` job is queued. It calls a `TranscriberInterface` implementation to turn the audio into text, saves the transcript, and marks the recording `transcribed`.
4. That job dispatches `SummarizeRecording`, which calls a `SummarizerInterface` implementation (an LLM) to produce a short summary plus a list of action items, and marks the recording `completed`.
5. The detail page polls a small JSON status endpoint every 2.5s while processing is in flight, so the UI updates itself without a page refresh.
6. A scheduled command (`recordings:purge`) deletes old completed/failed recordings and their files, so storage doesn't grow forever.

```
Upload ─▶ [pending] ─▶ TranscribeRecording job ─▶ [transcribing] ─▶ [transcribed]
                                                                          │
                                                                          ▼
                                          SummarizeRecording job ─▶ [summarizing] ─▶ [completed]

                                (any step's failure() hook ─▶ [failed] + error_message)
```

## Why it's pluggable (fake / real drivers)

Both the transcriber and the summarizer are bound to an interface (`TranscriberInterface`, `SummarizerInterface`) in `AppServiceProvider`, chosen at runtime by `config('recordings.transcriber')` / `config('recordings.summarizer')`:

- **`fake` (default)** — deterministic, offline implementations. No API key needed. This is what the automated test suite runs against, and what lets anyone clone this repo and see the full pipeline work in under a minute.
- **`openai`** — `OpenAiWhisperTranscriber` calls OpenAI's audio transcription endpoint.
- **`anthropic`** — `AnthropicSummarizer` calls Claude's Messages API with a system prompt that asks for strict JSON (`{"summary": ..., "action_items": [...]}`), which it then parses.

Swapping providers (AssemblyAI, Deepgram, AWS Transcribe, OpenAI's chat models, etc.) means adding one class that implements the interface and one line in `AppServiceProvider` — nothing else in the app changes.

## Tech it touches

- **Laravel 13 / PHP 8.4** — MVC (`RecordingController` + Blade views), Eloquent models, form request validation, route model binding, PHP 8 enums for state (`RecordingStatus`), typed DTOs for service results.
- **Queued jobs** — `TranscribeRecording` and `SummarizeRecording` (`ShouldQueue`, retries with backoff, `failed()` hooks), chained rather than run inline, using the database queue driver (swap to Redis/SQS in `config/queue.php` with no application code changes).
- **Scheduled tasks** — `php artisan recordings:purge` registered on Laravel's scheduler in `routes/console.php`.
- **File/video handling & S3-ready storage** — uploads go through the `Storage` facade against a configurable disk; switching `RECORDINGS_DISK=s3` (plus AWS credentials and `composer require league/flysystem-aws-s3-v3`) moves storage to S3 with zero code changes.
- **LLM & transcription API integration** — real HTTP calls via Laravel's `Http` facade (Whisper for transcription, Claude for summarization), with structured JSON parsing and error handling.
- **Testing** — PHPUnit feature tests (upload validation, the full processing pipeline with mocked service interfaces) and unit tests (JSON parsing, action-item extraction), all runnable with zero external dependencies via `php artisan test`.

## Setup

This was built in a sandboxed environment without access to Packagist, so dependencies aren't vendored in — that's normal for a Laravel repo (`vendor/` is gitignored on every real Laravel project). On your own machine:

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
php artisan serve
```

Then visit `http://localhost:8000`. Everything runs in `fake` mode out of the box — upload any small audio/video file and watch it move through the pipeline with no API keys.

Jobs need a queue worker running to actually process (the queue connection defaults to `database`, not `sync`):

```bash
php artisan queue:work
```

Run the test suite:

```bash
php artisan test
```

### Switching on the real AI drivers

In `.env`:

```
TRANSCRIBER_DRIVER=openai
OPENAI_API_KEY=sk-...

SUMMARIZER_DRIVER=anthropic
ANTHROPIC_API_KEY=sk-ant-...
```

### Switching to S3 storage

```
RECORDINGS_DISK=s3
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=...
AWS_SECRET_ACCESS_KEY=...
AWS_BUCKET=...
```

and `composer require league/flysystem-aws-s3-v3`.

## Project layout

```
app/
  Console/Commands/PurgeStaleRecordings.php   scheduled cleanup command
  Enums/RecordingStatus.php                   pipeline state machine
  Http/Controllers/RecordingController.php    upload / list / detail / status / delete
  Http/Requests/StoreRecordingRequest.php     upload validation
  Jobs/TranscribeRecording.php                queued: audio -> transcript
  Jobs/SummarizeRecording.php                 queued: transcript -> summary + action items
  Models/Recording.php
  Services/Transcription/                     TranscriberInterface + fake/OpenAI drivers
  Services/Summarization/                     SummarizerInterface + fake/Anthropic drivers
config/recordings.php                         app-specific config (disk, limits, driver selection)
resources/views/recordings/                   upload form, list, detail (with polling)
tests/Feature/                                 upload flow, end-to-end pipeline
tests/Unit/Services/                           JSON parsing, action-item extraction
```

## What I'd add next

Multi-tenant auth (recordings scoped to a user), Laravel Horizon for queue monitoring in production, WebSocket/broadcast-driven updates instead of polling, and a proper background-noise-aware chunking strategy for very long recordings.

<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Storage Disk
    |--------------------------------------------------------------------------
    |
    | Which filesystem disk uploaded recordings are stored on. Defaults to
    | the local disk for zero-config development. Point this at "s3" (see
    | config/filesystems.php) to store recordings in an S3 bucket instead --
    | no application code needs to change, since everything goes through
    | the Storage facade.
    |
    */

    'disk' => env('RECORDINGS_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Upload Limits
    |--------------------------------------------------------------------------
    */

    'max_upload_kb' => (int) env('RECORDINGS_MAX_UPLOAD_KB', 51200), // 50 MB

    'allowed_mime_types' => [
        'audio/mpeg', 'audio/mp3', 'audio/wav', 'audio/x-wav',
        'audio/mp4', 'audio/m4a', 'audio/x-m4a', 'audio/webm',
        'video/mp4', 'video/quicktime', 'video/webm', 'video/x-matroska',
    ],

    /*
    |--------------------------------------------------------------------------
    | Processing Drivers
    |--------------------------------------------------------------------------
    |
    | "fake" drivers require no API keys and produce deterministic output,
    | so the whole upload -> transcribe -> summarize pipeline can be run,
    | demoed and tested offline. Switch to the real drivers by setting an
    | API key and changing these to "openai" / "anthropic".
    |
    */

    'transcriber' => env('TRANSCRIBER_DRIVER', 'fake'), // fake | openai

    'summarizer' => env('SUMMARIZER_DRIVER', 'fake'), // fake | anthropic

    /*
    |--------------------------------------------------------------------------
    | Retention
    |--------------------------------------------------------------------------
    |
    | Finished (completed or failed) recordings older than this are purged
    | by the scheduled `recordings:purge` command -- see routes/console.php.
    |
    */

    'retention_days' => (int) env('RECORDINGS_RETENTION_DAYS', 30),

];

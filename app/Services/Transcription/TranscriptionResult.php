<?php

namespace App\Services\Transcription;

final class TranscriptionResult
{
    public function __construct(
        public readonly string $text,
        public readonly ?string $language = null,
    ) {
    }
}

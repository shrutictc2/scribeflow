<?php

namespace App\Services\Transcription;

use App\Models\Recording;

interface TranscriberInterface
{
    /**
     * Transcribe the audio/video source behind the given recording.
     *
     * @throws \RuntimeException when the upstream provider fails.
     */
    public function transcribe(Recording $recording): TranscriptionResult;
}

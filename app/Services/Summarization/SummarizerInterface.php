<?php

namespace App\Services\Summarization;

interface SummarizerInterface
{
    /**
     * Turn a raw transcript into a short summary plus a list of
     * extracted action items.
     *
     * @throws \RuntimeException when the upstream provider fails.
     */
    public function summarize(string $transcript): SummaryResult;
}

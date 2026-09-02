<?php

namespace App\Services\Summarization;

final class SummaryResult
{
    /**
     * @param  array<int, string>  $actionItems
     */
    public function __construct(
        public readonly string $summary,
        public readonly array $actionItems,
    ) {
    }
}

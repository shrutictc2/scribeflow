<?php

namespace App\Enums;

enum RecordingStatus: string
{
    case Pending = 'pending';
    case Transcribing = 'transcribing';
    case Transcribed = 'transcribed';
    case Summarizing = 'summarizing';
    case Completed = 'completed';
    case Failed = 'failed';

    /**
     * Human-readable label for the UI.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Queued',
            self::Transcribing => 'Transcribing…',
            self::Transcribed => 'Transcribed',
            self::Summarizing => 'Summarizing…',
            self::Completed => 'Completed',
            self::Failed => 'Failed',
        };
    }

    /**
     * Bootstrap-free CSS class hook for the status badge.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Pending, self::Transcribing, self::Summarizing => 'badge badge--working',
            self::Transcribed => 'badge badge--info',
            self::Completed => 'badge badge--success',
            self::Failed => 'badge badge--danger',
        };
    }

    /**
     * Whether the pipeline is still in flight, i.e. the frontend should
     * keep polling for updates.
     */
    public function isInProgress(): bool
    {
        return in_array($this, [self::Pending, self::Transcribing, self::Transcribed, self::Summarizing], true);
    }

    /**
     * Whether the record is finished (either way) and eligible for purging.
     */
    public function isFinished(): bool
    {
        return in_array($this, [self::Completed, self::Failed], true);
    }
}

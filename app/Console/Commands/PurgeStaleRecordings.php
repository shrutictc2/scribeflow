<?php

namespace App\Console\Commands;

use App\Models\Recording;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PurgeStaleRecordings extends Command
{
    protected $signature = 'recordings:purge {--days= : Override recordings.retention_days} {--dry-run : List what would be deleted without deleting it}';

    protected $description = 'Delete completed/failed recordings (and their stored files) older than the retention window';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?? config('recordings.retention_days'));
        $cutoff = now()->subDays($days);

        $stale = Recording::query()
            ->whereIn('status', ['completed', 'failed'])
            ->where('updated_at', '<', $cutoff)
            ->get();

        if ($stale->isEmpty()) {
            $this->info("No recordings older than {$days} day(s) to purge.");

            return self::SUCCESS;
        }

        foreach ($stale as $recording) {
            $this->line("- #{$recording->id} \"{$recording->title}\" (updated {$recording->updated_at->diffForHumans()})");

            if ($this->option('dry-run')) {
                continue;
            }

            Storage::disk($recording->disk)->delete($recording->path);
            $recording->delete();
        }

        if ($this->option('dry-run')) {
            $this->info(count($stale).' recording(s) would be purged (dry run, nothing deleted).');
        } else {
            $this->info(count($stale).' recording(s) purged.');
        }

        return self::SUCCESS;
    }
}

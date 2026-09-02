<?php

namespace App\Models;

use App\Enums\RecordingStatus;
use Database\Factories\RecordingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'title', 'original_filename', 'disk', 'path', 'mime_type', 'size_bytes',
    'status', 'transcript', 'summary', 'action_items', 'error_message',
])]
class Recording extends Model
{
    /** @use HasFactory<RecordingFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => RecordingStatus::class,
            'action_items' => 'array',
            'size_bytes' => 'integer',
        ];
    }

    /**
     * The filesystem disk this recording's source file lives on.
     */
    public function filesystem(): \Illuminate\Contracts\Filesystem\Filesystem
    {
        return Storage::disk($this->disk);
    }

    /**
     * Absolute (or driver-appropriate) path used to read the raw file back
     * out for transcription.
     */
    public function fileContents(): string
    {
        return $this->filesystem()->get($this->path);
    }

    public function markStatus(RecordingStatus $status, array $extra = []): void
    {
        $this->forceFill(array_merge(['status' => $status], $extra))->save();
    }

    public function markFailed(string $message): void
    {
        $this->markStatus(RecordingStatus::Failed, ['error_message' => $message]);
    }
}

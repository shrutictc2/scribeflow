<?php

namespace Tests\Feature;

use App\Enums\RecordingStatus;
use App\Jobs\TranscribeRecording;
use App\Models\Recording;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RecordingUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_page_loads(): void
    {
        $this->get(route('recordings.index'))->assertOk();
    }

    public function test_a_valid_recording_can_be_uploaded(): void
    {
        Storage::fake('local');
        Queue::fake();

        $file = UploadedFile::fake()->create('kickoff-call.mp3', 500, 'audio/mpeg');

        $response = $this->post(route('recordings.store'), [
            'title' => 'Kickoff call',
            'file' => $file,
        ]);

        $this->assertDatabaseHas('recordings', [
            'title' => 'Kickoff call',
            'original_filename' => 'kickoff-call.mp3',
            'status' => RecordingStatus::Pending->value,
        ]);

        $recording = Recording::firstOrFail();
        Storage::disk('local')->assertExists($recording->path);
        Queue::assertPushed(TranscribeRecording::class);

        $response->assertRedirect(route('recordings.show', $recording));
    }

    public function test_title_defaults_to_the_original_filename_when_left_blank(): void
    {
        Storage::fake('local');
        Queue::fake();

        $file = UploadedFile::fake()->create('team-standup.wav', 200, 'audio/wav');

        $this->post(route('recordings.store'), ['file' => $file]);

        $this->assertDatabaseHas('recordings', ['title' => 'team-standup.wav']);
    }

    public function test_upload_requires_a_file(): void
    {
        $this->post(route('recordings.store'), ['title' => 'No file attached'])
            ->assertSessionHasErrors('file');

        $this->assertDatabaseCount('recordings', 0);
    }

    public function test_unsupported_file_types_are_rejected(): void
    {
        $file = UploadedFile::fake()->create('notes.pdf', 100, 'application/pdf');

        $this->post(route('recordings.store'), ['file' => $file])
            ->assertSessionHasErrors('file');

        $this->assertDatabaseCount('recordings', 0);
    }

    public function test_oversized_files_are_rejected(): void
    {
        config(['recordings.max_upload_kb' => 100]);

        $file = UploadedFile::fake()->create('huge.mp4', 500, 'video/mp4');

        $this->post(route('recordings.store'), ['file' => $file])
            ->assertSessionHasErrors('file');
    }
}

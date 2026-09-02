<?php

namespace Database\Factories;

use App\Enums\RecordingStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Recording>
 */
class RecordingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $filename = fake()->slug(3).'.mp3';

        return [
            'title' => fake()->sentence(4),
            'original_filename' => $filename,
            'disk' => 'local',
            'path' => 'recordings/'.fake()->uuid().'.mp3',
            'mime_type' => 'audio/mpeg',
            'size_bytes' => fake()->numberBetween(100_000, 20_000_000),
            'status' => RecordingStatus::Pending,
        ];
    }

    public function transcribed(): static
    {
        return $this->state(fn () => [
            'status' => RecordingStatus::Transcribed,
            'transcript' => fake()->paragraphs(3, true),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => RecordingStatus::Completed,
            'transcript' => fake()->paragraphs(3, true),
            'summary' => fake()->paragraph(),
            'action_items' => [fake()->sentence(), fake()->sentence()],
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn () => [
            'status' => RecordingStatus::Failed,
            'error_message' => 'Simulated failure for testing.',
        ]);
    }
}

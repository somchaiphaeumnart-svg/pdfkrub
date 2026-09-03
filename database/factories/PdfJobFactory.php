<?php

namespace Database\Factories;

use App\Models\PdfJob;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PdfJobFactory extends Factory
{
    protected $model = PdfJob::class;

    public function definition(): array
    {
        return [
            'user_id' => null,
            'session_id' => $this->faker->uuid(),
            'input_file_ids' => [],
            'output_file_id' => null,
            'tool_name' => $this->faker->randomElement([
                'merge-pdf', 'split-pdf', 'compress-pdf', 'pdf-to-word',
                'word-to-pdf', 'rotate-pdf', 'ocr-pdf',
            ]),
            'tool_config' => [],
            'status' => PdfJob::STATUS_QUEUED,
            'progress' => 0,
            'error_message' => null,
            'queue_name' => 'default',
            'queue_job_id' => null,
            'processing_time_ms' => null,
            'started_at' => null,
            'completed_at' => null,
        ];
    }

    public function queued(): static
    {
        return $this->state(['status' => PdfJob::STATUS_QUEUED]);
    }

    public function processing(): static
    {
        return $this->state([
            'status' => PdfJob::STATUS_PROCESSING,
            'started_at' => now(),
            'progress' => 50,
        ]);
    }

    public function done(): static
    {
        return $this->state([
            'status' => PdfJob::STATUS_DONE,
            'progress' => 100,
            'started_at' => now()->subSeconds(5),
            'completed_at' => now(),
            'processing_time_ms' => 5000,
        ]);
    }

    public function failed(): static
    {
        return $this->state([
            'status' => PdfJob::STATUS_FAILED,
            'error_message' => 'Processing failed',
            'completed_at' => now(),
        ]);
    }

    public function forUser(User $user): static
    {
        return $this->state(['user_id' => $user->id, 'session_id' => null]);
    }
}

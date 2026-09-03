<?php

namespace Database\Factories;

use App\Models\UploadedFile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class UploadedFileFactory extends Factory
{
    protected $model = UploadedFile::class;

    public function definition(): array
    {
        $ext = $this->faker->randomElement(['pdf', 'docx', 'txt', 'jpg']);
        $name = $this->faker->slug(2).'.'.$ext;

        return [
            'user_id' => null,
            'session_id' => $this->faker->uuid(),
            'original_name' => $name,
            'storage_key' => 'uploads/test/'.$this->faker->uuid().'.'.$ext,
            'storage_disk' => 'local',
            'file_size' => $this->faker->numberBetween(10_000, 5_000_000),
            'mime_type' => match ($ext) {
                'pdf' => 'application/pdf',
                'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'txt' => 'text/plain',
                'jpg' => 'image/jpeg',
                default => 'application/octet-stream',
            },
            'file_hash' => $this->faker->sha256(),
            'page_count' => $this->faker->optional()->numberBetween(1, 100),
            'metadata' => null,
            'is_encrypted' => false,
            'is_scanned' => false,
            'is_clean' => true,
            'expires_at' => now()->addHours(48),
        ];
    }

    public function expired(): static
    {
        return $this->state(['expires_at' => now()->subHour()]);
    }

    public function forUser(User $user): static
    {
        return $this->state(['user_id' => $user->id, 'session_id' => null]);
    }

    public function pdf(): static
    {
        return $this->state([
            'original_name' => $this->faker->slug(2).'.pdf',
            'storage_key' => 'uploads/test/'.$this->faker->uuid().'.pdf',
            'mime_type' => 'application/pdf',
        ]);
    }
}

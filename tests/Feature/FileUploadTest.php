<?php

namespace Tests\Feature;

use App\Jobs\ProcessPdfJob;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FileUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Queue::fake();
        $this->artisan('db:seed', ['--class' => 'PlanSeeder']);
    }

    // ─────────────────────────────────────────────────────────────
    // Upload
    // ─────────────────────────────────────────────────────────────

    public function test_guest_can_upload_pdf_and_get_job_id(): void
    {
        $file = UploadedFile::fake()->create('test.pdf', 500, 'application/pdf');

        $response = $this->postJson('/files/upload', [
            'files' => [$file],
            'tool' => 'merge-pdf',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['job_id', 'status', 'status_url']);

        Queue::assertPushed(ProcessPdfJob::class);
    }

    public function test_upload_validates_file_is_required(): void
    {
        $response = $this->postJson('/files/upload', [
            'tool' => 'merge-pdf',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['files']);
    }

    public function test_upload_validates_tool_is_required(): void
    {
        $file = UploadedFile::fake()->create('test.pdf', 100, 'application/pdf');

        $response = $this->postJson('/files/upload', [
            'files' => [$file],
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['tool']);
    }

    public function test_upload_stores_file_in_local_disk(): void
    {
        $file = UploadedFile::fake()->create('document.pdf', 200, 'application/pdf');

        $this->postJson('/files/upload', [
            'files' => [$file],
            'tool' => 'compress-pdf',
        ])->assertStatus(201);

        // Verify a file was stored
        $this->assertNotEmpty(Storage::disk('local')->allFiles('uploads'));
    }

    public function test_authenticated_user_upload_links_to_account(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('test.pdf', 100, 'application/pdf');

        $this->actingAs($user)->postJson('/files/upload', [
            'files' => [$file],
            'tool' => 'pdf-to-word',
        ])->assertStatus(201);

        $this->assertDatabaseHas('uploaded_files', [
            'user_id' => $user->id,
            'original_name' => 'test.pdf',
        ]);
    }

    public function test_upload_rejects_oversized_file(): void
    {
        $freePlan = Plan::where('name', 'free')->first();
        // Default free plan allows 10 MB
        $bigFile = UploadedFile::fake()->create('huge.pdf', ($freePlan->max_file_size_mb + 1) * 1024, 'application/pdf');

        $response = $this->postJson('/files/upload', [
            'files' => [$bigFile],
            'tool' => 'compress-pdf',
        ]);

        $response->assertStatus(422);
    }

    // ─────────────────────────────────────────────────────────────
    // Job Status
    // ─────────────────────────────────────────────────────────────

    public function test_job_status_returns_correct_structure(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('test.pdf', 100, 'application/pdf');

        $uploadResponse = $this->actingAs($user)->postJson('/files/upload', [
            'files' => [$file],
            'tool' => 'rotate-pdf',
        ]);

        $jobId = $uploadResponse->json('job_id');
        $statusUrl = $uploadResponse->json('status_url');

        $this->actingAs($user)
            ->getJson($statusUrl)
            ->assertOk()
            ->assertJsonStructure(['id', 'status', 'progress', 'tool_name']);
    }

    public function test_guest_cannot_view_another_sessions_job(): void
    {
        // Create a job under session A
        $file = UploadedFile::fake()->create('test.pdf', 100, 'application/pdf');
        $upload = $this->withSession(['_token' => 'abc'])->postJson('/files/upload', [
            'files' => [$file],
            'tool' => 'split-pdf',
        ]);

        $jobId = $upload->json('job_id');

        // Try to access it from a different session
        $this->withSession(['_token' => 'xyz'])
            ->getJson("/api/jobs/{$jobId}")
            ->assertStatus(403);
    }

    // ─────────────────────────────────────────────────────────────
    // Download
    // ─────────────────────────────────────────────────────────────

    public function test_unsigned_download_url_is_rejected(): void
    {
        $model = \App\Models\UploadedFile::factory()->create(['user_id' => null, 'session_id' => 'test']);

        $this->get("/files/download/{$model->id}")
            ->assertStatus(403); // Middleware: invalid signature
    }
}

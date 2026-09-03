<?php

namespace Tests\Feature;

use App\Models\PdfJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PdfJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        $this->artisan('db:seed', ['--class' => 'PlanSeeder']);
    }

    public function test_pdf_job_is_created_on_upload(): void
    {
        $user = User::factory()->create();
        Storage::fake('local');

        $file = UploadedFile::fake()->create('test.pdf', 100, 'application/pdf');

        $this->actingAs($user)->postJson('/files/upload', [
            'files' => [$file],
            'tool' => 'merge-pdf',
        ])->assertStatus(201);

        $this->assertDatabaseHas('pdf_jobs', [
            'user_id' => $user->id,
            'tool_name' => 'merge-pdf',
            'status' => PdfJob::STATUS_QUEUED,
        ]);
    }

    public function test_pdf_job_marks_as_processing(): void
    {
        $job = PdfJob::factory()->create(['status' => PdfJob::STATUS_QUEUED]);

        $job->markAsProcessing();

        $this->assertEquals(PdfJob::STATUS_PROCESSING, $job->fresh()->status);
        $this->assertNotNull($job->fresh()->started_at);
    }

    public function test_pdf_job_marks_as_complete(): void
    {
        $job = PdfJob::factory()->create([
            'status' => PdfJob::STATUS_PROCESSING,
            'started_at' => now()->subSeconds(3),
        ]);
        $outputFile = \App\Models\UploadedFile::factory()->create();

        $job->markAsComplete($outputFile->id);

        $fresh = $job->fresh();
        $this->assertEquals(PdfJob::STATUS_DONE, $fresh->status);
        $this->assertEquals($outputFile->id, $fresh->output_file_id);
        $this->assertEquals(100, $fresh->progress);
        $this->assertNotNull($fresh->processing_time_ms);
    }

    public function test_pdf_job_marks_as_failed(): void
    {
        $job = PdfJob::factory()->create(['status' => PdfJob::STATUS_PROCESSING]);

        $job->markAsFailed('LibreOffice not found');

        $fresh = $job->fresh();
        $this->assertEquals(PdfJob::STATUS_FAILED, $fresh->status);
        $this->assertEquals('LibreOffice not found', $fresh->error_message);
    }

    public function test_pdf_job_scope_pending_filters_correctly(): void
    {
        PdfJob::factory()->create(['status' => PdfJob::STATUS_QUEUED]);
        PdfJob::factory()->create(['status' => PdfJob::STATUS_PROCESSING]);
        PdfJob::factory()->create(['status' => PdfJob::STATUS_DONE]);
        PdfJob::factory()->create(['status' => PdfJob::STATUS_FAILED]);

        $pending = PdfJob::pending()->count();

        $this->assertEquals(2, $pending);
    }

    public function test_is_complete_is_false_for_queued_job(): void
    {
        $job = PdfJob::factory()->create(['status' => PdfJob::STATUS_QUEUED]);
        $this->assertFalse($job->isComplete());
    }

    public function test_is_failed_is_true_for_failed_status(): void
    {
        $job = PdfJob::factory()->create(['status' => PdfJob::STATUS_FAILED]);
        $this->assertTrue($job->isFailed());
    }

    public function test_dashboard_shows_job_counts(): void
    {
        $user = User::factory()->create();
        PdfJob::factory()->count(3)->create(['user_id' => $user->id, 'status' => PdfJob::STATUS_DONE]);
        PdfJob::factory()->create(['user_id' => $user->id, 'status' => PdfJob::STATUS_FAILED]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
    }
}

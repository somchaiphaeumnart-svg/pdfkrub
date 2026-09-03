<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pdf_jobs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('session_id')->nullable()->index();
            $table->json('input_file_ids');   // Array of uploaded_files ULIDs
            $table->string('output_file_id')->nullable(); // ULID of result file
            $table->string('tool_name', 100);  // 'pdf-to-word', 'merge-pdf', 'ocr', etc.
            $table->json('tool_config')->nullable(); // {"quality": "high", "lang": "tha+eng"}
            $table->string('status', 20)->default('queued'); // queued|processing|done|failed|cancelled
            $table->unsignedTinyInteger('progress')->default(0); // 0-100
            $table->text('error_message')->nullable();
            $table->string('queue_name', 50)->default('default'); // default|ocr|heavy
            $table->string('queue_job_id')->nullable()->index(); // Laravel job ID
            $table->unsignedInteger('processing_time_ms')->nullable(); // milliseconds
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pdf_jobs');
    }
};

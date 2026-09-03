<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uploaded_files', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('session_id')->nullable()->index(); // สำหรับ Guest users
            $table->string('original_name', 500);
            $table->string('storage_key')->unique(); // path in MinIO/R2
            $table->string('storage_disk', 50)->default('local'); // local|s3|r2
            $table->unsignedBigInteger('file_size'); // bytes
            $table->string('mime_type', 100);
            $table->string('file_hash', 64)->nullable()->index(); // SHA-256
            $table->unsignedInteger('page_count')->nullable();
            $table->json('metadata')->nullable(); // {"width": 595, "height": 842, "has_text": true}
            $table->boolean('is_encrypted')->default(false);
            $table->string('encryption_key_id')->nullable();
            $table->boolean('is_scanned')->default(false); // Antivirus scanned
            $table->boolean('is_clean')->default(true);   // No virus detected
            $table->timestamp('expires_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['expires_at', 'deleted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uploaded_files');
    }
};

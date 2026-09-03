<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usage_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('session_id')->nullable()->index();
            $table->string('tool_name', 100);
            $table->unsignedBigInteger('file_size')->nullable(); // bytes
            $table->unsignedInteger('processing_time_ms')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('country_code', 2)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'tool_name', 'created_at']);
        });

        // Daily aggregated usage for rate limiting
        Schema::create('daily_usage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('tool_name', 100);
            $table->unsignedInteger('count')->default(1);

            $table->unique(['user_id', 'date', 'tool_name']);
            $table->index(['user_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_usage');
        Schema::dropIfExists('usage_logs');
    }
};

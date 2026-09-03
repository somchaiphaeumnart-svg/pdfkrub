<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique(); // 'free' | 'pro' | 'business'
            $table->string('display_name', 100);
            $table->string('display_name_th', 100)->nullable();
            $table->decimal('price_monthly', 10, 2)->default(0);
            $table->decimal('price_yearly', 10, 2)->default(0);
            $table->string('currency', 3)->default('THB');
            $table->json('features')->nullable(); // {"max_file_mb": 200, "ocr": true, ...}
            $table->integer('max_file_size_mb')->default(10);
            $table->integer('daily_conversions')->default(3); // -1 = unlimited
            $table->integer('file_retention_hours')->default(2);
            $table->boolean('has_ocr')->default(false);
            $table->boolean('has_esign')->default(false);
            $table->boolean('has_watermark')->default(true); // watermark on output
            $table->boolean('has_api_access')->default(false);
            $table->integer('max_team_members')->default(1);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('template_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name_en', 200);
            $table->string('name_th', 200);
            $table->string('slug', 200)->unique();
            $table->string('icon', 100)->nullable(); // Heroicon name
            $table->foreignId('parent_id')->nullable()->constrained('template_categories')->nullOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('template_categories')->nullOnDelete();
            $table->string('title_en', 500);
            $table->string('title_th', 500)->nullable();
            $table->text('description_en')->nullable();
            $table->text('description_th')->nullable();
            $table->string('slug', 500)->unique();
            $table->json('tags')->nullable(); // ["contract", "legal", "thai"]
            $table->string('thumbnail_url')->nullable();
            $table->string('file_url');            // Storage path
            $table->string('file_format', 10)->default('pdf'); // pdf|docx
            $table->unsignedBigInteger('file_size')->nullable();
            $table->boolean('is_premium')->default(false);
            $table->boolean('is_thai')->default(false); // Thai government forms
            $table->unsignedInteger('download_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['category_id', 'is_active']);
            $table->index('is_thai');
        });

        Schema::create('template_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('templates')->cascadeOnDelete();
            $table->string('field_name', 200);
            $table->string('field_label_en', 200)->nullable();
            $table->string('field_label_th', 200)->nullable();
            $table->string('field_type', 50)->default('text'); // text|date|number|checkbox|signature|select
            $table->boolean('is_required')->default(false);
            $table->string('placeholder')->nullable();
            $table->string('default_value')->nullable();
            $table->json('options')->nullable(); // สำหรับ select fields
            $table->unsignedSmallInteger('sort_order')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('template_fields');
        Schema::dropIfExists('templates');
        Schema::dropIfExists('template_categories');
    }
};

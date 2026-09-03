<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('templates');
    }

    public function down(): void
    {
        // Reversed in create_template_categories_table
    }
};

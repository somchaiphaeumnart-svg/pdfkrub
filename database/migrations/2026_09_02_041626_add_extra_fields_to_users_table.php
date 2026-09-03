<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar_url')->nullable()->after('email');
            $table->string('provider', 50)->nullable()->after('avatar_url'); // 'email'|'google'|'microsoft'
            $table->string('provider_id')->nullable()->after('provider');
            $table->string('locale', 10)->default('th')->after('provider_id');
            $table->unsignedBigInteger('storage_used')->default(0)->after('locale'); // bytes
            $table->foreignId('plan_id')->nullable()->after('storage_used');
            $table->softDeletes();

            $table->index(['provider', 'provider_id']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['provider', 'provider_id']);
            $table->dropColumn(['avatar_url', 'provider', 'provider_id', 'locale', 'storage_used', 'plan_id', 'deleted_at']);
        });
    }
};

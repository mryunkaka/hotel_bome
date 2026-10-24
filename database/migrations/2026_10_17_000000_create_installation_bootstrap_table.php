<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_platform_admin')->default(false)->index();
        });

        Schema::create('installation_bootstrap', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->foreignId('admin_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('completed_at')->nullable();
        });

        DB::table('installation_bootstrap')->insert(['id' => 1]);
    }

    public function down(): void
    {
        Schema::dropIfExists('installation_bootstrap');

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['is_platform_admin']);
            $table->dropColumn('is_platform_admin');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_orders', function (Blueprint $table): void {
            $table->unsignedBigInteger('asset_id')->nullable()->after('room_id');
            $table->foreign(['property_id', 'asset_id'])->references(['property_id', 'id'])->on('engineering_assets')->restrictOnDelete();
            $table->index(['property_id', 'asset_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('work_orders', function (Blueprint $table): void {
            $table->dropForeign(['property_id', 'asset_id']);
            $table->dropIndex(['property_id', 'asset_id', 'status']);
            $table->dropColumn('asset_id');
        });
    }
};

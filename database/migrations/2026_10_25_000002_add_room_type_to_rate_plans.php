<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rate_plans', function (Blueprint $table) {
            $table->unsignedBigInteger('room_type_id')->nullable()->after('property_id');
            $table->foreign(['property_id', 'room_type_id'])
                ->references(['property_id', 'id'])
                ->on('room_types')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('rate_plans', function (Blueprint $table) {
            $table->dropForeign(['property_id', 'room_type_id']);
            $table->dropColumn('room_type_id');
        });
    }
};

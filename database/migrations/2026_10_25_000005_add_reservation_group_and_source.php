<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservation_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->restrictOnDelete();
            $table->string('name', 200);
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->index('property_id');
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->foreignId('group_id')->nullable()->after('is_walk_in')
                ->constrained('reservation_groups')->nullOnDelete();
            $table->string('source', 30)->nullable()->after('group_id'); // walk_in, phone, online, etc.
            $table->text('notes')->nullable()->after('source');
        });

        // Add changes column to reservation_events for room/guest changes
        Schema::table('reservation_events', function (Blueprint $table) {
            $table->json('changes')->nullable()->after('reason');
        });
    }

    public function down(): void
    {
        Schema::table('reservation_events', function (Blueprint $table) {
            $table->dropColumn('changes');
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('group_id');
            $table->dropColumn(['source', 'notes']);
        });

        Schema::dropIfExists('reservation_groups');
    }
};

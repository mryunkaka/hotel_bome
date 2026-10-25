<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rate_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->restrictOnDelete();
            $table->foreignId('rate_plan_id')->constrained()->restrictOnDelete();
            $table->string('rate_plan_name', 100);
            $table->string('rate_plan_code', 20);
            $table->unsignedInteger('rate_per_night'); // IDR
            $table->unsignedSmallInteger('nights');
            $table->unsignedInteger('subtotal'); // IDR
            $table->decimal('tax_percentage', 5, 2)->nullable();
            $table->unsignedInteger('tax_amount'); // IDR
            $table->unsignedInteger('total'); // IDR
            $table->date('check_in');
            $table->date('check_out');
            $table->json('nightly_breakdown');
            $table->timestamp('captured_at')->useCurrent();

            $table->index('property_id');
        });

        // Immutable: reject UPDATE and DELETE
        DB::unprepared("
            CREATE TRIGGER rate_snapshots_no_update
            BEFORE UPDATE ON rate_snapshots
            BEGIN
                SELECT RAISE(ABORT, 'rate_snapshots rows are immutable');
            END;
        ");

        DB::unprepared("
            CREATE TRIGGER rate_snapshots_no_delete
            BEFORE DELETE ON rate_snapshots
            BEGIN
                SELECT RAISE(ABORT, 'rate_snapshots rows are immutable');
            END;
        ");
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS rate_snapshots_no_delete');
        DB::unprepared('DROP TRIGGER IF EXISTS rate_snapshots_no_update');
        Schema::dropIfExists('rate_snapshots');
    }
};

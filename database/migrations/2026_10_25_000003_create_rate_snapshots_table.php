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
        foreach (['UPDATE', 'DELETE'] as $operation) {
            $name = 'rate_snapshots_no_'.strtolower($operation);
            $sql = match (DB::getDriverName()) {
                'sqlite' => "CREATE TRIGGER {$name} BEFORE {$operation} ON rate_snapshots BEGIN SELECT RAISE(ABORT, 'rate_snapshots rows are immutable'); END",
                'mysql' => "CREATE TRIGGER {$name} BEFORE {$operation} ON rate_snapshots FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'rate_snapshots rows are immutable'",
                default => throw new RuntimeException('Rate snapshot immutability triggers are unsupported for this database driver.'),
            };
            DB::unprepared($sql);
        }
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS rate_snapshots_no_delete');
        DB::unprepared('DROP TRIGGER IF EXISTS rate_snapshots_no_update');
        Schema::dropIfExists('rate_snapshots');
    }
};

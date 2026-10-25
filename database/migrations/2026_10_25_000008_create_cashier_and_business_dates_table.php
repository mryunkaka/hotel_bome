<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_dates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->restrictOnDelete();
            $table->date('business_date');
            $table->string('status', 30)->default('open'); // open, closed
            $table->timestamp('opened_at')->useCurrent();
            $table->timestamp('closed_at')->nullable();
            $table->unsignedBigInteger('closed_by')->nullable();
            $table->timestamps();

            $table->unique(['property_id', 'business_date']);
            $table->index(['property_id', 'status']);
        });

        Schema::create('cashier_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('cashier_id');
            $table->date('business_date');
            $table->string('status', 30)->default('open'); // open, closed
            $table->bigInteger('opening_cash')->default(0);
            $table->bigInteger('closing_cash_counted')->nullable();
            $table->bigInteger('closing_cash_expected')->nullable();
            $table->bigInteger('variance')->nullable();
            $table->timestamp('opened_at')->useCurrent();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['property_id', 'status']);
            $table->index(['property_id', 'cashier_id']);
        });

        Schema::create('day_closing_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->restrictOnDelete();
            $table->date('business_date');
            $table->bigInteger('total_charges');
            $table->bigInteger('total_payments');
            $table->integer('active_stays_count');
            $table->integer('active_reservations_count');
            $table->json('snapshot_data');
            $table->unsignedBigInteger('closed_by');
            $table->timestamp('closed_at')->useCurrent();

            $table->index(['property_id', 'business_date']);
        });

        Schema::create('business_date_reopens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->restrictOnDelete();
            $table->date('business_date');
            $table->string('reason', 500);
            $table->unsignedBigInteger('reopened_by');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['property_id', 'business_date']);
        });

        // Triggers to enforce immutability on snapshots and reopen records
        foreach (['day_closing_snapshots', 'business_date_reopens'] as $tableName) {
            foreach (['UPDATE', 'DELETE'] as $operation) {
                $name = $tableName.'_no_'.strtolower($operation);
                $message = "{$tableName} is immutable: {$operation} not allowed";
                $sql = match (DB::getDriverName()) {
                    'sqlite' => "CREATE TRIGGER {$name} BEFORE {$operation} ON {$tableName} BEGIN SELECT RAISE(ABORT, '{$message}'); END",
                    'mysql' => "CREATE TRIGGER {$name} BEFORE {$operation} ON {$tableName} FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = '{$message}'",
                    default => throw new RuntimeException('Cashier immutability triggers are unsupported for this database driver.'),
                };
                DB::unprepared($sql);
            }
        }
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS day_closing_snapshots_no_update');
        DB::unprepared('DROP TRIGGER IF EXISTS day_closing_snapshots_no_delete');
        DB::unprepared('DROP TRIGGER IF EXISTS business_date_reopens_no_update');
        DB::unprepared('DROP TRIGGER IF EXISTS business_date_reopens_no_delete');

        Schema::dropIfExists('business_date_reopens');
        Schema::dropIfExists('day_closing_snapshots');
        Schema::dropIfExists('cashier_shifts');
        Schema::dropIfExists('business_dates');
    }
};

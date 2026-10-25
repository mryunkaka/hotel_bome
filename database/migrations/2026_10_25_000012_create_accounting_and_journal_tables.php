<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chart_of_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->restrictOnDelete();
            $table->string('code', 50);
            $table->string('name', 200);
            $table->string('type', 50); // asset, liability, equity, revenue, expense
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['property_id', 'code']);
            $table->index(['property_id', 'type']);
        });

        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->restrictOnDelete();
            $table->string('entry_number', 100);
            $table->date('entry_date');
            $table->string('description', 500);
            $table->string('source_type', 50)->default('manual'); // manual, folio, night_audit
            $table->unsignedBigInteger('source_id')->nullable();
            $table->boolean('is_balanced')->default(true);
            $table->bigInteger('total_amount'); // sum of debits (which equals sum of credits)
            $table->foreignId('reversal_of_id')->nullable()->constrained('journal_entries')->restrictOnDelete();
            $table->string('reason', 500)->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->unique(['property_id', 'entry_number']);
            $table->index(['property_id', 'entry_date']);
        });

        Schema::create('journal_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entry_id')->constrained('journal_entries')->restrictOnDelete();
            $table->foreignId('account_id')->constrained('chart_of_accounts')->restrictOnDelete();
            $table->bigInteger('debit')->default(0);
            $table->bigInteger('credit')->default(0);
            $table->string('description', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['entry_id', 'account_id']);
        });

        // Triggers to enforce immutability on journal_entries and journal_lines
        DB::unprepared(<<<'SQL'
            CREATE TRIGGER journal_entries_no_update
            BEFORE UPDATE ON journal_entries
            BEGIN
                SELECT RAISE(ABORT, 'journal_entries is immutable: UPDATE not allowed');
            END;

            CREATE TRIGGER journal_entries_no_delete
            BEFORE DELETE ON journal_entries
            BEGIN
                SELECT RAISE(ABORT, 'journal_entries is immutable: DELETE not allowed');
            END;

            CREATE TRIGGER journal_lines_no_update
            BEFORE UPDATE ON journal_lines
            BEGIN
                SELECT RAISE(ABORT, 'journal_lines is immutable: UPDATE not allowed');
            END;

            CREATE TRIGGER journal_lines_no_delete
            BEFORE DELETE ON journal_lines
            BEGIN
                SELECT RAISE(ABORT, 'journal_lines is immutable: DELETE not allowed');
            END;
        SQL);
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS journal_entries_no_update');
        DB::unprepared('DROP TRIGGER IF EXISTS journal_entries_no_delete');
        DB::unprepared('DROP TRIGGER IF EXISTS journal_lines_no_update');
        DB::unprepared('DROP TRIGGER IF EXISTS journal_lines_no_delete');

        Schema::dropIfExists('journal_lines');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('chart_of_accounts');
    }
};

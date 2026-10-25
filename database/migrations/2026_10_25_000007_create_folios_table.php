<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('folios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->restrictOnDelete();
            $table->foreignId('reservation_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('guest_id')->nullable();
            $table->string('status', 30)->default('open'); // open, closed, void
            $table->timestamps();

            $table->index('property_id');
            $table->index('reservation_id');
        });

        Schema::create('folio_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('folio_id')->constrained('folios')->restrictOnDelete();
            $table->foreignId('property_id')->constrained()->restrictOnDelete();
            $table->bigInteger('amount'); // in IDR (can be negative for reversal)
            $table->string('description', 255);
            $table->string('item_type', 50)->default('room'); // room, service, food_beverage, tax, other
            $table->foreignId('reversal_of_id')->nullable()->constrained('folio_charges')->restrictOnDelete();
            $table->string('reason', 500)->nullable();
            $table->unsignedBigInteger('posted_by');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['folio_id', 'property_id']);
        });

        Schema::create('folio_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('folio_id')->constrained('folios')->restrictOnDelete();
            $table->foreignId('property_id')->constrained()->restrictOnDelete();
            $table->bigInteger('amount'); // in IDR (can be negative for refund)
            $table->string('tender_type', 50); // cash, card, transfer, deposit
            $table->string('idempotency_key', 100)->nullable();
            $table->foreignId('refund_of_id')->nullable()->constrained('folio_payments')->restrictOnDelete();
            $table->string('reason', 500)->nullable();
            $table->unsignedBigInteger('posted_by');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['folio_id', 'property_id']);
            $table->unique(['property_id', 'idempotency_key']);
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->restrictOnDelete();
            $table->foreignId('folio_id')->constrained('folios')->restrictOnDelete();
            $table->string('invoice_number', 100);
            $table->bigInteger('total_charges');
            $table->bigInteger('total_payments');
            $table->bigInteger('balance_due');
            $table->json('line_items_snapshot');
            $table->unsignedBigInteger('issued_by');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['property_id', 'folio_id']);
            $table->unique(['property_id', 'invoice_number']);
        });

        // Immutable triggers for charges, payments, and invoices
        DB::unprepared(<<<'SQL'
            CREATE TRIGGER folio_charges_no_update
            BEFORE UPDATE ON folio_charges
            BEGIN
                SELECT RAISE(ABORT, 'folio_charges is immutable: UPDATE not allowed');
            END;

            CREATE TRIGGER folio_charges_no_delete
            BEFORE DELETE ON folio_charges
            BEGIN
                SELECT RAISE(ABORT, 'folio_charges is immutable: DELETE not allowed');
            END;

            CREATE TRIGGER folio_payments_no_update
            BEFORE UPDATE ON folio_payments
            BEGIN
                SELECT RAISE(ABORT, 'folio_payments is immutable: UPDATE not allowed');
            END;

            CREATE TRIGGER folio_payments_no_delete
            BEFORE DELETE ON folio_payments
            BEGIN
                SELECT RAISE(ABORT, 'folio_payments is immutable: DELETE not allowed');
            END;

            CREATE TRIGGER invoices_no_update
            BEFORE UPDATE ON invoices
            BEGIN
                SELECT RAISE(ABORT, 'invoices is immutable: UPDATE not allowed');
            END;

            CREATE TRIGGER invoices_no_delete
            BEFORE DELETE ON invoices
            BEGIN
                SELECT RAISE(ABORT, 'invoices is immutable: DELETE not allowed');
            END;
        SQL);
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS folio_charges_no_update');
        DB::unprepared('DROP TRIGGER IF EXISTS folio_charges_no_delete');
        DB::unprepared('DROP TRIGGER IF EXISTS folio_payments_no_update');
        DB::unprepared('DROP TRIGGER IF EXISTS folio_payments_no_delete');
        DB::unprepared('DROP TRIGGER IF EXISTS invoices_no_update');
        DB::unprepared('DROP TRIGGER IF EXISTS invoices_no_delete');

        Schema::dropIfExists('invoices');
        Schema::dropIfExists('folio_payments');
        Schema::dropIfExists('folio_charges');
        Schema::dropIfExists('folios');
    }
};

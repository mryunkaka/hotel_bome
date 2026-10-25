<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_outlets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->restrictOnDelete();
            $table->string('code', 50);
            $table->string('name', 200);
            $table->string('type', 50)->default('restaurant'); // restaurant, cafe, bar, room_service
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['property_id', 'code']);
            $table->index(['property_id', 'type']);
        });

        Schema::create('pos_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->restrictOnDelete();
            $table->foreignId('outlet_id')->constrained('pos_outlets')->restrictOnDelete();
            $table->string('order_number', 100);
            $table->string('table_number', 50)->nullable();
            $table->unsignedBigInteger('room_id')->nullable();
            $table->string('status', 30)->default('open'); // open, paid, posted_to_room, cancelled
            $table->bigInteger('total_amount')->default(0);
            $table->string('tender_type', 50)->nullable();
            $table->unsignedBigInteger('folio_id')->nullable();
            $table->unsignedBigInteger('folio_charge_id')->nullable();
            $table->string('idempotency_key', 100)->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->unique(['property_id', 'order_number']);
            $table->unique(['property_id', 'idempotency_key']);
            $table->index(['property_id', 'outlet_id', 'status']);
        });

        Schema::create('pos_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('pos_orders')->cascadeOnDelete();
            $table->string('item_name', 200);
            $table->integer('quantity');
            $table->bigInteger('unit_price');
            $table->bigInteger('total_price');
            $table->timestamp('created_at')->useCurrent();

            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_order_items');
        Schema::dropIfExists('pos_orders');
        Schema::dropIfExists('pos_outlets');
    }
};

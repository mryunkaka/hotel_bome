<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->restrictOnDelete();
            $table->string('code', 50);
            $table->string('name', 200);
            $table->string('category', 50)->default('amenity'); // amenity, minibar, linen, supply
            $table->string('unit', 30)->default('pcs'); // pcs, bottle, pack, set
            $table->integer('par_level')->default(0);
            $table->bigInteger('unit_cost')->default(0); // in IDR
            $table->bigInteger('sale_price')->nullable(); // in IDR (minibar sale price)
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['property_id', 'code']);
            $table->index(['property_id', 'category']);
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->restrictOnDelete();
            $table->foreignId('item_id')->constrained('inventory_items')->restrictOnDelete();
            $table->string('movement_type', 50); // receive, issue, adjustment, minibar_sale
            $table->integer('quantity'); // positive for addition, negative for deduction
            $table->bigInteger('unit_cost')->nullable();
            $table->string('reference_type', 50)->nullable(); // purchase_order, room, folio
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('reason', 500)->nullable();
            $table->unsignedBigInteger('actor_id');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['property_id', 'item_id']);
        });

        // Trigger to enforce immutability on stock_movements
        foreach (['UPDATE', 'DELETE'] as $operation) {
            $name = 'stock_movements_no_'.strtolower($operation);
            $message = "stock_movements is immutable: {$operation} not allowed";
            $sql = match (DB::getDriverName()) {
                'sqlite' => "CREATE TRIGGER {$name} BEFORE {$operation} ON stock_movements BEGIN SELECT RAISE(ABORT, '{$message}'); END",
                'mysql' => "CREATE TRIGGER {$name} BEFORE {$operation} ON stock_movements FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = '{$message}'",
                default => throw new RuntimeException('Stock movement triggers are unsupported for this database driver.'),
            };
            DB::unprepared($sql);
        }
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS stock_movements_no_update');
        DB::unprepared('DROP TRIGGER IF EXISTS stock_movements_no_delete');

        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('inventory_items');
    }
};

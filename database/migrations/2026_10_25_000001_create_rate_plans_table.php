<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rate_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->restrictOnDelete();
            $table->string('name', 100);
            $table->string('code', 20);
            $table->unsignedInteger('base_price_per_night'); // IDR, no decimals
            $table->decimal('tax_percentage', 5, 2)->nullable(); // e.g. 10.00 for PBJT
            $table->date('effective_from')->nullable();
            $table->date('effective_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['property_id', 'code']);
            $table->index(['property_id', 'is_active']);
        });

        Schema::create('rate_plan_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->restrictOnDelete();
            $table->foreignId('rate_plan_id')->constrained()->restrictOnDelete();
            $table->string('event_type', 50); // created, updated, deactivated
            $table->json('changes')->nullable();
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rate_plan_events');
        Schema::dropIfExists('rate_plans');
    }
};

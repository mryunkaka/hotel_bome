<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_spaces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->restrictOnDelete();
            $table->string('code', 50);
            $table->string('name', 200);
            $table->integer('capacity')->default(0);
            $table->bigInteger('hourly_rate')->default(0); // in IDR
            $table->bigInteger('day_rate')->default(0); // in IDR
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['property_id', 'code']);
            $table->index(['property_id', 'is_active']);
        });

        Schema::create('event_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->restrictOnDelete();
            $table->foreignId('space_id')->constrained('event_spaces')->restrictOnDelete();
            $table->string('event_name', 200);
            $table->string('organizer_name', 200);
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->integer('expected_attendees')->nullable();
            $table->string('setup_style', 50)->nullable(); // theater, banquet, u_shape, classroom
            $table->string('status', 30)->default('confirmed'); // tentative, confirmed, in_progress, completed, cancelled
            $table->bigInteger('rental_amount')->default(0);
            $table->bigInteger('catering_amount')->default(0);
            $table->bigInteger('total_amount')->default(0);
            $table->bigInteger('deposit_required')->default(0);
            $table->unsignedBigInteger('folio_id')->nullable();
            $table->string('cancellation_reason', 500)->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->index(['property_id', 'space_id', 'status']);
            $table->index(['property_id', 'start_time', 'end_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_bookings');
        Schema::dropIfExists('event_spaces');
    }
};

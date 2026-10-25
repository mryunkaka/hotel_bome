<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_outbox', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->restrictOnDelete();
            $table->uuid('event_uuid');
            $table->string('topic', 100);
            $table->json('payload');
            $table->string('status', 30)->default('queued'); // queued, in_flight, delivered, failed
            $table->integer('attempts')->default(0);
            $table->integer('max_attempts')->default(5);
            $table->text('last_error')->nullable();
            $table->timestamp('next_retry_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->unique(['property_id', 'event_uuid']);
            $table->index(['property_id', 'status', 'next_retry_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_outbox');
    }
};

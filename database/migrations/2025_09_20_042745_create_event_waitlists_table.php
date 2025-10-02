<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
/**
 * Migration: create event_waitlists
 *
 * Why:
 * - Separate table avoids nullable columns on bookings and supports FIFO.
 * - Indexes speed up “next-in-line” queries by event and time.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('event_waitlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('notified_at')->nullable(); 
            $table->timestamps();

            $table->unique(['event_id', 'user_id']); //one entry per user event
            $table->index(['event_id', 'created_at']); //add index for performance by specify which row to check immidiately
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_waitlists');
    }
};

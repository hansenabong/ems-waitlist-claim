<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create events
 *
 * Why:
 * - Stores organiser-owned events with capacity for booking/waitlist logic.
 */
return new class extends Migration
{
    
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organiser_id')->constrained('users')->cascadeOnDelete();
            $table->string('title', 100);
            $table->text('description')->nullable();
            $table->dateTime('starts_at');
            $table->string('location', 255);
            $table->unsignedInteger('capacity'); // validate 1..1000 later
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Migration: add claim-window columns
     *
     * Why:
     * - hold_expires_at: enforces temporary “offer window”.
     * - claimed_at: audit/guard against duplicate claims.
     * - Extra indexes accelerate scheduler and claim lookups.
     */
    public function up(): void
    {
        Schema::table('event_waitlists', function (Blueprint $table) {
            $table->timestamp('hold_expires_at')->nullable()->after('notified_at');
            $table->timestamp('claimed_at')->nullable()->after('hold_expires_at');

            $table->index(['event_id', 'hold_expires_at']);
            $table->index(['event_id', 'claimed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('event_waitlists', function (Blueprint $table) {
            $table->dropIndex(['event_id', 'hold_expires_at']);
            $table->dropIndex(['event_id', 'claimed_at']);
            $table->dropColumn(['hold_expires_at', 'claimed_at']);
        });
    }
};

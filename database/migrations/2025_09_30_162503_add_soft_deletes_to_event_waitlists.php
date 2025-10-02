<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Migration: enable soft deletes on event_waitlists
     *
     * Why:
     * - Keep audit trail and allow users to rejoin later without new row churn.
     */
    public function up(): void
    {
        Schema::table('event_waitlists', function (Blueprint $table) {
            //
            $table->softDeletes(); // adds deleted_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('event_waitlists', function (Blueprint $table) {
            $table->dropSoftDeletes(); // removes deleted_at
        });
    }
};

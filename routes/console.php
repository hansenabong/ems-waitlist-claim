<?php

use Illuminate\Support\Facades\Schedule;
/**
 * routes/console.php
 *
 * Purpose:
 * - Define scheduled tasks using the Laravel 11+ scheduling hook.
 * - This runs when `php artisan schedule:work` or the system cron triggers
 *   `schedule:run` every minute.
 */
Schedule::command('waitlist:process-expired')->everyMinute();


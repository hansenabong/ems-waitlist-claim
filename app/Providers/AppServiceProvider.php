<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;
/**
 * AppServiceProvider
 *
 * Purpose:
 *  - Defines simple Gates used by can:* middleware in routes/controllers.
 *  - Central place to register policies later if required.
 *
 * Security:
 *  - viewOrganiser: restricts routes/views to organiser accounts.
 *  - viewAttendee:  restricts routes/views to attendee accounts.
 *
 * Note:
 *  - Ensure the User model has a 'type' attribute persisted in DB/seeders.
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Gate used by can:viewOrganiser middleware
        Gate::define('viewOrganiser', function (User $user) {
            return $user->type === 'organiser';
        });
        // Gate used by can:viewAttendee middleware
        Gate::define('viewAttendee', function (User $user) {
            return $user->type === 'attendee';
        });
    }
}

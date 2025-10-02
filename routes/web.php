<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EventWaitlistController;
use Illuminate\Support\Facades\Route;

/**
 * routes/web.php
 *
 * Purpose:
 * - Public pages (events list/show), auth-protected attendee and organiser flows,
 *   and the signed claim route for waitlist offers.
 *
 * Security highlights:
 * - Organiser-only areas: can:viewOrganiser.
 * - Attendee-only areas:  can:viewAttendee.
 * - Claim link:           signed + attendee gate.
 */

Route::view('/privacy', 'static.privacy')->name('policy');
Route::view('/terms',   'static.terms')->name('terms');

//landing page -> upcoming events
Route::redirect('/', '/events');
// Public
Route::get('/events', [EventController::class, 'index'])->name('events.index');
Route::get('/events/{event}', [EventController::class, 'show'])
    ->whereNumber('event')
    ->name('events.show');
// Organiser dashboard + waitlist overview
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'can:viewOrganiser'])
    ->name('dashboard');
Route::get('/organiser/waitlists', [EventWaitlistController::class, 'indexOrganiser'])
    ->middleware(['auth', 'can:viewOrganiser'])
    ->name('organiser.waitlists');
// Auth-protected attendee features
Route::middleware('auth')->group(function () {
    Route::get('/bookings', [BookingController::class, 'index'])->name('bookings.index')
        ->middleware(['can:viewAttendee']);
    Route::get('/mywaitlist', [EventWaitlistController::class, 'indexAttendee'])->name('mywaitlist')
        ->middleware(['can:viewAttendee']);
    Route::post('/events/{event}/book', [BookingController::class, 'store'])->name('bookings.store')
        ->whereNumber('event')
        ->middleware(['can:viewAttendee']);
    Route::post('/events/{event}/waitlist', [EventWaitlistController::class, 'store'])->name('waitlist.store')
        ->whereNumber('event')
        ->middleware(['can:viewAttendee']);
    Route::get('/waitlist/{waitlist}/claim', [EventWaitlistController::class, 'claim'])
        ->whereNumber('waitlist')
        ->name('waitlist.claim')
        ->middleware(['signed', 'can:viewAttendee' ]); 
    Route::delete('/events/{event}/waitlist', [EventWaitlistController::class, 'delete'])->name('waitlist.delete')
        ->middleware(['can:viewAttendee']);
    Route::delete('/bookings/{booking}', [BookingController::class, 'delete'])
        ->whereNumber('booking')
        ->name('bookings.delete')
        ->middleware(['auth', 'can:viewAttendee']);
    Route::get('/events/create', [EventController::class, 'create'])->name('events.create')
        ->middleware(['can:viewOrganiser']);
    Route::post('/events', [EventController::class, 'store'])->name('events.store')
        ->middleware(['can:viewOrganiser']);
    Route::get('/events/{event}/edit', [EventController::class, 'edit'])->name('events.edit')
        ->whereNumber('event')
        ->middleware(['can:viewOrganiser']);
    Route::put('/events/{event}', [EventController::class, 'update'])->name('events.update')
        ->middleware(['can:viewOrganiser']);
    Route::delete('/events/{event}', [EventController::class, 'delete'])->name('events.delete')
        ->middleware(['can:viewOrganiser']);
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';

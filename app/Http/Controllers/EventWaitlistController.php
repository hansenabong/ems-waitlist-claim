<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Event;
use App\Models\EventWaitlist;
use App\Models\Booking;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

/**
 * Class EventWaitlistController
 *
 * Responsibilities
 * - Attendee-facing: join/leave waitlist, list my waitlists, claim offer.
 * - Organiser-facing: list events that currently have an active waitlist.
 *
 * Business Rules
 * - FIFO fairness: queue ordered by created_at ASC.
 * - A user can’t waitlist if event has seats or they already hold a booking.
 * - Claim link has a time-bound hold; capacity is re-checked under lock on claim.
 *
 * Concurrency
 * - All state transitions that depend on capacity use DB transactions + row locks.
 */

class EventWaitlistController extends Controller
{
    /**
     * Authorisation helper for attendee-only actions.
     *
     * Why:
     * - Centralises the role check for join/leave/index actions so that
     *   intent is explicit and future caller mistakes fail fast.
     *
     * @param  Request $request Authenticated request (must have user).
     * @return void
     */
    private function assertAttendee(Request $request): void
    {
        // Requires auth middleware; restricts to attendee accounts only.
        abort_unless($request->user()->type === 'attendee', 403);
    }
    /**
     * POST /events/{event}/waitlist — Join the waitlist for a full, upcoming event.
     *
     * Why:
     * - Prevents duplicate rows (via soft-deleted restore or existing active check).
     * - Enforces that users should book instead if capacity exists.
     *
     * Key checks:
     * - Event must be upcoming.
     * - Event must be full.
     * - User must not already have a booking.
     * - If a soft-deleted row exists for (event,user), restore it to preserve FIFO order.
     *
     * @param  Request $request Current request (attendee).
     * @param  Event   $event   Target event (route-model bound).
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request, Event $event)
    {
        $this->assertAttendee($request);
        $user = $request->user();
        // Re-admit previously soft-deleted waitlist entries to preserve history/FIFO.
        $existing = EventWaitlist::withTrashed()
            ->where('event_id', $event->id)
            ->where('user_id', $user->id)
            ->first();
        if ($existing) {
            if ($existing->trashed()) {
                // bring it back as a fresh, active waitlist entry
                $existing->restore();
                $existing->forceFill([
                    'notified_at'     => null,
                    'hold_expires_at' => null,
                    'claimed_at'      => null,
                ])->save();

                return back()->with('status', 'You rejoined the waiting list.');
            }
            // already active on the waitlist
            return back()->withErrors(['booking' => 'You are already on this waitlist.']);
        }
        if (! $event->starts_at->isFuture()) {
            return back()->withErrors(['booking' => 'Event is not upcoming.']);
        }

        // must be full (otherwise ask them to book)
        $isFull = $event->bookings()->count() >= $event->capacity;
        if (! $isFull) {
            return back()->withErrors(['booking' => 'Event has spots; please book instead.']);
        }

        // cannot already be booked
        if (Booking::where('event_id', $event->id)->where('user_id', $user->id)->exists()) {
            return back()->withErrors(['booking' => 'You already have a booking for this event.']);
        }

        $event->waitlist()->create(['user_id' => $user->id]);
        return back()->with('status', 'You joined the waiting list.');
    }
    /** 
     * DELETE /events/{event}/waitlist — Leave the waitlist.
     *
     * Why:
     * - Lets attendees opt out at any time; uses soft-delete (via model trait)
     *   so admins can audit history if needed (depending on model setup).
     *
     * @param  Request $request Current request (attendee).
     * @param  Event   $event   Event context for which the user leaves the queue.
     * @return \Illuminate\Http\RedirectResponse
     */
    public function delete(Request $request, Event $event)
    {
        $this->assertAttendee($request);
        $event->waitlist()->where('user_id', $request->user()->id)->delete();
        return back()->with('status', 'You left the waiting list.');
    }
    /**
     * GET /mywaitlist — List active waitlist entries for the current attendee.
     *
     * Why:
     * - Filters to upcoming events only (no noise from past events).
     * - Suppresses consumed claims by excluding rows with claimed_at set.
     *
     * @param  Request $request Current request (attendee).
     * @return \Illuminate\Contracts\View\View
     */
    public function indexAttendee(Request $request)
    {
        $this->assertAttendee($request);

        $entries = EventWaitlist::with('event')
            ->where('user_id', $request->user()->id)
            ->whereHas('event', fn($q) => $q->where('starts_at', '>', now()))
            ->whereNull('claimed_at')                // only active rows
            ->orderBy('created_at')
            ->get();

        return view('waitlist.index', compact('entries'));
    }
    /**
     * GET /organiser/waitlists — List organiser’s events that currently have a queue.
     *
     * Why:
     * - withCount adds bookings_count and an active waitlist_count (claimed_at IS NULL)
     *   so organisers get a quick at-a-glance view without extra queries.
     * - remaining_spots is computed in PHP to keep the SQL simple.
     *
     * @param  Request $request Current request (organiser).
     * @return \Illuminate\Contracts\View\View
     */
    public function indexOrganiser(Request $request)
    {
        abort_unless($request->user()?->type === 'organiser', 403);

        $events = Event::where('organiser_id', $request->user()->id)
            ->withCount(['bookings', 'waitlist as waitlist_count' => fn($q) => $q->whereNull('claimed_at'),])   // adds bookings_count, waitlist_count that is active
            ->whereHas('waitlist', fn($q) => $q->whereNull('claimed_at'))                  // only events that have active waitlist rows
            ->orderBy('starts_at')
            ->get()
            ->map(function ($e) {
                $e->remaining_spots = max(0, $e->capacity - $e->bookings_count);
                return $e;
            });

        return view('waitlist.organiser', compact('events'));
    }
      /**
     * GET /waitlist/{waitlist}/claim — Claim a held seat via signed link (attendee).
     *
     * Why:
     * - Runs inside a DB transaction and locks the event row to prevent races.
     * - Re-checks that the claim window is still valid and capacity exists.
     * - Creates booking if not already present, marks claim as consumed,
     *   and cleans up any duplicate waitlist rows for the same user+event.
     *
     * Security:
     * - Route is signed; controller ensures the waitlist row belongs to the caller.
     *
     * @param  Request        $request  Current request (attendee).
     * @param  EventWaitlist  $waitlist The specific waitlist row being claimed.
     * @return \Illuminate\Http\RedirectResponse
     */
    public function claim(Request $request, EventWaitlist $waitlist)
    {
        abort_unless($waitlist->user_id === $request->user()->id, 403);
        try {
            DB::transaction(function () use ($waitlist) {
                $event = Event::whereKey($waitlist->event_id)->lockForUpdate()->first();

                // window valid?
                if ($waitlist->claimed_at || !$waitlist->hold_expires_at || now()->greaterThan($waitlist->hold_expires_at)) {
                    throw new \RuntimeException('This offer has expired or was already claimed.');
                }

                // capacity check under lock
                $current = $event->bookings()->lockForUpdate()->count();
                if ($current >= $event->capacity) {
                    throw new \RuntimeException('Sorry, the event is full.');
                }

                // create booking if not already exists
                if (! $event->bookings()->where('user_id', $waitlist->user_id)->exists()) {
                    $event->bookings()->create(['user_id' => $waitlist->user_id]);
                }

                // mark claimed
                $waitlist->forceFill(['claimed_at' => now()])->save();
                // clean any other stale waitlist rows for same user+event 
                $event->waitlist()->where('user_id', $waitlist->user_id)->delete();
            });

            return redirect()->route('bookings.index')->with('status', 'Seat claimed and booking created!');
        } catch (\RuntimeException $e) {
            return back()->withErrors(['booking' => $e->getMessage()]);
        }
    }
}

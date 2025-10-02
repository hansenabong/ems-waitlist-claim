<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;
use App\Models\Event;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use App\Mail\WaitlistOfferMail;
use Illuminate\Support\Facades\Mail;

/**
 * Class BookingController
 *
 * Purpose:
 *  - Expose attendee booking actions (list, create, cancel).
 *  - Enforce role-based access and fairness rules around capacity & waitlist.
 *
 * Security:
 *  - All routes run behind auth middleware.
 *  - Attendee-only actions are guarded (see assertAttendee()).
 *
 * Data Integrity:
 *  - Concurrency-sensitive paths are wrapped in DB transactions and use
 *    row-level locking (FOR UPDATE) to prevent race conditions that could
 *    oversell capacity or skip the FIFO waitlist.
 */
class BookingController extends Controller
{
    /**
     * Ensures only 'attendee' users can perform booking actions.
     * Why:
     * We still call this even when routes use gates, to keep authorization
     * explicit and close to the sensitive code path.
     * @param  Request  $request  The current HTTP request containing the authenticated user.
     * @return void
     */
    private function assertAttendee(Request $request): void
    {
        abort_unless($request->user()->type === 'attendee', 403);
    }
    /**
     * GET /bookings — List the authenticated attendee's bookings (newest first).
     *
     * Why:
     *  - Eager-load the related Event to avoid N+1 queries in Blade.
     *
     * @param  Request  $request  Current authenticated request.
     * @return \Illuminate\Contracts\View\View  Bookings index view.
     */
    public function index(Request $request)
    {
        $bookings = Booking::with('event')           // eager-load event details
            ->where('user_id', $request->user()->id) // only this user's bookings
            ->latest()                               //ORDERED by created_at DESC
            ->get();

        return view('bookings.index', ['bookings' => $bookings]);
    }
    /**
     * POST /events/{event}/book — Create a booking for the given Event.
     *
     * Business rules:
     *  - Only attendees can book.
     *  - Event must be upcoming; user cannot already hold a booking.
     *  - Capacity must not be exceeded.
     *  - If there’s an active waitlist hold (claim window) for someone else, block.
     *  - If we succeed, clean any stale waitlist row for this user/event.
     *
     * Concurrency:
     *  - Lock the Event row as a mutex, then count bookings under lock.
     *  - Also lock the waitlist rows we consult, so the FIFO/hold check is atomic.
     *
     * @param  Request  $request  Current authenticated request.
     * @param  Event    $event    Route-model bound Event to qebook.
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request, Event $event)
    {
        $this->assertAttendee($request);
        $user = $request->user();

        // Manual pre-checks (use the same keyed error 'booking')
        if (!$event->starts_at->isFuture()) {
            return back()->withErrors(['booking' => 'Event is not upcoming.']);
        }
        if ($event->bookings()->where('user_id', $user->id)->exists()) {
            return back()->withErrors(['booking' => 'You have already booked this event.']);
        }

        try {
            //db transaction wrapper to prevent race condition(ensuring the steps done 1 by 1 from locking the event row
            //, check capacity, and then insert the booking)
            DB::transaction(function () use ($event, $user) {
                // 1) lock the event row as a mutex
                $lockEvent = Event::where('id', $event->id)->lockForUpdate()->first();

                // 2) lock & count bookings for this event
                $current = $lockEvent->bookings()->lockForUpdate()->count();
                if ($current >= $lockEvent->capacity) {
                    throw new \RuntimeException('Event is full.');
                }

                // 3) NEW: query to show who's first at waitlist respect active claim window (FIFO)
                $activeHold = $lockEvent->waitlist()
                    ->whereNotNull('notified_at')
                    ->whereNull('claimed_at')
                    ->where('hold_expires_at', '>', now())
                    ->orderBy('created_at', 'asc')
                    ->lockForUpdate()
                    ->first();

                // If there is an active hold for SOMEONE ELSE → block normal booking
                // type cast the id to ensure the $activehold query doesnt return string type
                if ($activeHold && (int)$activeHold->user_id !== (int)$user->id) {
                    throw new \RuntimeException('Seat temporarily held for a waitlisted attendee.');
                }

                // create booking
                $lockEvent->bookings()->create(['user_id' => $user->id]);
                // Clean up any stale waitlist row for this user+event
                $lockEvent->waitlist()->where('user_id', $user->id)->delete();
            });
        } catch (\RuntimeException $error) {
            return back()->withErrors(['booking' => $error->getMessage()]);
        }

        return redirect()->route('bookings.index')->with('status', 'Successfully Booked.');
    }
    /**
     * DELETE /bookings/{booking} — Cancel an attendee's own booking.
     *
     * Business rules:
     *  - Only the owner (attendee) can cancel their booking.
     *  - If the event was full at the moment of cancellation, notify the next
     *    person on the waitlist (FIFO) with a 15-minute signed claim link.
     *
     * Concurrency:
     *  - Lock the Event and count bookings under lock to decide whether the
     *    event was full just before we delete (prevents double-notify or skip).
     *
     * @param  Request  $request   Current authenticated request.
     * @param  Booking  $booking   Route-model bound Booking to delete.
     * @return \Illuminate\Http\RedirectResponse
     */
    public function delete(Request $request, Booking $booking)
    {
        // Owner-only guard (belt-and-suspenders alongside gates)
        abort_unless($booking->user_id === $request->user()->id, 403);
        $this->assertAttendee($request);
        DB::transaction(function () use ($booking) {
            $lockEvent = Event::where('id', $booking->event_id)->lockForUpdate()->first();
            $bookingCount = $lockEvent->bookings()->lockForUpdate()->count();
            $isEventFull = $bookingCount >= $lockEvent->capacity;
            $booking->delete();
            if ($isEventFull) {
                $next = $lockEvent->waitlist()
                    ->whereNull('claimed_at') //make sure not to notify someone who already claimed
                    ->whereNull('notified_at')   // not yet contacted
                    ->orderBy('created_at', 'asc')      // FIFO
                    ->first();

                if ($next) {
                    $next->forceFill([
                        'notified_at' => now(),
                        'hold_expires_at' => now()->addMinutes(15),
                    ])->save();
                    $claimUrl = URL::temporarySignedRoute(
                        'waitlist.claim',
                        now()->addMinutes(15),
                        ['waitlist' => $next->id]
                    );
                    Mail::to($next->user->email)->send(new WaitlistOfferMail($lockEvent, $claimUrl));
                }
            }
        });
        return back()->with('status', 'You cancelled the booking.');
    }
}

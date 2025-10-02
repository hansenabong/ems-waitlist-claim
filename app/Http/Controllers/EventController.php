<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventWaitlist;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Class EventController
 *
 * Responsibilities:
 * - Public listing, show
 * - Organiser create/update/delete
 *
 * Security:
 * - Mutating actions gated by assertOwner() (organiser-only)
 *
 * Notes:
 * - "Upcoming" = starts_at > now()
 * - Availability shown on show() via live booking count
 */

class EventController extends Controller
{
    /**
     * Authorisation helper: only the organiser (owner) can mutate an event.
     *
     * Why:
     * - Keeps permission logic close to sensitive operations.
     * - Clear fail-fast 403 instead of silent fall-through.
     *
     * @param  Request  $request  Current authenticated request.
     * @param  Event    $event    Event being mutated.
     * @return void
     */
    private function assertOwner(Request $request, Event $event): void
    {
        // If no user OR user doesn't match organiser_id → 403
        //make sure organiser doesnt mess other organiser events
        abort_unless($request->user()?->id === $event->organiser_id, 403);
    }
    /**
     * GET /events — Paginated list of upcoming events.
     *
     * Why:
     * - Sort by start time to surface near-term events first.
     * - Paginate (8) to keep page fast and predictable.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        $events = Event::where('starts_at', '>', now())
            ->orderBy('starts_at')
            ->paginate(8);

        return view('events.index', ['events' => $events]);
    }
    /**
     * GET /events/create — Render create form (organiser).
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function create()
    {
        return view('events.create');
    }
    /**
     * POST /events — Create a new event owned by the current organiser.
     *
     * Why:
     * - organiser_id is derived server-side from the authenticated user
     *   to prevent spoofing via client form fields.
     *
     * @param  Request  $request
     * @return \Illuminate\Contracts\View\View
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'title'       => 'required|string|max:100',
            'description' => 'nullable|string',
            'starts_at'   => 'required|date|after:now',
            'location'    => 'required|string|max:255',
            'capacity'    => 'required|integer|min:1|max:1000',
        ]);
        // Security: organiser_id is derived from the authenticated user(logged in user), not client input to prevent spoofing.
        $data['organiser_id'] = $request->user()->id;
        $event = Event::create($data); // requires $fillable on the Event model
        return redirect()->route('events.show', $event)->with('status', 'Event created.');
    }
    /**
     * GET /events/{event}/edit — Render edit form (organiser-only).
     *
     * @param  Request  $request
     * @param  Event    $event
     * @return \Illuminate\Contracts\View\View
     */
    public function edit(Request $request, Event $event)
    {
        $this->assertOwner($request, $event);
        return view('events.edit', ['event' => $event]);
    }
    /**
     * PUT /events/{event} — Update an existing event (organiser-only).
     *
     * Why:
     * - organiser_id is immutable here to avoid ownership transfer via UI(hide or dont show organiser id).
     *
     * @param  Request  $request
     * @param  Event    $event
     * @return \Illuminate\Contracts\View\View
     */
    public function update(Request $request, Event $event)
    {
        $this->assertOwner($request, $event);
        $data = $request->validate([
            'title'       => 'required|string|max:100',
            'description' => 'nullable|string',
            'starts_at'   => 'required|date|after:now',
            'location'    => 'required|string|max:255',
            'capacity'    => 'required|integer|min:1|max:1000',
        ]);
        $event->update($data);
        return redirect()->route('events.show', $event)->with('status', 'Event updated.');
    }
    /**
     * GET /events/{event} — Show an event with live availability and UI flags.
     *
     * UI Flags:
     * - $checkOrganiser: organiser sees edit/delete controls.
     * - $checkUser: attendee sees “Book” when seats available & upcoming.
     * - $checkWaitlist: attendee sees “Join Waitlist” only when full & upcoming
     *   and they’re neither booked nor already on the waitlist.
     *
     * Why:
     * - Eager-load organiser so Blade can access $event->organiser->name
     *   without extra queries.
     *
     * @param  Request  $request
     * @param  Event    $event
     * @return \Illuminate\Contracts\View\View
     */
    public function show(Request $request, Event $event)
    {
        $user = $request->user();
        // True when current user is the organiser (used for edit/delete controls)
        $checkOrganiser = $user?->type === 'organiser' && $user?->id === $event->organiser_id; //if true = show edit and delete
        // Eager-load organiser on this Event so Blade can use $event->organiser->name
        // without extra queries (post-fetch eager loading).
        $event->load('organiser');
        // Live availability: count current bookings for this event
        $bookingCount = Booking::where('event_id', $event->id)->count();
        $alreadyBooked = $user && Booking::where('event_id', $event->id)->where('user_id', $user->id)->exists();
        $onWaitlist     = $user && EventWaitlist::where('event_id', $event->id)->where('user_id', $user->id)->whereNull('claimed_at')->exists();
        $available = max(0, $event->capacity - $bookingCount);
        // Conditions for showing "Join Waitlist" button to an attendee
        $checkWaitlist = $user?->type === 'attendee'
            && $available === 0
            && $event->starts_at->isFuture()
            && !$alreadyBooked
            && !$onWaitlist;
        // Conditions for showing "Book" button to an attendee
        $checkUser = $user?->type === 'attendee'
            && $available > 0
            && $event->starts_at->isFuture()
            && !$alreadyBooked;
        return view('events.show', [
            'event' => $event,
            'available' => $available,
            'checkUser' => $checkUser,
            'checkOrganiser' => $checkOrganiser,
            'checkWaitlist' => $checkWaitlist,
            'onWaitlist' => $onWaitlist
        ]);
    }
    /**
     * DELETE /events/{event} — Delete an event (owner-only) if it has zero bookings.
     *
     * Why:
     * - Prevents breaking attendee expectations by removing a booked event.
     * - Uses route-model binding so we validate against the correct event
     *   when authorising via assertOwner().
     *
     * @param  Request  $request
     * @param  Event    $event
     * @return \Illuminate\Http\RedirectResponse
     */
    public function delete($id)
    {
        $request = new Request;
        $event = new Event;

        $this->assertOwner($request, $event);

        $event = Event::findOrFail($id);
        // Protect attendees: do not allow deletion when there are bookings
        $bookingCount = Booking::where('event_id', $event->id)->count();
        if ($bookingCount > 0) {
            return back()->withErrors([
                'delete' => 'Cannot delete this event because it has existing bookings.',
            ]);
        } else {
            $event->delete();   // <-- deletes the row
        }
        return redirect('dashboard')->with('status', 'Event deleted.');
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Event;
use App\Models\Booking;
use Illuminate\Support\Facades\DB;

/**
 * Class DashboardController
 *
 * Purpose:
 *   - Show the organiser's own events on the dashboard with live availability.
 *
 * Security:
 *   - Server-side filter ensures only events where organiser_id === auth user are returned.
 *
 * Performance:
 *   - Single SQL statement with scalar subselects to compute bookings_count and remaining_spots.
 *   - Uses parameter binding to prevent SQL injection.
 *   - Ensure DB indexes on events.organiser_id and bookings.event_id.
 *
 * Future:
 *   - Consider Eloquent + withCount('bookings') (see commented block) for readability and pagination.
 */
class DashboardController extends Controller

{
    /**
     * GET /dashboard
     *
     * Retrieves the authenticated organiser’s events (ordered by start time) with
     * computed booking totals and remaining capacity for display.
     * Purpose:
     *
     *
     * Query notes:
     * - Uses parameter binding to prevent SQL injection.
     * - Scalar subqueries compute bookings_count per event efficiently.
     * - remaining_spots is capacity - bookings_count (clamp to >=0 in Blade if desired).
     * The dashboard view populated with the organiser’s events.
     * @param  Request  $request  The current HTTP request with the authenticated user.
     * @return \Illuminate\Http\RedirectResponse  
     */
    public function index(Request $request)
    {
        // Authenticated organiser id (middleware should guarantee a logged-in user ).
        $user = $request->user()->id;
        /*
         * Query notes:
         * - Parameter binding (?) prevents SQL injection.
         * - Correlated subqueries compute bookings_count per event without GROUP BY in the outer query.
         * - remaining_spots is derived from capacity - bookings_count; clamp in Blade if you want non-negative display.
         * - ORDER BY starts_at presents upcoming events chronologically.
         */
        $events = DB::select('SELECT e.id, e.title, e.starts_at, e.capacity, 
            (SELECT COUNT(*) FROM bookings b WHERE b.event_id = e.id) AS bookings_count,
            e.capacity - (SELECT COUNT(*) FROM bookings b WHERE b.event_id = e.id) AS remaining_spots
            FROM events e
            WHERE e.organiser_id = ?
            ORDER BY e.starts_at', [$user]);
        return view('dashboard', ['events' => $events]);
    }
}

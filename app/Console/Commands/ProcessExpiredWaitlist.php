<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Event;
use App\Models\EventWaitlist;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use App\Mail\WaitlistOfferMail;
use Carbon\Carbon;

/**
 * Class ProcessExpiredWaitlist
 *
 * Purpose:
 * - Advances the waitlist automatically by expiring unclaimed offers and notifying
 *   the next person in FIFO order when capacity is available.
 *
 * Why a console command:
 * - Lets us run on a schedule (every minute) for hands-free operations without
 *   coupling this logic to web requests.
 *
 * Concurrency & Fairness:
 * - Operates per-event inside DB transactions.
 * - Locks the Event row as a mutex while counting bookings and selecting the next
 *   candidate to avoid races and preserve FIFO integrity.
 *
 * Business Rules:
 * - A claim is considered expired when: notified_at is set, claimed_at is null,
 *   and hold_expires_at <= now.
 * - Expired offers are reset to "waiting" (cleared timestamps) and sent to the end
 *   of the queue by bumping created_at (because FIFO uses created_at ASC).
 * - Only notify the next user if capacity exists right now under lock.
 */
class ProcessExpiredWaitlist extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'waitlist:process-expired';


    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear expired claim holds and notify next waitlisters (FIFO)';

    /**
     * Execute the command.
     *
     * Why:
     * - Batch scans for expired holds across all events, then processes each
     *   event in isolation to keep transactions short and row locks minimal.
     *
     * Return:
     * - Always return Command::SUCCESS for scheduler health; specific per-event
     *   outcomes (e.g., “no capacity”) are normal and not failures.
     *
     * @return int Command::SUCCESS on completion.
     */
    public function handle(): int
    {
        $now = Carbon::now();

        // 1) Find all rows whose claim window expired (notified, not claimed, expired)
        $expired = EventWaitlist::query()
            ->whereNotNull('notified_at')
            ->whereNull('claimed_at')
            ->where('hold_expires_at', '<=', $now)
            ->get();

        // 2) Process per-event to keep transactions clean and locks minimal
        foreach ($expired->groupBy('event_id') as $eventId => $rows) {
            DB::transaction(function () use ($eventId, $rows, $now) {
                // Lock event as our mutex
                $event = Event::whereKey($eventId)->lockForUpdate()->first();

                // 2a) Clear/soft-delete the expired holds for THIS event
                // Option A: reset them to "waiting again"
                EventWaitlist::whereIn('id', $rows->pluck('id'))->update([
                    'notified_at'     => null,
                    'hold_expires_at' => null,
                    'claimed_at' => null,
                    'created_at'      => $now,             // push to back (FIFO uses created_at ASC), waitlist at last position
                    'updated_at'      => $now,
                ]);

                // 2b) If there’s still capacity, notify the next person
                $bookings = $event->bookings()->lockForUpdate()->count();
                if ($bookings >= $event->capacity) {
                    // still full; nothing to do
                    return;
                }

                // Next FIFO person who is NOT in an active/claimed state
                $next = $event->waitlist()
                    ->whereNull('claimed_at')
                    ->whereNull('notified_at')    // hasn’t been contacted now
                    ->orderBy('created_at', 'asc') // FIFO
                    ->lockForUpdate()
                    ->first();

                if ($next) {
                    $next->forceFill([
                        'notified_at'     => $now,
                        'hold_expires_at' => $now->copy()->addMinutes(15),
                    ])->save();

                    $claimUrl = URL::temporarySignedRoute(
                        'waitlist.claim',
                        $now->copy()->addMinutes(15),
                        ['waitlist' => $next->id]
                    );

                    Mail::to($next->user->email)->send(
                        new WaitlistOfferMail($event, $claimUrl)
                    );
                }
            }); // transaction
        }

        $this->info('Expired holds processed.');
        return self::SUCCESS;
    }
}

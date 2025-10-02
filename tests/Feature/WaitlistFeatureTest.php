<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Event;
use App\Models\EventWaitlist;
use App\Models\Booking;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use App\Mail\WaitlistOfferMail;
use Illuminate\Support\Carbon;

class WaitlistFeatureTest extends TestCase
{
    use RefreshDatabase;

    private function attendee(): User
    {
        return User::factory()->create(['type' => 'attendee', 'password' => bcrypt('password')]);
    }
    private function organiser(): User
    {
        return User::factory()->create(['type' => 'organiser']);
    }

    public function test_attendee_sees_join_waitlist_when_event_is_full(): void
    {
        $event = Event::factory()->create(['capacity' => 1, 'starts_at' => now()->addDay()]);
        Booking::create(['user_id' => $this->attendee()->id, 'event_id' => $event->id]);

        $me = $this->attendee();
        $res = $this->actingAs($me)->get(route('events.show', $event));
        $res->assertOk()->assertSee('Join Waitlist');
    }

    public function test_attendee_can_join_and_leave_waitlist(): void
    {
        $event = Event::factory()->create(['capacity' => 1, 'starts_at' => now()->addDay()]);
        Booking::create(['user_id' => $this->attendee()->id, 'event_id' => $event->id]);

        $me = $this->attendee();
        $this->actingAs($me)->post(route('waitlist.store', $event))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('event_waitlists', ['user_id' => $me->id, 'event_id' => $event->id]);

        $this->actingAs($me)->delete(route('waitlist.delete', $event))
            ->assertSessionHasNoErrors();
        $this->assertSoftDeleted('event_waitlists', [
            'user_id'  => $me->id,
            'event_id' => $event->id,
        ]);
    }

    public function test_automated_notification_triggers_when_full_event_booking_is_cancelled(): void
    {
        Mail::fake();

        $event = Event::factory()->create(['capacity' => 1, 'starts_at' => now()->addDay()]);
        $holder = $this->attendee();
        Booking::create(['user_id' => $holder->id, 'event_id' => $event->id]);

        $next = $this->attendee();
        EventWaitlist::create(['user_id' => $next->id, 'event_id' => $event->id]);

        // Cancel booking (hits your controller logic)
        $this->actingAs($holder)->delete(route('bookings.delete', Booking::first()))
            ->assertSessionHasNoErrors();

        Mail::assertSent(WaitlistOfferMail::class, 1);
    }

    public function test_signed_claim_link_allows_claim_within_window_and_creates_booking(): void
    {
        $this->freezeTime();

        $attendee = User::factory()->create(['type' => 'attendee']);
        $event    = Event::factory()->create(['capacity' => 1]);

        // Make the event full first
        $other = User::factory()->create(['type' => 'attendee']);
        $booking = Booking::factory()->create([
            'event_id' => $event->id,
            'user_id'  => $other->id,
        ]);

        // Put the attendee on the waitlist and "notify" them with a 15-min window
        $wl = EventWaitlist::factory()->create([
            'event_id'        => $event->id,
            'user_id'         => $attendee->id,
            'notified_at'     => now(),
            'hold_expires_at' => now()->addMinutes(15),
            'claimed_at'      => null,
        ]);

        // Simulate the cancellation that frees a seat (what happens in production before claiming)
        $booking->delete();

        // Generate the signed claim URL and claim
        $url = URL::temporarySignedRoute('waitlist.claim', now()->addMinutes(15), [
            'waitlist' => $wl->id,
        ]);

        $this->actingAs($attendee)
            ->get($url)
            ->assertRedirect(route('bookings.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('bookings', [
            'event_id' => $event->id,
            'user_id'  => $attendee->id,
        ]);
        $this->assertNotNull($wl->fresh()->claimed_at);
    }

    public function test_claim_fails_after_expiry_and_moves_to_next_on_scheduler(): void
    {
        Mail::fake();

        $event = Event::factory()->create(['capacity' => 1, 'starts_at' => now()->addDay()]);
        $holder = $this->attendee();
        Booking::create(['user_id' => $holder->id, 'event_id' => $event->id]);

        $a = $this->attendee();
        $b = $this->attendee();

        $wlA = EventWaitlist::create([
            'user_id' => $a->id,
            'event_id' => $event->id,
            'notified_at' => now()->subMinutes(20),
            'hold_expires_at' => now()->subMinutes(5), // expired
        ]);
        $wlB = EventWaitlist::create([
            'user_id' => $b->id,
            'event_id' => $event->id,
        ]);

        // seat becomes free → cancel holder; mail to A would have been sent earlier, but it’s expired now.
        $this->actingAs($holder)->delete(route('bookings.delete', Booking::first()));

        // Manually run the console command (simulating scheduler)
        $this->artisan('waitlist:process-expired')->assertSuccessful();

        // B should be notified
        Mail::assertSent(WaitlistOfferMail::class, function ($m) use ($event) {
            return $m->event->id === $event->id;
        });
    }

    public function test_active_hold_blocks_non_waitlisted_booking(): void
    {
        $event = Event::factory()->create(['capacity' => 1, 'starts_at' => now()->addDay()]);
        // free seat but give hold to someone
        $a = $this->attendee();
        $wl = EventWaitlist::create([
            'user_id' => $a->id,
            'event_id' => $event->id,
            'notified_at' => now(),
            'hold_expires_at' => now()->addMinutes(15)
        ]);

        $b = $this->attendee();
        $this->actingAs($b)->post(route('bookings.store', $event))
            ->assertSessionHasErrors('booking'); // seat temporarily held
    }
}

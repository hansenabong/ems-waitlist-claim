<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Event;
use App\Models\Booking;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class AttendeeActionsTest extends TestCase
{
    use RefreshDatabase;

    private function makeAttendee(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'type' => 'attendee',
            'password' => Hash::make('password'),
        ], $overrides));
    }

    public function test_a_user_can_successfully_register_as_an_attendee(): void
    {
        $res = $this->post('/register', [
            'name' => 'A',
            'email' => 'a@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'agree' => 'on', // adjust to your form input
        ]);

        $res->assertRedirect();
        $this->assertDatabaseHas('users', ['email' => 'a@example.com']);
    }

    public function test_a_registered_attendee_can_log_in_and_log_out(): void
    {
        $user = $this->makeAttendee(['email' => 'a@example.com']);
        $this->post('/login', ['email' => 'a@example.com', 'password' => 'password'])
             ->assertRedirect();

        $this->assertTrue(Auth::check());
        $this->post('/logout')->assertRedirect();
        $this->assertFalse(Auth::check());
    }

    public function test_a_logged_in_attendee_can_book_an_available_upcoming_event(): void
    {
        $user = $this->makeAttendee();
        $event = Event::factory()->create(['capacity' => 5, 'starts_at' => now()->addDays(2)]);

        $this->actingAs($user)->post(route('bookings.store', $event))
             ->assertRedirect(route('bookings.index'));

        $this->assertDatabaseHas('bookings', ['user_id' => $user->id, 'event_id' => $event->id]);
    }

    public function test_after_booking_an_attendee_can_see_the_event_on_their_bookings_page(): void
    {
        $user = $this->makeAttendee();
        $event = Event::factory()->create(['capacity' => 5, 'starts_at' => now()->addDays(2)]);
        Booking::create(['user_id' => $user->id, 'event_id' => $event->id]);

        $this->actingAs($user)->get(route('bookings.index'))
             ->assertOk()->assertSee($event->title);
    }

    public function test_an_attendee_cannot_book_the_same_event_more_than_once(): void
    {
        $user = $this->makeAttendee();
        $event = Event::factory()->create(['capacity' => 5, 'starts_at' => now()->addDays(2)]);
        Booking::create(['user_id' => $user->id, 'event_id' => $event->id]);

        $this->actingAs($user)->post(route('bookings.store', $event))
             ->assertSessionHasErrors('booking');
    }

    public function test_an_attendee_cannot_book_a_full_event(): void
    {
        $user = $this->makeAttendee();
        $event = Event::factory()->create(['capacity' => 1, 'starts_at' => now()->addDays(2)]);
        // Fill it
        Booking::create(['user_id' => $this->makeAttendee()->id, 'event_id' => $event->id]);

        $this->actingAs($user)->post(route('bookings.store', $event))
             ->assertSessionHasErrors('booking');
    }

    public function test_an_attendee_cannot_see_edit_or_delete_buttons_on_any_event_page(): void
    {
        $user = $this->makeAttendee();
        $event = Event::factory()->create(['starts_at' => now()->addDays(2)]);

        $this->actingAs($user)->get(route('events.show', $event))
             ->assertOk()
             ->assertDontSee('Edit')
             ->assertDontSee('Delete');
    }
}

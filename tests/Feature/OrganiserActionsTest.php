<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Event;
use App\Models\Booking;

class OrganiserActionsTest extends TestCase
{
    use RefreshDatabase;

    private function makeOrganiser(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'type' => 'organiser',
            'password' => bcrypt('password'),
        ], $overrides));
    }

    public function test_an_organiser_can_log_in_and_view_their_specific_dashboard(): void
    {
        $org = $this->makeOrganiser(['email' => 'o@example.com']);
        $this->post('/login', ['email' => 'o@example.com', 'password' => 'password'])
             ->assertRedirect();

        $this->get(route('dashboard'))->assertOk();
    }

    public function test_an_organiser_can_successfully_create_an_event_with_valid_data(): void
    {
        $org = $this->makeOrganiser();
        $this->actingAs($org)->post(route('events.store'), [
            'title' => 'My Event',
            'description' => 'D',
            'starts_at' => now()->addDays(3),
            'location' => 'L',
            'capacity' => 50,
        ])->assertRedirect();

        $this->assertDatabaseHas('events', ['title' => 'My Event', 'organiser_id' => $org->id]);
    }

    public function test_an_organiser_receives_validation_errors_for_invalid_event_data(): void
    {
        $org = $this->makeOrganiser();
        $this->actingAs($org)->post(route('events.store'), [
            'title' => '',
            'starts_at' => now()->subDay(),
            'capacity' => -1,
        ])->assertSessionHasErrors();
    }

    public function test_an_organiser_can_successfully_update_an_event_they_own(): void
    {
        $org = $this->makeOrganiser();
        $event = Event::factory()->create(['organiser_id' => $org->id, 'title' => 'Old']);

        $this->actingAs($org)->put(route('events.update', $event), [
            'title' => 'New',
            'description' => $event->description,
            'starts_at' => now()->addDays(4),
            'location' => 'New L',
            'capacity' => 100,
        ])->assertRedirect();

        $this->assertDatabaseHas('events', ['id' => $event->id, 'title' => 'New']);
    }

    public function test_an_organiser_cannot_update_an_event_created_by_another_organiser(): void
    {
        $orgA = $this->makeOrganiser();
        $orgB = $this->makeOrganiser();
        $event = Event::factory()->create(['organiser_id' => $orgA->id]);

        $this->actingAs($orgB)->put(route('events.update', $event), [
            'title' => 'Hack',
            'description' => $event->description,
            'starts_at' => now()->addDays(4),
            'location' => 'X',
            'capacity' => 100,
        ])->assertForbidden();
    }

    public function test_an_organiser_can_delete_an_event_they_own_that_has_no_bookings(): void
    {
        $org = $this->makeOrganiser();
        $event = Event::factory()->create(['organiser_id' => $org->id]);

        $this->actingAs($org)->delete(route('events.delete', $event))->assertRedirect();
        $this->assertDatabaseMissing('events', ['id' => $event->id]);
    }

    public function test_an_organiser_cannot_delete_an_event_that_has_active_bookings(): void
    {
        $org = $this->makeOrganiser();
        $event = Event::factory()->create(['organiser_id' => $org->id]);
        Booking::create(['user_id' => User::factory()->create(['type' => 'attendee'])->id, 'event_id' => $event->id]);

        $this->actingAs($org)->delete(route('events.delete', $event))
             ->assertSessionHasErrors(); // or ->assertStatus(302) then check errors bag

        $this->assertDatabaseHas('events', ['id' => $event->id]);
    }
}

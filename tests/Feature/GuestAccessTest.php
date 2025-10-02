<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Event;

class GuestAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_guest_can_view_the_paginated_list_of_upcoming_events(): void
    {
        Event::factory()->count(3)->create(['starts_at' => now()->addDays(5)]);
        $res = $this->get(route('events.index'));
        $res->assertOk()->assertSeeTextInOrder(Event::pluck('title')->all());
    }

    public function test_a_guest_can_view_a_specific_event_details_page(): void
    {
        $event = Event::factory()->create(['starts_at' => now()->addWeek()]);
        $res = $this->get(route('events.show', $event));
        $res->assertOk()->assertSee($event->title);
    }

    public function test_a_guest_is_redirected_when_accessing_protected_routes(): void
    {
        $event = Event::factory()->create(['starts_at' => now()->addWeek()]);
        $this->get(route('bookings.index'))->assertRedirect(route('login'));
        $this->post(route('bookings.store', $event))->assertRedirect(route('login'));
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_a_guest_cannot_see_action_buttons_on_event_details_page(): void
    {
        $event = Event::factory()->create(['starts_at' => now()->addWeek()]);
        $res = $this->get(route('events.show', $event));
        $res->assertOk();
        $res->assertDontSee('Edit');
        $res->assertDontSee('Delete');
        $res->assertDontSee('Book Now');
        $res->assertDontSee('Join Waitlist');
    }
}

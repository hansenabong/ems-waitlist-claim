<?php

namespace Database\Factories;

use App\Models\EventWaitlist;
use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory: EventWaitlist
 *
 * Purpose:
 * - Generate waitlist rows in controlled states (fresh, notified, claimed)
 *   to test FIFO and claim-window behaviour.
 */
class EventWaitlistFactory extends Factory
{
    /**
     * Base blueprint: “fresh” waitlist (no notification yet).
     *
     * @return array<string,mixed>
     */
    protected $model = EventWaitlist::class;
    /**
     * Base blueprint: “fresh” waitlist (no notification yet).
     *
     * @return array<string,mixed>
     */
    public function definition(): array
    {
        return [
            'event_id'        => Event::factory(),                     // or pass ->for($event)
            'user_id'         => User::factory()->state(['type' => 'attendee']),
            'notified_at'     => null,
            'hold_expires_at' => null,
            'claimed_at'      => null,
        ];
    }

    // handy states if you need them in tests
    public function notified(): static
    {
        return $this->state(fn() => [
            'notified_at'     => now(),
            'hold_expires_at' => now()->addMinutes(15),
        ]);
    }

    public function claimed(): static
    {
        return $this->state(fn() => ['claimed_at' => now()]);
    }
}

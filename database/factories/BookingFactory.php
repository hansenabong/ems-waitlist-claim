<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory: Booking
 *
 * Purpose:
 * - Quickly create valid bookings for tests/seeds.
 * - Defaults to a fresh event + attendee user; tests can override.
 */
class BookingFactory extends Factory
{
    /** @var class-string<\App\Models\Booking> */
    protected $model = Booking::class;
    /**
     * Base blueprint for a Booking.
     *
     * @return array<string,mixed>
     */
    public function definition(): array
    {
        return [
            // create related models by default; override in tests as needed
            'event_id' => Event::factory(),
            'user_id'  => User::factory()->state(['type' => 'attendee']),
        ];
    }
}

<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Event>
 */
/**
 * EventFactory
 *
 * Purpose:
 *  - Provides realistic test/seed data for Event records.
 *
 * Defaults:
 *  - starts_at is always in the future (between +1 day and +90 days).
 *  - capacity uses a reasonable range for demos.
 *
 * Notes:
 *  - organiser_id is intentionally left unset here so tests/seeders can attach
 *    a specific organiser (User) as needed:
 *      Event::factory()->for($organiser, 'organiser')->create();
 */
class EventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organiser_id' => \App\Models\User::factory()->state(['type' => 'organiser']),
            'title' => fake()->sentence(3),
            'description' => fake()->sentence(),
            'starts_at' => now()->addDays(3),
            'location' => fake()->city(),
            'capacity' => 30,
        ];
    }
}

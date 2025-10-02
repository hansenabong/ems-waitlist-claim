<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Event;

/**
 * EventSeeder
 *
 * Purpose:
 * - Populates multiple events split across existing organisers
 *   to exercise dashboards and pagination.
 */
class EventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $organisers = User::where('type', 'organiser')->pluck('id');
        // make sure we have at least two organisers
        if ($organisers->count() === 0) return;

        // 18 events split across organisers
        Event::factory(18)->make()->each(function ($event) use ($organisers) {
            $event->organiser_id = $organisers->random();
            $event->save();
        });
    }
}

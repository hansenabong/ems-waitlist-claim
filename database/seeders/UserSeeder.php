<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * UserSeeder
 *
 * Purpose:
 * - Creates two organisers with known credentials and a set of attendees
 *   so both roles can be exercised in demos/tests.
 */
class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // organisers
        User::factory()->create([
            'name' => 'Org One',
            'email' => 'org1@example.com',
            'password' => Hash::make('password'),
            'type' => 'organiser',
        ]);
        User::factory()->create([
            'name' => 'Org Two',
            'email' => 'org2@example.com',
            'password' => Hash::make('password'),
            'type' => 'organiser',
        ]);

        // attendees
        User::factory(10)->create(['type' => 'attendee']);
    }
}

<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // create a test user
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'a@a.a',
            'password' => bcrypt('a'),
        ]);

        // call regs and meetings seeders
        $this->call([
            RegSeeder::class,
            MeetingSeeder::class,
        ]);
    }
}

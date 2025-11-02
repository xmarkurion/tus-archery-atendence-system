<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Reg;

class RegSeeder extends Seeder
{
    public function run(): void
    {
        Reg::factory()->count(150)->create();
    }
}


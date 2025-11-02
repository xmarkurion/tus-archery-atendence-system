<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Reg>
 */
class RegFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'number' => (string)fake()->unique()->numerify('S####'),
            'sessions_attended' => fake()->numberBetween(0, 10),
        ];
    }
}


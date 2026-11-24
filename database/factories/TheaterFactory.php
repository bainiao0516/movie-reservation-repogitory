<?php

namespace Database\Factories;

use App\Models\Theater;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Theater>
 */
class TheaterFactory extends Factory
{
    protected $model = Theater::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'YOHO シネマズ ' . fake()->unique()->city(),
            'location' => fake()->address(),
        ];
    }
}

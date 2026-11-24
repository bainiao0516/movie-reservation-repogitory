<?php

namespace Database\Factories;

use App\Models\Screen;
use App\Models\Theater;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Screen>
 */
class ScreenFactory extends Factory
{
    protected $model = Screen::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'theater_id' => Theater::factory(),
            'name' => 'SCREEN ' . fake()->numberBetween(1, 10),
        ];
    }
}

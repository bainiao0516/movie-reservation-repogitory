<?php

namespace Database\Factories;

use App\Models\Screen;
use App\Models\Seat;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Seat>
 */
class SeatFactory extends Factory
{
    protected $model = Seat::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $row = fake()->randomElement(['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H']);
        $number = fake()->numberBetween(1, 15);

        return [
            'screen_id' => Screen::factory(),
            'name' => "{$row}-{$number}",
        ];
    }
}

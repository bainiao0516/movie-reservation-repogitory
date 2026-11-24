<?php

namespace Database\Factories;

use App\Models\Movie;
use App\Models\Schedule;
use App\Models\Screen;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Schedule>
 */
class ScheduleFactory extends Factory
{
    protected $model = Schedule::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startAt = fake()->dateTimeBetween('now', '+7 days');
        $endAt = (clone $startAt)->modify('+3 hours');

        return [
            'screen_id' => Screen::factory(),
            'movie_id' => Movie::factory(),
            'start_at' => $startAt,
            'end_at' => $endAt,
        ];
    }
}

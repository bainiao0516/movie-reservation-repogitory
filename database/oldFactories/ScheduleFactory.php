<?php

namespace Database\Factories;

use App\Models\Movie;
use App\Models\Schedule;
use App\Models\Screen;
use App\Models\Theater;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Schedule>
 */
class ScheduleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {

        $startTime = Carbon::instance($this->faker->dateTimeBetween('now', '+1 week'));

        return [
            'theater_id' => Theater::factory(),
            'screen_id' => Screen::factory(),
            'movie_id' => Movie::factory(),
            'start_time' => $startTime,
            'end_time' => $startTime->copy()->addMinutes(fake()->numberBetween(60, 180)),
        ];
    }
}

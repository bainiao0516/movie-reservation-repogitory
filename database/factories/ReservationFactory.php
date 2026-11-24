<?php

namespace Database\Factories;

use App\Models\Reservation;
use App\Models\Schedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reservation>
 */
class ReservationFactory extends Factory
{
    protected $model = Reservation::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'schedule_id' => Schedule::factory(),
            'reservation_code' => strtoupper(fake()->bothify('YC-????##')),
            'name' => fake()->name(),
            'tel' => fake()->phoneNumber(),
            'email' => fake()->safeEmail(),
            'total_amount' => fake()->randomElement([2000, 4000, 6000]),
            'status' => 'completed',
        ];
    }
}

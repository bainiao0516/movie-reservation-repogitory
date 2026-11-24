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
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'schedule_id' => Schedule::factory(),
            'name' => fake()->name(),
            'tel' => fake()->phoneNumber(),
            'email' => fake()->safeEmail(),
            'status' => 'completed', 
            'expires_at' => null,
        ];
    }
}

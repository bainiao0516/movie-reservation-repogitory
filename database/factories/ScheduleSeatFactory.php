<?php

namespace Database\Factories;

use App\Models\Schedule;
use App\Models\ScheduleSeat;
use App\Models\Seat;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ScheduleSeat>
 */
class ScheduleSeatFactory extends Factory
{
    protected $model = ScheduleSeat::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'schedule_id' => Schedule::factory(),
            'seat_id' => Seat::factory(),
            'reservation_id' => null,
            'status' => 'available',
            'expires_at' => null,
        ];
    }
}

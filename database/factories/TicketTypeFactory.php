<?php

namespace Database\Factories;

use App\Models\TicketType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TicketType>
 */
class TicketTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word() . 'チケット',
            'price' => $this->faker->numberBetween(10, 20) * 100,
            'display_order' => $this->faker->numberBetween(1, 10),
            'is_active' => true,
        ];
    }
}

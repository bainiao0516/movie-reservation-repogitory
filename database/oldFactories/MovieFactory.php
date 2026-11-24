<?php

namespace Database\Factories;

use App\Models\Movie;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Movie>
 */
class MovieFactory extends Factory
{
    private static int $imageIndex = 0;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $images = [
            'honda-sony.png',
            'iconic-sp.png',
            'koke.png',
            'lion.png',
            'mitake.png',
            'mouflon.png',
            'nagoya.png',
            'nara.png',
            'nobeyama.png',
            'rome.png',
            'sakana.png',
            'soya.png',
            'soyuz.png',
        ];

        $thumbnailPath = $images[self::$imageIndex % count($images)];
        self::$imageIndex++;

        return [
            'name' => fake()->word(),
            'time_minutes' => fake()->numberBetween(12, 18) * 10,
            'thumbnail_path' => $thumbnailPath,
        ];
    }
}

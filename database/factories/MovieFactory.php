<?php

namespace Database\Factories;

use App\Models\Movie;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Movie>
 */
class MovieFactory extends Factory
{
    protected $model = Movie::class;

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

        $releaseDate = fake()->dateTimeBetween('-1 month', 'now');
        $endDate = (clone $releaseDate)->modify('+1 month');

        return [
            'title' => fake()->unique()->word(),
            'duration_minutes' => fake()->numberBetween(12, 18) * 10,
            'thumbnail_path' => $thumbnailPath,
            'rating' => fake()->randomElement(['G', 'PG12', 'R15+', 'R18+']),
            'director' => fake()->name(),
            'cast' => implode(', ', [fake()->name(), fake()->name(), fake()->name()]),
            'description' => fake()->realText(150),
            'release_date' => $releaseDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
        ];
    }
}

<?php

namespace Database\Seeders;

use App\Models\Screen;
use App\Models\Theater;
use Illuminate\Database\Seeder;

class ScreenSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $theaters = Theater::all();

        foreach ($theaters as $theater) {
            for ($number = 1; $number <= 10; $number++) {
                Screen::factory()->create([
                    'theater_id' => $theater->id,
                    'name' => 'Screen' . $number,
                ]);
            }
        }
    }
}

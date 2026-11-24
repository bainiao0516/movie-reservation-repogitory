<?php

namespace Database\Seeders;

use App\Models\Screen;
use App\Models\Seat;
use Illuminate\Database\Seeder;

class SeatSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $screens = Screen::all();

        $rows = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H']; // 8行

        foreach ($screens as $screen) {
            foreach ($rows as $row) {
                for ($number = 1; $number <= 8; $number++) {
                    Seat::factory()->create([
                        'screen_id' => $screen->id,
                        'name' => $row . $number,
                    ]);
                }
            }
        }
    }
}

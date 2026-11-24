<?php

namespace Database\Seeders;

use App\Models\Movie;
use App\Models\Schedule;
use App\Models\Theater;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $theaters = Theater::all();
        $movies = Movie::all();

        $timeSlots = ['10:00', '14:00', '18:00'];
        $movieIndex = 0;

        foreach ($theaters as $theater) {
            $screens = $theater->screens;
            foreach ($screens as $screen) {
                $movie = $movies[$movieIndex % $movies->count()];
                $movieIndex++;
                for ($i = 0; $i <= 14; $i++) {
                    $dateStr = Carbon::today()->addDays($i)->format('Y-m-d');
                    foreach ($timeSlots as $slot) {
                        $startTime = Carbon::parse("{$dateStr} {$slot}");

                        Schedule::factory()->create([
                            'theater_id' => $theater->id,
                            'screen_id' => $screen->id,
                            'movie_id' => $movie->id,
                            'start_time' => $startTime,
                            'end_time' => $startTime->copy()->addMinutes($movie->time_minutes + 15),
                        ]);
                    }
                }
            }
        }
    }
}

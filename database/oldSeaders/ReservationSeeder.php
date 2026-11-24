<?php

namespace Database\Seeders;

use App\Models\Reservation;
use App\Models\Schedule;
use Illuminate\Database\Seeder;

class ReservationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Schedule::select('id')->chunkById(1000, function($schedules) {
            foreach ($schedules as $schedule) {
                Reservation::factory()
                    ->count(5)
                    ->create([
                        'schedule_id' => $schedule->id,
                    ]);
            }
        });
    }
}

<?php

namespace Database\Seeders;

use App\Models\ReservationSeat;
use App\Models\Schedule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReservationSeatSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 全スケジュールと、それに紐づくスクリーン・座席・予約を一括取得
        Schedule::with(['screen.seats', 'reservations'])->chunkById(100, function($schedules) {
            $insertData = [];

            foreach ($schedules as $schedule) {
                // 2席ずつ区切って隣接ペアを作り、順番をランダム化
                $pairs = $schedule->screen->seats->chunk(2)->shuffle();

                foreach ($schedule->reservations as $reservation) {
                    if ($pairs->isEmpty()) {
                        break;
                    }

                    // ランダムな隣接2席を取り出して登録
                    foreach ($pairs->pop() as $seat) {
                        ReservationSeat::factory()->create([
                            'reservation_id' => $reservation->id,
                            'seat_id' => $seat->id,
                            'created_at'     => now(),
                            'updated_at'     => now(),
                        ]);
                    }
                }
            }

            if (!empty($insertData)) {
                DB::table('reservation_seats')->insert($insertData);
            }
        });
    }
}

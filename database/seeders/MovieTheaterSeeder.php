<?php

namespace Database\Seeders;

use App\Models\Movie;
use App\Models\Screen;
use App\Models\Seat;
use App\Models\Theater;
use App\Models\TicketType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class MovieTheaterSeeder extends Seeder
{
    public function run(): void
    {
        // 1. 処理高速化のためクエリログをオフ
        DB::disableQueryLog();

        // デフォルト券種
        $rawTicketTypes = [
            ['name' => '大人', 'price' => 1900, 'display_order' => 1, 'is_active' => true],
            ['name' => '高校生・大学生', 'price' => 1500, 'display_order' => 2, 'is_active' => true],
            ['name' => '小人（3歳〜中学生）', 'price' => 1000, 'display_order' => 3, 'is_active' => true],
            ['name' => 'シニア（60歳以上）', 'price' => 1200, 'display_order' => 4, 'is_active' => true],
        ];

        DB::table('ticket_types')->insert($rawTicketTypes);
        $ticketTypes = TicketType::all();

        // 2. 映画館 75個を作成（Fakerの重複エラー回避のため連番化）
        $theaters = Theater::factory()
            ->count(75)
            ->sequence(fn ($sq) => ['name' => 'YOHOシネマズ 劇場' . ($sq->index + 1)])
            ->create();

        // 3. スクリーン & 物理座席の一括作成
        $seatRows = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H']; // 8行 × 8列 = 64席
        $screensData = [];

        foreach ($theaters as $theater) {
            for ($s = 1; $s <= 15; $s++) {
                $screensData[] = [
                    'theater_id' => $theater->id,
                    'name' => "SCREEN {$s}",
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // スクリーンを一括挿入
        DB::table('screens')->insert($screensData);
        $allScreens = Screen::all();

        // 各スクリーンの座席をバルクインサート（メモリ圧迫を防ぐため10,000件単位）
        $seatsData = [];
        foreach ($allScreens as $screen) {
            foreach ($seatRows as $row) {
                for ($num = 1; $num <= 8; $num++) {
                    $seatsData[] = [
                        'screen_id' => $screen->id,
                        'name' => "{$row}{$num}",
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            if (count($seatsData) >= 10000) {
                DB::table('seats')->insert($seatsData);
                $seatsData = [];
            }
        }
        if (!empty($seatsData)) {
            DB::table('seats')->insert($seatsData);
        }

        // 4. 映画作品 30個を作成
        $movies = Movie::factory()->count(30)->create()->sortBy('id')->values();
        $movieCount = $movies->count();

        // 5. スケジュール・予約・座席在庫（30日分）の一括挿入
        $today = Carbon::today();

        $reservationCounter = 1;

        // メモリ節約のため、物理座席情報をあらかじめスクリーンIDごとにキャッシュ
        $seatsByScreen = Seat::all()->groupBy('screen_id');

        foreach ($allScreens as $index => $screen) {
            $movie1 = $movies[($index * 2) % $movieCount];
            $movie2 = $movies[($index * 2 + 1) % $movieCount];
            $assignedMovies = [$movie1, $movie2];

            $seats = $seatsByScreen->get($screen->id);

            // スクリーンの隣接ペア（32組）をあらかじめ配列化
            $seatsByRow = $seats->groupBy(fn($seat) => substr($seat->name, 0, 1));
            $allAdjacentPairs = [];
            foreach ($seatsByRow as $rowSeats) {
                $sortedRow = $rowSeats->sortBy(fn($seat) => (int) substr($seat->name, 1))->values();
                for ($i = 0; $i < $sortedRow->count(); $i += 2) {
                    if (isset($sortedRow[$i + 1])) {
                        $allAdjacentPairs[] = [$sortedRow[$i]->id, $sortedRow[$i + 1]->id];
                    }
                }
            }

            for ($day = 0; $day < 30; $day++) {
                $currentDate = $today->copy()->addDays($day);

                $timeSlots = [
                    ['start' => '09:00', 'movie' => $assignedMovies[0]],
                    ['start' => '13:00', 'movie' => $assignedMovies[0]],
                    ['start' => '17:00', 'movie' => $assignedMovies[1]],
                    ['start' => '21:00', 'movie' => $assignedMovies[1]],
                ];

                foreach ($timeSlots as $slot) {
                    $movie = $slot['movie'];
                    $startAt = Carbon::parse("{$currentDate->toDateString()} {$slot['start']}");
                    $endAt = (clone $startAt)->addMinutes($movie->duration_minutes + 15);

                    // ① スケジュールを作成し、生成された ID を直接取得
                    $scheduleId = DB::table('schedules')->insertGetId([
                        'screen_id' => $screen->id,
                        'movie_id' => $movie->id,
                        'start_at' => $startAt,
                        'end_at' => $endAt,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    // ② ランダムに 0〜32組（0〜64席分）の予約対象を抽出
                    $pairCount = rand(0, count($allAdjacentPairs));
                    $shuffledPairs = $allAdjacentPairs;
                    shuffle($shuffledPairs);
                    $reservedSeatPairs = array_slice($shuffledPairs, 0, $pairCount);

                    // ③ 予約データを準備
                    $reservationsData = [];
                    $reservedSeatIds = [];
                    $assignedTicketsBySeat = [];

                    foreach ($reservedSeatPairs as $pair) {
                        $code = str_pad(strtoupper(base_convert($reservationCounter++, 10, 36)), 8, '0', STR_PAD_LEFT);

                        $ticket1 = $ticketTypes->random();
                        $ticket2 = $ticketTypes->random();
                        $assignedTicketsBySeat[$pair[0]] = $ticket1;
                        $assignedTicketsBySeat[$pair[1]] = $ticket2;

                        $totalAmount = $ticket1->price + $ticket2->price;

                        $reservationsData[] = [
                            'schedule_id' => $scheduleId,
                            'reservation_code' => $code,
                            'name' => fake()->name(),
                            'tel' => fake()->phoneNumber(),
                            'email' => fake()->safeEmail(),
                            'total_amount' => 3800,
                            'status' => 'completed',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                        $reservedSeatIds[] = $pair[0];
                        $reservedSeatIds[] = $pair[1];
                    }

                    // 予約が存在する場合のみ一括挿入して先頭IDを取得
                    $firstReservationId = null;
                    if (!empty($reservationsData)) {
                        DB::table('reservations')->insert($reservationsData);
                        $firstReservationId = DB::getPdo()->lastInsertId();
                    }

                    // ④ ScheduleSeats（64席分の在庫）を一括作成
                    $scheduleSeatData = [];
                    $resOffset = 0;

                    // 予約済み座席
                    foreach ($reservedSeatPairs as $pair) {
                        $currentResId = $firstReservationId + $resOffset;
                        foreach ($pair as $seatId) {
                            $ticket = $assignedTicketsBySeat[$seatId];

                            $scheduleSeatData[] = [
                                'schedule_id' => $scheduleId,
                                'seat_id' => $seatId,
                                'reservation_id' => $currentResId,
                                'ticket_type_id' => $ticket->id,
                                'price' => $ticket->price,
                                'status' => 'reserved',
                                'expires_at' => null,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                        }
                        $resOffset++;
                    }

                    // 空席（残り座席）
                    $allSeatIds = $seats->pluck('id')->toArray();
                    $unreservedSeatIds = array_diff($allSeatIds, $reservedSeatIds);
                    foreach ($unreservedSeatIds as $seatId) {
                        $scheduleSeatData[] = [
                            'schedule_id' => $scheduleId,
                            'seat_id' => $seatId,
                            'ticket_type_id' => null,
                            'price' => null,
                            'reservation_id' => null,
                            'status' => 'available',
                            'expires_at' => null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }

                    // 64席分を seat_id 順にソートし、一括挿入
                    usort($scheduleSeatData, fn($a, $b) => $a['seat_id'] <=> $b['seat_id']);
                    DB::table('schedule_seats')->insert($scheduleSeatData);
                }
            }
        }
    }
}

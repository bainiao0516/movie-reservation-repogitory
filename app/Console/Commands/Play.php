<?php

namespace App\Console\Commands;

use App\Models\Movie;
use App\Models\ReservationSeat;
use App\Models\Schedule;
use App\Models\ScheduleSeat;
use App\Models\Theater;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('app:play')]
#[Description('Command description')]
class Play extends Command
{
    protected $signature = 'app:play';

    protected $description = 'Eloquentのパフォーマンスと発行SQLを検証する';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $measure = function (string $title, callable $callback) {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $startTime = microtime(true);

            $callback();

            $executionTime = round((microtime(true) - $startTime) * 1000, 2);
            $queries = DB::getQueryLog();

            $this->info("[検証] {$title}");
            $this->line(" ├─ 処理時間  : {$executionTime} ms");
            $this->line(' └─ 発行SQL数 : ' . count($queries) . ' 回');
            $this->line('');
        };

        // --------------------------------------------------
        // 検証クエリの実行
        // --------------------------------------------------

        $measure('映画館一覧の取得', function() {
            return Theater::all();
        });

        $theater = Theater::find(1);
        $startOfDay = '2026-09-09';
        $endOfDay = '2026-09-16';

        $measure('上映スケジュールの取得', function () use ($theater, $startOfDay, $endOfDay) {
            $screenIds = $theater->screens()->pluck('id');

            $scheduleConstraint = function ($query) use ($screenIds, $startOfDay, $endOfDay) {
                $query->whereIn('screen_id', $screenIds)
                    ->whereBetween('start_at', [$startOfDay, $endOfDay])
                    ->orderBy('start_at', 'asc');
            };

            return Movie::whereHas('schedules', $scheduleConstraint)
                ->with(['schedules' => $scheduleConstraint])
                ->get();
        });

        $schedule = Schedule::find(1);

        $measure('座席一覧の取得（リレーション付き）', function() use ($schedule) {
            return ScheduleSeat::with('seat')
                ->where('schedule_id', $schedule->id)
                ->get();
        });

        $reservationId = 1;

        $measure('仮押さえ済みの座席取得', function() use($reservationId) {
            return ScheduleSeat::where('reservation_id', $reservationId)->pluck('seat_id')
            ->toArray();
        });
    }
}

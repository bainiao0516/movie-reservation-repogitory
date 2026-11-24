<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use App\Models\Theater;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        // 公開日が新しい順に6件取得
        $latestMovies = Movie::latest('release_date')->take(6)->get();

        $today = today();
        $reservationStart = now()->subWeek();
        $popularMovies = Movie::query()
            ->whereDate('release_date', '<=', $today)
            ->where(function ($query) use ($today) {
                $query->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $today);
            })
            ->withCount([
                'schedules as reserved_seats_count' => function ($query) use ($reservationStart) {
                    $query->join(
                        'schedule_seats',
                        'schedule_seats.schedule_id',
                        '=',
                        'schedules.id'
                    )->join(
                        'reservations',
                        'reservations.id',
                        '=',
                        'schedule_seats.reservation_id'
                    )->where('schedule_seats.status', 'reserved')
                        ->where('reservations.status', 'completed')
                        ->where('reservations.created_at', '>=', $reservationStart);
                },
            ])
            ->orderByDesc('reserved_seats_count')
            ->take(5)
            ->get();

        // 訪問した劇場の取得
        $visitedTheaterIds = array_map(
            'intval',
            session('visited_theater_ids', []),
        );

        $recentTheaters = Theater::query()
            ->whereIn('id', $visitedTheaterIds)
            ->get()
            ->sortBy(
                fn (Theater $theater) => array_search(
                    $theater->id,
                    $visitedTheaterIds,
                    true,
                ),
            )
            ->values();

        return view('home.index', compact('latestMovies', 'popularMovies', 'recentTheaters'));
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use App\Models\Theater;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TheaterController extends Controller
{
    public function index(Request $request): View
    {
        $location = $request->input('location');

        $theaters = Theater::query()
            ->when($location, function ($query, $location) {
                $query->where(function ($q) use ($location) {
                    $q->where('name', 'like', "%{$location}%")
                        ->orWhere('location', 'like', "%{$location}%");
                });
            })
            ->paginate(20)
            ->withQueryString();

        return view('theaters.index', compact('theaters'));
    }

    public function indexSchedules(Request $request, Theater $theater): View|RedirectResponse
    {
        $today = now()->startOfDay();
        $inputDate = $request->query('date');

        // 日付形式チェック
        $isValid = $inputDate && is_string($inputDate) && Carbon::hasFormat($inputDate, 'Y-m-d');
        $parsedDate = $isValid ? Carbon::parse($inputDate)->startOfDay() : null;

        // クエリが不正・未指定・過去日付の場合は「今日の日付URL」へリダイレクト（URLを書き換える）
        if (!$isValid || $parsedDate->isBefore($today)) {
            return redirect()->route('theaters.schedules.index', [
                'theater' => $theater->id,
                'date' => $today->format('Y-m-d'),
            ]);
        }

        $date = $parsedDate;
        $dates = collect(range(0, 7))->map(fn($i) => $today->copy()->addDays($i));

        $startOfDay = $date->copy()->startOfDay();
        $endOfDay = $date->copy()->endOfDay();

        $screenIds = $theater->screens()->pluck('id');

        $scheduleConstraint = function ($query) use ($screenIds, $startOfDay, $endOfDay) {
            $query->whereIn('screen_id', $screenIds)
                ->whereBetween('start_at', [$startOfDay, $endOfDay])
                ->orderBy('start_at', 'asc')
                ->withCount('scheduleSeats as schedule_seats_count')
                ->withCount(['scheduleSeats as reserved_seats_count' => function ($q) {
                    $q->where('status', 'reserved')
                        ->orWhere(function ($sub) {
                            $sub->where('status', 'temporary')
                                ->where('expires_at', '>', now());
                        });
                }]);
        };

        $movies = Movie::whereHas('schedules', $scheduleConstraint)
            ->with(['schedules' => $scheduleConstraint])
            ->get();

        // 訪問した劇場をセッション保存
        $visitedTheaterIds = session('visited_theater_ids', []);
        $visitedTheaterIds = array_values(array_diff(
            $visitedTheaterIds,
            [$theater->id],
        ));
        array_unshift($visitedTheaterIds, $theater->id);
        session([
            'visited_theater_ids' => array_slice($visitedTheaterIds, 0, 5),
        ]);

        return view('theaters.schedules', compact('theater', 'date', 'movies', 'dates'));
    }
}

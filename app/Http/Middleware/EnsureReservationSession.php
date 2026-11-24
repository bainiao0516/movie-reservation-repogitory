<?php

namespace App\Http\Middleware;

use App\Models\Reservation;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureReservationSession
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {

        $reservationId = session('reservation_id');

        // 予約セッションが存在しない場合の予約者情報入力画面以降のアクセス拒否
        if (!$reservationId) {
            if (!$request->routeIs('reservations.seats.select', 'reservations.temporary.store')) {
                return redirect()->route('theaters.index', ['ref' => 'session_expired'])
                    ->with('error', '予約セッションが存在しません。最初からお選びください。');
            }

            return $next($request);
        }

        // 予約情報の取得
        $reservation = Reservation::with([
            'schedule.movie',
            'scheduleSeats.seat',
        ])->where('id', $reservationId)->first();

        // 3. 仮予約が存在しない・15分期限切れ・または上映開始時刻を過ぎている場合の予約セッション破棄
        if (!$reservation || !$reservation->isConfirmable()) {
            if ($reservation) {
                $reservation->releaseSeats('expired');
            }

            // セッション破棄
            session()->forget(['reservation_id', 'reservation.customer']);

            $isStarted = $reservation?->isScheduleStarted();
            $errorMessage = $isStarted
            ? '上映開始時刻を過ぎたため、お手続きを中断しました。'
            : '仮予約の有効期限（15分）が切れたため、お手続きを中断しました。最初からお選びください。';

            // 座席選択画面以外からのアクセス時は一覧へ退避
            if (!$request->routeIs('reservations.seats.select')) {
                return redirect()->route('theaters.index')->with('error', $errorMessage);
            }

            session()->flash('error', $errorMessage);

            return $next($request);
        }

        // 券種情報未入力のすり抜け防止
        if ($request->routeIs('reservations.customer.create')) {
            if (!$reservation->total_amount) {
                return redirect()->route('reservations.tickets.select')
                    ->with('error', '券種が選択されてません。選択を行なってください。');
            }
        }

        // 予約者情報未入力のすり抜け防止
        if ($request->routeIs('reservations.confirm.show', 'reservations.complete.store')) {
            if (!session()->has('reservation.customer')) {
                return redirect()->route('reservations.customer.create')
                    ->with('error', '予約者情報が入力されていません。入力を行ってください。');
            }
        }

        $request->attributes->set('reservation', $reservation);

        return $next($request);
    }
}

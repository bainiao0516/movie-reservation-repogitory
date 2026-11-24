<?php

namespace App\Http\Controllers;

use App\Http\Requests\SelectSeatRequest;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\StoreTicketsRequest;
use App\Models\Reservation;
use App\Models\Schedule;
use App\Models\ScheduleSeat;
use App\Models\TicketType;
use App\Services\PaymentService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ReservationController extends Controller
{
    public function selectSeats(Schedule $schedule): View|RedirectResponse
    {
        session(['reservation.schedule_id' => $schedule->id]);

        // 過去の上映枠の予約拒否
        if (!$schedule->isBookable()) {
            return redirect()->route('theaters.index')
                ->with('error', '選択された上映枠の予約受付は終了しています。');
        }

        $currentReservationId = session('reservation_id');
        $currentReservation = Reservation::getCurrentTemporary($currentReservationId);

        if ($currentReservation?->schedule_id !== $schedule->id && $currentReservationId) {

            return redirect()->route('reservations.holding.show');
        }

        $reservedScheduleId = $currentReservation?->schedule_id;

        $seats = ScheduleSeat::with('seat')
            ->join('seats', 'schedule_seats.seat_id', '=', 'seats.id')
            ->where('schedule_seats.schedule_id', $schedule->id)
            ->orderBy('seats.name', 'asc')
            ->select('schedule_seats.*')
            ->get();

        $reservedSeatIds = ScheduleSeat::where('schedule_id', $schedule->id)
            ->where(function ($query) {
                $query->where('status', 'reserved')
                      // 「temporary かつ 期限内」と明示してカッコ内に収める
                    ->orWhere(function ($q) {
                        $q->where('status', 'temporary')
                            ->where('expires_at', '>', now());
                    });
            })
            ->pluck('id')
            ->toArray();

        // 仮予約されたスケジュールの座席画面を開く際、仮予約座席を取得
        $mySeatIds = [];

        if ($currentReservationId && $schedule->id == $reservedScheduleId) {
            $mySeatIds = ScheduleSeat::where('reservation_id', $currentReservationId)
                ->pluck('id')
                ->toArray();
        }

        $reservedSeatIds = array_values(array_diff($reservedSeatIds, $mySeatIds));

        return view('reservations.seats', compact('schedule', 'seats', 'reservedSeatIds', 'mySeatIds'));
    }

    /**
     * 仮予約選択画面の表示
     */
    public function showHolding(): View
    {

        return view('reservations.holding');
    }

    /**
     * 既存の仮予約手続きへ戻る
     */
    public function resumeHolding(): RedirectResponse
    {

        $reservationId = session('reservation_id');
        $currentReservation = Reservation::getCurrentTemporary($reservationId);

        if (!$currentReservation) {
            return redirect()->route('theaters.index');
        }

        return redirect()->route('reservations.seats.select', ['schedule' => $currentReservation->schedule_id]);
    }

    /**
     * 既存の仮予約を破棄して新しい予約へ進む
     */
    public function discardHolding(Request $request): RedirectResponse
    {
        $reservationId = session('reservation_id');
        $currentReservation = Reservation::getCurrentTemporary($reservationId);

        if ($currentReservation) {
            $currentReservation->discardTemporary(); // DBの座席解放
        }

        $newScheduleId = $request->input('target_schedule_id');

        return redirect()->route('reservations.seats.select', ['schedule' => $newScheduleId])
            ->with('info', '以前の仮予約をキャンセルしました。');
    }

    public function storeTemporary(SelectSeatRequest $request): RedirectResponse
    {

        // 1. バリデーション（座席が選択されているかチェック）

        $seatIds = collect((array) $request->validated()['seat_ids'])->sort()->values()->toArray();
        $scheduleId = $request->validated()['schedule_id'];
        // $schedule = Schedule::find($scheduleId);

        // if (!$schedule || !$schedule->isBookable()) {
        //     return redirect()
        //         ->route('theaters.index')
        //         ->with('error', '選択された上映枠の予約受付は終了しています。');
        // }

        $currentReservationId = session('reservation_id');

        try {
            $reservation = DB::transaction(function () use ($scheduleId, $seatIds, $currentReservationId) {

                // 1. 対象の上映枠・座席の ScheduleSeat レコードを悲観的ロック
                $scheduleSeats = ScheduleSeat::where('schedule_id', $scheduleId)
                    ->whereIn('id', $seatIds)
                    ->orderBy('id', 'asc')
                    ->lockForUpdate()
                    ->get();

                // 選択された座席数が不足している場合はエラー処理
                if ($scheduleSeats->count() !== count($seatIds)) {
                    return null;
                }

                // 2. 重複チェック（すでに予約済み、または他人が有効な仮予約中か）
                $isTaken = $scheduleSeats->contains(function ($seat) use ($currentReservationId) {
                    // 完了済みの予約
                    if ($seat->status === 'reserved') {
                        return true;
                    }
                    // 他ユーザーによる有効期限内の仮予約（自セッションの仮予約は除外）
                    if ($seat->status === 'temporary'
                        && $seat->expires_at?->isFuture()
                        && $seat->reservation_id !== $currentReservationId) {
                        return true;
                    }

                    return false;
                });

                if ($isTaken) {
                    return null;
                }

                // 3. 既存の自仮予約があればクリア（古い選択座席の解放）
                if ($currentReservationId) {
                    ScheduleSeat::where('reservation_id', $currentReservationId)
                        ->update([
                            'reservation_id' => null,
                            'status' => 'available',
                            'expires_at' => null,
                        ]);

                    Reservation::where('id', $currentReservationId)
                        ->where('status', 'temporary')
                        ->update([
                            'status' => 'cancelled',
                        ]);
                }

                $expiresAt = now()->addMinutes(15); // 有効期限（必要に応じて調整）

                // 2. 予約データの作成
                $attempts = 0;

                do {
                    try {
                        $tempReservation = Reservation::create([
                            'schedule_id' => $scheduleId,
                            'reservation_code' => strtoupper(Str::random(8)),
                            'status' => 'temporary',
                        ]);
                    } catch (QueryException $e) {
                        if ($e->errorInfo[1] === 1062 && $attempts < 3) {
                            $attempts++;
                            $tempReservation = null;

                            continue;
                        }
                        throw $e;
                    }
                } while (!$tempReservation);

                // 5. ScheduleSeat の状態・紐付けを一括更新
                ScheduleSeat::whereIn('id', $scheduleSeats->pluck('id'))
                    ->update([
                        'reservation_id' => $tempReservation->id,
                        'status' => 'temporary',
                        'expires_at' => $expiresAt,
                    ]);

                return $tempReservation;
            }, 3);

            if (!$reservation) {
                return back()->with('error', '選択した座席はすでに他のお客様に確保されたか、無効です。');
            }

            // セッションに仮予約IDを保持して入力画面へ移動
            session(['reservation_id' => $reservation->id]);

            return redirect()->route('reservations.tickets.select');

        } catch (\Exception $e) {
            Log::error('仮予約処理でエラーが発生しました。', [
                'schedule_id' => $scheduleId,
                'seat_ids' => $seatIds,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', '予期せぬエラーが発生しました。もう一度お試しください。');
        }
    }

    public function selectTickets(Request $request): View
    {
        /** @var Reservation $reservation */
        $reservation = $request->attributes->get('reservation');

        // 1. $reservation に紐づく scheduleSeats と物理座席（seat）を一括取得
        $reservation->load(['scheduleSeats.seat', 'schedule']);

        // 2. 関連する ScheduleSeat レコード一覧を取得
        $reservedSeats = $reservation->scheduleSeats;

        // 3. 物理座席（Seatモデル）の一覧を取り出す場合
        $seats = $reservedSeats->pluck('seat');

        // 4. 画面で選択させるための有効な券種一覧を取得
        $ticketTypes = TicketType::where('is_active', true)
            ->orderBy('display_order')
            ->get();

        return view('reservations.tickets', compact('reservation', 'reservedSeats', 'seats', 'ticketTypes'));
    }

    public function storeTickets(StoreTicketsRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $totalAmount = 0;
        $reservationId = session('reservation_id');

        DB::transaction(function () use ($validated, &$totalAmount, $reservationId) {
            foreach ($validated['tickets'] as $ticket) {
                $scheduleSeat = ScheduleSeat::where('id', $ticket['seat_id'])
                    ->where('reservation_id', $reservationId)
                    ->lockForUpdate()
                    ->firstOrFail();

                $price = TicketType::where('id', $ticket['ticket_type_id'])
                    ->value('price');

                $totalAmount += $price;

                // 該当の予約座席に券種IDを紐付け
                $scheduleSeat
                    ->update([
                        'ticket_type_id' => $ticket['ticket_type_id'],
                        'price' => $price,
                    ]);

            }

            $reservationId = session('reservation_id');

            if (!$reservationId) {
                throw new \Exception('予約情報（セッション）が見つかりません。');
            }

            Reservation::where('id', $reservationId)
                ->update([
                    'total_amount' => $totalAmount,
                ]);
        });

        // 3. お客様情報入力画面 (createCustomer) へリダイレクト
        return redirect()
            ->route('reservations.customer.create')
            ->with('status', '券種を保存しました。お客様情報を入力してください。');
    }

    public function createCustomer(Request $request): View
    {
        $reservation = $request->attributes->get('reservation');
        $customer = session('reservation.customer');

        return view('reservations.customer', compact('reservation', 'customer'));
    }

    public function storeCustomer(StoreCustomerRequest $request): RedirectResponse
    {

        $validated = $request->validated();

        session(['reservation.customer' => $validated]);

        return redirect()->route('reservations.confirm.show');
    }

    public function showConfirm(Request $request): Response
    {
        $reservation = $request->attributes->get('reservation');

        $customer = session('reservation.customer');

        // 確認画面はキャッシュさせない
        return response()
            ->view('reservations.confirm', compact('reservation', 'customer'))
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
    }

    public function storeComplete(Request $request, PaymentService $paymentService): RedirectResponse
    {

        $reservation = $request->attributes->get('reservation');
        $customer = session('reservation.customer');

        // 顧客情報がセッションに存在しない場合のガード
        if (!$customer) {
            return redirect()->route('reservations.customer.create')
                ->with('error', 'お客様情報が取得できませんでした。最初からやり直してください。');
        }

        // 決済直前の排他ロック
        try {
            DB::transaction(function () use ($reservation) {
                $locked = Reservation::where('id', $reservation->id)
                    ->lockForUpdate()
                    ->first();

                if (!$locked || $locked->status !== 'temporary' || !$locked->isConfirmable()) {
                    throw new \Exception('仮予約の期限が切れているか、既に処理中です。');
                }

                $locked->update(['status' => 'processing']);
            });
        } catch (\Exception $e) {
            return redirect()->route('theaters.index')
                ->with('error', '仮予約の有効期限が切れたため、予約を完了できませんでした。');
        }

        // 外部決済処理
        try {
            $isPaid = $paymentService->charge($reservation->total_amount, $customer);
        } catch (\Exception $e) {
            $isPaid = false;
        }

        if (!$isPaid) {
            $reservation->update(['status' => 'temporary']);

            if (!$reservation->isConfirmable()) {
                $reservation->releaseSeats('expired');
                session()->forget(['reservation_id', 'reservation.customer']);

                return redirect()->route('theaters.index')
                    ->with('error', '決済処理に失敗し、仮予約の有効期限も切れたため予約を解除しました。');
            }

            return redirect()->route('reservations.confirm.show')
                ->with('error', '決済処理に失敗しました。お支払い情報をご確認の上、再度お試しください。');
        }

        // DBの更新処理(予約確定)
        try {
            DB::transaction(function () use ($reservation, $customer) {
                $reservation->update(array_merge($customer, [
                    'status' => 'completed',
                ]));

                $reservation->scheduleSeats()->update([
                    'status' => 'reserved',
                    'expires_at' => null,
                ]);
            });
        } catch (\Exception $e) {
            // ログ出力（問題発生時の原因追跡用）
            Log::critical('決済完了後の DB 更新に失敗しました。返金処理を実行します。', [
                'reservation_id' => $reservation->id,
                'error' => $e->getMessage(),
            ]);

            // 返金 API を実行（ログ追跡用に reservation_id を配列で渡す）
            $paymentService->refund($reservation->total_amount, [
                'reservation_id' => $reservation->id,
            ]);

            // 座席解放 & ステータスを 'failed' に更新
            $reservation->releaseSeats('failed');

            // 不整合データを残さないようセッション破棄
            session()->forget(['reservation_id', 'reservation.customer']);

            return redirect()->route('theaters.index')
                ->with('error', 'システムエラーにより予約を完了できませんでした。決済は取り消されました。');
        }

        session()->forget([
            'reservation_id',
            'reservation.customer',
        ]);

        session()->flash('can_access_thanks', true);

        return redirect()
            ->route('reservations.thanks.show')
            ->with('completed_reservation_id', $reservation->id);
    }

    public function showThanks(): View|RedirectResponse
    {
        $reservationId = session('completed_reservation_id');
        $canAccess = session('can_access_thanks');

        if (!$reservationId || !$canAccess) {
            return redirect()->route('theaters.index')
                ->with('error', '不正なアクセスか、セッションの有効期限が切れています。');
        }

        $reservation = Reservation::with([
            'schedule.movie',
            'scheduleSeats.seat',
        ])->find($reservationId);

        return view('reservations.thanks', compact('reservation'));
    }
}

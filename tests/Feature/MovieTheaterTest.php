<?php

namespace Tests\Feature;

use App\Models\Reservation;
use App\Models\Schedule;
use App\Models\ScheduleSeat;
use App\Models\Seat;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationTest extends TestCase
{
    // テスト実行ごとにデータベースをリセット・マイグレーションする
    use RefreshDatabase;

    /**
     * 座席が紐付いた仮予約データを生成
     */
    private function createTemporaryReservationWithSeat(array $reservationAttributes = [], $seatAttributes = [], int $expiresInMinutes = 15): array
    {
        $reservation = Reservation::factory()->create(array_merge([
            'name' => null,
            'tel' => null,
            'email' => null,
            'total_amount' => 1900,
            'status' => 'temporary',
        ], $reservationAttributes));

        $scheduleSeat = ScheduleSeat::factory()->create(array_merge([
            'schedule_id' => $reservation->schedule_id,
            'reservation_id' => $reservation->id,
            'status' => 'temporary',
            'expires_at' => now()->addMinutes($expiresInMinutes),
        ], $seatAttributes));

        return [$reservation, $scheduleSeat];
    }

    /**
     * テスト用顧客データの生成
     */
    private function validCustomerData(array $attributes = []): array
    {
        return array_merge([
            'name' => 'テスト太郎',
            'tel' => '09012345678',
            'email' => 'test@example.com',
        ], $attributes);
    }

    /**
     * 予約受付可能なスケジュールと空席を生成
     */
    private function createBookableScheduleWithSeat(array $scheduleAttributes = []): array
    {
        $schedule = Schedule::factory()->create(array_merge([
            'start_at' => now()->addHours(2),
            'end_at' => now()->addHours(4),
        ], $scheduleAttributes));

        $seat = Seat::factory()->create(['screen_id' => $schedule->screen_id]);

        $scheduleSeat = ScheduleSeat::factory()->create([
            'schedule_id' => $schedule->id,
            'seat_id' => $seat->id,
            'status' => 'available',
        ]);

        return [$schedule, $seat, $scheduleSeat];
    }

    /**
     * 正常系: 座席を選択して仮予約ができること
     */
    public function test_user_can_create_temporary_reservation(): void
    {
        [$schedule, , $scheduleSeat] = $this->createBookableScheduleWithSeat();

        // 2. Act (実行): 対象のエンドポイントへHTTPリクエストを送信
        $response = $this->post(route('reservations.temporary.store'), [
            'schedule_id' => $schedule->id,
            'seat_ids' => [$scheduleSeat->id],
        ]);

        // 仮予約の作成を確認
        $response->assertRedirect(route('reservations.tickets.select'));
        $this->assertDatabaseHas('reservations', [
            'schedule_id' => $schedule->id,
            'status' => 'temporary',
        ]);
        $response->assertSessionHas('reservation_id');
    }

    public function test_user_can_create_complete_reservation(): void
    {
        // 仮予約の作成
        [$reservation] = $this->createTemporaryReservationWithSeat();

        $response = $this->withSession([
            'reservation_id' => $reservation->id,
        ])
            ->post(route('reservations.customer.store'), $this->validCustomerData());

        // バリデーションエラーが起きていないか確認
        $response->assertSessionHasNoErrors();

        // 確認画面へのリダイレクト確認
        $response->assertRedirect(route('reservations.confirm.show'));

        // 完了処理
        $response = $this->put(route('reservations.complete.store'));

        // 本予約完了テスト
        $this->assertDatabaseHas('reservations', array_merge([
            'id' => $reservation->id,
            'status' => 'completed',
        ], $this->validCustomerData()));
        $response->assertRedirect(route('reservations.thanks.show'));
        $response->assertSessionMissing('reservation_id');
    }

    public function test_cannot_select_already_reserved_seat(): void
    {
        [$schedule, , $scheduleSeat] = $this->createBookableScheduleWithSeat();

        [$reservation, $scheduleSeat] = $this->createTemporaryReservationWithSeat(['schedule_id' => $schedule->id]);

        // 別のユーザーが同じ座席を直接指定してリクエスト
        $response = $this->post(route('reservations.temporary.store'), [
            'schedule_id' => $schedule->id,
            'seat_ids' => [$scheduleSeat->id],
        ]);

        // 予約失敗（エラーメッセージ保持、またはリダイレクト）
        $response->assertSessionHas('error', '選択した座席はすでに他のお客様に確保されたか、無効です。');
    }

    public function test_cannot_confirm_expired_reservation(): void
    {
        // 仮予約の作成
        [$reservation, $scheduleSeat] = $this->createTemporaryReservationWithSeat([], [
            'expires_at' => now()->subMinutes(16),
        ]);

        $response = $this->withSession([
            'reservation_id' => $reservation->id,
        ])
            ->post(route('reservations.customer.store'), $this->validCustomerData());

        // 予約不可の確認
        $response->assertRedirect(route('theaters.index'));
        $response->assertSessionHas('error', '仮予約の有効期限（15分）が切れたため、お手続きを中断しました。最初からお選びください。');
        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => 'expired',
        ]);
    }

    public function test_cannot_select_untargeted_seats(): void
    {

        [$schedule, $seat] = $this->createBookableScheduleWithSeat();

        [$otherSchedule] = $this->createBookableScheduleWithSeat();

        $otherScheduleSeat = ScheduleSeat::factory()->create([
            'seat_id' => $seat->id,
            'schedule_id' => $otherSchedule->id,
        ]);

        $response = $this->post(route('reservations.temporary.store'), [
            'schedule_id' => $schedule->id,
            'seat_ids' => [$otherScheduleSeat->id],
        ]);

        // 仮予約不可の確認
        $response->assertSessionHas('error', '選択した座席はすでに他のお客様に確保されたか、無効です。');
    }

    public function test_redirects_to_confirm_when_payment_fails(): void
    {
        // PaymentService のモック作成（charge が false を返す）
        $this->mock(PaymentService::class, function ($mock) {
            $mock->shouldReceive('charge')->once()->andReturn(false);
        });

        [$reservation] = $this->createTemporaryReservationWithSeat();

        $response = $this->withSession([
            'reservation_id' => $reservation->id,
            'reservation.customer' => $this->validCustomerData(),
        ])->put(route('reservations.complete.store'));

        // 決済の失敗とリダイレクトの確認
        $response->assertRedirect(route('reservations.confirm.show'));
        $response->assertSessionHas('error', '決済処理に失敗しました。お支払い情報をご確認の上、再度お試しください。');
    }

    public function test_refunds_payment_and_releases_seats_if_db_update_fails(): void
    {
        $this->mock(PaymentService::class, function ($mock) {
            $mock->shouldReceive('charge')->once()->andReturn(true);
            $mock->shouldReceive('refund')->once();
        });

        [$reservation, $scheduleSeat] = $this->createTemporaryReservationWithSeat();

        // DB書き込み処理中に例外エラーが起こるようにする
        Reservation::updating(function (Reservation $res) {
            if ($res->status === 'completed') {
                throw new \Exception('DB書き込み疑似エラー');
            }
        });

        $response = $this->withSession([
            'reservation_id' => $reservation->id,
            'reservation.customer' => [
                $this->validCustomerData(),
            ],
        ])->put(route('reservations.complete.store'));

        // 適切なエラーとセッション破棄、DB変更を確認
        $response->assertRedirect(route('theaters.index'));
        $response->assertSessionHas('error', 'システムエラーにより予約を完了できませんでした。決済は取り消されました。');
        $response->assertSessionMissing('reservation_id');
        $response->assertSessionMissing('reservation.customer');
        $this->assertDatabaseHas('schedule_seats', [
            'id' => $scheduleSeat->id,
            'reservation_id' => null,
            'status' => 'available',
        ]);
    }

    public function test_redirects_to_holding_when_accessing_different_schedule_with_reservation_held(): void
    {
        $scheduleA = Schedule::factory()->create([
            'start_at' => now()->addHours(2),
            'end_at' => now()->addHours(4),
        ]);

        $scheduleB = Schedule::factory()->create([
            'start_at' => now()->addHours(5),
            'end_at' => now()->addHours(7),
        ]);

        $reservation = Reservation::factory()->create([
            'schedule_id' => $scheduleA->id,
            'name' => null,
            'tel' => null,
            'email' => null,
            'total_amount' => 1900,
            'status' => 'temporary',
        ]);

        ScheduleSeat::factory()->create([
            'schedule_id' => $reservation->schedule_id,
            'reservation_id' => $reservation->id,
            'status' => 'temporary',
            'expires_at' => now()->addMinutes(16),
        ]);

        ScheduleSeat::factory()->create([
            'schedule_id' => $scheduleB->id,
            'reservation_id' => $reservation->id,
            'status' => 'temporary',
            'expires_at' => now()->addMinutes(16),
        ]);

        $response = $this->withSession([
            'reservation_id' => $reservation->id,
            'reservation.theater' => $scheduleB->screen->theater_id,
            'reservation.date' => $scheduleB->start_at->toDateString(),
        ])->get(route('reservations.seats.select', ['schedule' => $scheduleB->id]));

        $response->assertRedirect(route('reservations.holding.show'));

    }

    public function test_discard_holding_reservation_and_redirects_to_new_seats_index(): void
    {
        $seat = Seat::factory()->create();

        $scheduleA = Schedule::factory()->create([
            'screen_id' => $seat->screen->id,
        ]);

        $reservation = Reservation::factory()->create([
            'schedule_id' => $scheduleA->id,
            'name' => null,
            'tel' => null,
            'email' => null,
            'total_amount' => 1900,
            'status' => 'temporary',
        ]);

        $scheduleSeat = ScheduleSeat::factory()->create([
            'schedule_id' => $reservation->schedule_id,
            'reservation_id' => $reservation->id,
            'status' => 'temporary',
            'expires_at' => now()->addMinutes(16),
        ]);

        $scheduleB = Schedule::factory()->create([
            'start_at' => now()->addHours(5),
            'end_at' => now()->addHours(7),
        ]);

        ScheduleSeat::factory()->create([
            'schedule_id' => $scheduleB->id,
            'reservation_id' => $reservation->id,
            'status' => 'temporary',
            'expires_at' => now()->addMinutes(16),
        ]);

        $response = $this->withSession([
            'reservation_id' => $reservation->id,
            'reservation.theater' => $scheduleB->screen->theater_id,
            'reservation.date' => $scheduleB->start_at->toDateString(),
        ])->delete(route('reservations.holding.discard'), ['target_schedule_id' => $scheduleB->id]);

        $response->assertRedirect(route('reservations.seats.select', $scheduleB->id));

        $this->assertDatabaseHas('schedule_seats', [
            'id' => $scheduleSeat->id,
            'status' => 'available',
        ]);
    }

    public function test_cannot_access_seats_page_for_unbookable_schedule(): void
    {
        // 過去日時のスケジュール
        $schedule = Schedule::factory()->create([
            'start_at' => now()->subHours(2),
        ]);

        $response = $this->withSession([
            'reservation.theater' => $schedule->screen->theater_id,
            'reservation.date' => $schedule->start_at->toDateString(),
        ])->get(route('reservations.seats.select', ['schedule' => $schedule->id]));

        $response->assertRedirect(route('theaters.index'));
        $response->assertSessionHas('error', '選択された上映枠の予約受付は終了しています。');
    }
}

<?php

namespace Tests\Unit\Models;

use App\Models\Reservation;
use App\Models\Schedule;
use App\Models\ScheduleSeat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * isConfirmable: 異常系（座席不足・有効期限切れ・上映開始済み）で確定不可（false）になること
     */
    public function test_is_confirmable_returns_false_for_invalid_conditions(): void
    {
        // 1. 座席が紐付いていない場合
        $noSeatRes = Reservation::factory()->create(['status' => 'temporary']);
        $this->assertFalse($noSeatRes->isConfirmable());

        // 2. 座席の有効期限が切れている場合
        $expiredRes = Reservation::factory()->create(['status' => 'temporary']);
        ScheduleSeat::factory()->create([
            'reservation_id' => $expiredRes->id,
            'expires_at' => now()->subMinute(),
        ]);
        $expiredRes->load('scheduleSeats');
        $this->assertFalse($expiredRes->isConfirmable());

        // 3. すでに上映が開始されている場合
        $pastSchedule = Schedule::factory()->create(['start_at' => now()->subMinute()]);
        $startedRes = Reservation::factory()->create([
            'schedule_id' => $pastSchedule->id,
            'status' => 'temporary',
        ]);
        ScheduleSeat::factory()->create([
            'schedule_id' => $pastSchedule->id,
            'reservation_id' => $startedRes->id,
            'expires_at' => now()->addMinutes(15),
        ]);
        $this->assertFalse($startedRes->isConfirmable());
    }

    /**
     * isConfirmable: 正常系（処理中ステータス、または有効期限内の仮予約）で確定可能（true）になること
     */
    public function test_is_confirmable_returns_true_for_valid_conditions(): void
    {
        // 1. ステータスが processing の場合
        $processingRes = Reservation::factory()->create(['status' => 'processing']);
        $this->assertTrue($processingRes->isConfirmable());

        // 2. 上映前 & 座席期限内の場合
        $futureSchedule = Schedule::factory()->create(['start_at' => now()->addHour()]);
        $validRes = Reservation::factory()->create([
            'schedule_id' => $futureSchedule->id,
            'status' => 'temporary',
        ]);
        ScheduleSeat::factory()->create([
            'schedule_id' => $futureSchedule->id,
            'reservation_id' => $validRes->id,
            'expires_at' => now()->addMinutes(15),
        ]);
        $this->assertTrue($validRes->isConfirmable());
    }

    /**
     * discardTemporary: 仮予約の破棄時に座席解放とセッション消去が同時に正しく行われること
     */
    public function test_discard_temporary_resets_seats_and_session(): void
    {
        $reservation = Reservation::factory()->create(['status' => 'temporary']);
        $scheduleSeat = ScheduleSeat::factory()->create([
            'reservation_id' => $reservation->id,
            'status' => 'temporary',
            'expires_at' => now()->addMinutes(15),
        ]);

        session(['reservation_id' => $reservation->id]);

        // 破棄処理の実行
        $reservation->discardTemporary();

        // 座席が空席にリセットされていること
        $this->assertDatabaseHas('schedule_seats', [
            'id' => $scheduleSeat->id,
            'reservation_id' => null,
            'status' => 'available',
            'expires_at' => null,
        ]);

        // 予約データおよびセッションが削除されていること
        $this->assertDatabaseMissing('reservations', ['id' => $reservation->id]);
        $this->assertFalse(session()->has('reservation_id'));
    }
}

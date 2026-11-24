<?php

namespace App\Models;

use Database\Factories\ReservationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Reservation extends Model
{
    /** @use HasFactory<ReservationFactory> */
    use HasFactory;

    protected $fillable = [
        'schedule_id',
        'reservation_code',
        'name',
        'tel',
        'email',
        'total_amount',
        'status',
    ];

    /**
     * @return BelongsTo<Schedule, $this>
     */
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    /**
     * @return HasMany<ScheduleSeat, $this>
     */
    public function scheduleSeats(): HasMany
    {
        return $this->hasMany(ScheduleSeat::class);
    }

    /**
     * @return BelongsTo<TicketType, $this>
     */
    public function ticketType(): BelongsTo
    {
        return $this->belongsTo(TicketType::class);
    }

    // 予約処理の継続判定
    public function isConfirmable(): bool
    {
        if ($this->status === 'processing') {
            return true;
        }

        $firstSeat = $this->scheduleSeats->first();
        if (!$firstSeat || !$firstSeat->expires_at?->isFuture()) {
            return false;
        }

        if ($this->isScheduleStarted()) {
            return false;
        }

        return true;
    }

    // 上映時刻開始時刻の判定
    public function isScheduleStarted(): bool
    {
        return $this->schedule && now()->gte($this->schedule->start_at);
    }

    public function releaseSeats(string $status = 'expired'): void
    {
        DB::transaction(function () use ($status) {
            // 1. 紐づく座席を空席に戻す
            $this->scheduleSeats()->update([
                'status' => 'available',
                'expires_at' => null,
                'reservation_id' => null,
            ]);

            // 2. 予約自身のステータスを更新
            $this->update(['status' => $status]);
        });
    }

    public static function getCurrentTemporary(?int $currentReservationId): ?self
    {
        if (!$currentReservationId) {
            return null;
        }

        return self::where('id', $currentReservationId)
            ->where('status', 'temporary')
            ->first();
    }

    /**
     * 仮予約を破棄して座席を解放する
     */
    public function discardTemporary(): void
    {
        DB::transaction(function () {
            // 1. 紐づく schedule_seats のステータスをリセット
            $this->scheduleSeats()->update([
                'reservation_id' => null,
                'status' => 'available',
                'expires_at' => null,
            ]);

            // 2. 予約レコード自体を削除（または status = 'cancelled'）
            $this->delete();

            $currentReservationId = session('reservation_id');

            if ($currentReservationId) {
                session()->forget('reservation_id');
            }
        });
    }
}

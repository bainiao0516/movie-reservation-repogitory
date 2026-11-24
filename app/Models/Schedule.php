<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Schedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'screen_id',
        'movie_id',
        'start_at',
        'end_at',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];

    public function screen(): BelongsTo
    {
        return $this->belongsTo(Screen::class);
    }

    public function movie(): BelongsTo
    {
        return $this->belongsTo(Movie::class);
    }

    public function scheduleSeats(): HasMany
    {
        return $this->hasMany(ScheduleSeat::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public const BOOKING_CUTOFF_MINUTES = 15;

    /**
     * 満席判定
     */
    public function isFull(): bool
    {
        $reservationId = session('reservation_id');

        if ($reservationId) {
            $mySeats = $this->scheduleSeats()->where('reservation_id', $reservationId)
                ->where('status', 'temporary')
                ->where('expires_at', '>', now())
                ->count();
        }

        $totalSeats = $this->schedule_seats_count ?? $this->scheduleSeats()->count();

        // 総座席数が0、または残席数が0以下の場合は満席
        if ($totalSeats === 0) {
            return true;
        }

        $remainingSeats = $totalSeats - ($this->reserved_seats_count ?? 0) + ($mySeats ?? 0);

        return $remainingSeats <= 0;
    }

    /**
     * 予約可能かどうかを判定
     */
    public function isBookable(): bool
    {
        if (!$this->start_at) {
            return false;
        }

        // 満席の場合は予約不可
        if ($this->isFull()) {
            return false;
        }

        // 上映開始時間の15分前を超えているか確認
        return $this->start_at->gt(now()->addMinutes(self::BOOKING_CUTOFF_MINUTES));
    }

    /**
     * 混雑度アイコンのアクセサ
     */
    protected function congestionIcon(): Attribute
    {
        return Attribute::make(
            get: function () {
                // 満席判定を共通利用
                if ($this->isFull()) {
                    return '×';
                }

                $totalSeats = $this->schedule_seats_count ?? 0;
                $remainingSeats = $totalSeats - ($this->reserved_seats_count ?? 0);
                $rate = $remainingSeats / $totalSeats;

                return match (true) {
                    $rate <= 0.2 => '△', // 残席率20%以下
                    default => '◎',
                };
            }
        );
    }
}

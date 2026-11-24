<div class="purchase-detail">
    <h3 class="purchase-detail__heading">ご購入内容</h3>

    <dl class="purchase-detail__list">
        <div class="purchase-detail__item">
            <dt class="purchase-detail__label">作品名</dt>
            <dd class="purchase-detail__value purchase-detail__title">
                {{ $reservation->schedule?->movie?->title ?? '作品情報なし' }}
            </dd>
        </div>

        <div class="purchase-detail__item">
            <dt class="purchase-detail__label">座席</dt>
            <dd class="purchase-detail__value purchase-detail__reserved-seats">
                @foreach ($reservation->scheduleSeats as $scheduleSeat)
                    <p>
                        <span>{{ $scheduleSeat->seat?->name ?? '座席不明' }}</span>
                        <span>{{ $scheduleSeat->ticketType?->name ?? '券種未設定' }}</span>
                    </p>
                @endforeach
            </dd>
        </div>

        <div class="purchase-detail__item">
            <dt class="purchase-detail__label">金額</dt>
            <dd class="purchase-detail__value purchase-detail__price">
                {{ number_format($reservation->total_amount ?? 0) }}円
            </dd>
        </div>

        <div class="purchase-detail__item">
            <dt class="purchase-detail__label">日時</dt>
            <dd class="purchase-detail__value purchase-detail__schedule">
                @if ($reservation->schedule?->start_at && $reservation->schedule?->end_at)
                    {{ $reservation->schedule->start_at->format('Y/m/d H:i') }} 〜 {{ $reservation->schedule->end_at->format('H:i') }}
                @else
                    日時未定
                @endif
            </dd>
        </div>
    </dl>
</div>

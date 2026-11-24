@extends('layouts.app')
@section('title', '券種の選択')
@section('content')
<main>
    <div class="wrapper">
        <h2 class='heading'>券種を選択してください</h2>
        <p class="screen"></p>

        <div id="js-error-message" class='error'></div>

        <div class="content">
            <dl class="purchase-detail__list">
                <div class="purchase-detail__item">
                    <dt class="purchase-detail__label">作品名</dt>
                    <dd class="purchase-detail__value purchase-detail__title">
                        {{ $reservation->schedule?->movie?->title ?? '作品情報なし' }}
                    </dd>
                </div>
                <div class="purchase-detail__item">
                    <dt class="purchase-detail__label">金額</dt>
                    <dd class="purchase-detail__value purchase-detail__price" id='js-total-price'>
                        0円
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
            <form method='POST' action="{{ route('reservations.tickets.store') }}" class="ticket-form" id='ticket-form'>
                @csrf
                @foreach($reservedSeats as $index => $reservedSeat)
                    <div class="ticket-form__item">
                        <div class="ticket-form__label">
                            <img src="{{ asset('seat-icon.svg') }}" alt="">
                            <span>{{ $reservedSeat->seat->name }}</span>
                        </div>

                        <input type="hidden" name="tickets[{{ $index }}][seat_id]" value="{{ $reservedSeat->id }}">
                        <input type="hidden" name="tickets[{{ $index }}][ticket_type_id]" class="js-ticket-type-input" value="">

                        <div class="ticket-form__button">
                            <button type='button'>券種を選択してください</button>
                        </div>
                    </div>
                @endforeach

                {{-- 送信ボタン --}}
                <div class="ticket-form__submit">
                    <button type="submit" disabled>次へ進む</button>
                </div>

                {{-- モーダル --}}
                <div class="ticket-form-modal" id='ticket-form-modal'>
                    <h3 class='ticket-form-modal__title'>券種を選択してください</h3>
                    <div class="ticket-form-modal__content">
                        @foreach($ticketTypes as $type)
                            <div class="ticket-form-modal__item">
                                <button type='button'
                                    class='ticket-form-modal__button'
                                    data-id='{{ $type->id }}'
                                    data-name='{{ $type->name }}'
                                    data-price='{{ $type->price }}'
                                >
                                    <span>{{ $type->name }}</span>
                                    <span>{{ number_format($type->price) }}円
                                    </span>
                                </button>
                            </div>
                        @endforeach
                    </div>
                    <button type='button' class='ticket-form-modal__close'></button>
                </div>
                <div class="ticket-form-mask" id='ticket-form-mask'></div>
            </form>
        </div>
    </div>
</main>
<script>
    const totalPriceEl = document.getElementById('js-total-price');

    function calculateTotal() {
        let total = 0;

        // 全ての券種入力要素（.js-ticket-type-input）をチェック
        document.querySelectorAll('.ticket-form__item').forEach(item => {
            const input = item.querySelector('.js-ticket-type-input');
            const selectedTypeId = input.value;

            if (selectedTypeId) {
                // モーダルボタンの data-price 属性から価格を取得
                const modalBtn = document.querySelector(`.ticket-form-modal__button[data-id="${selectedTypeId}"]`);
                if (modalBtn) {
                    total += Number(modalBtn.dataset.price);
                }
            }
        });

        // 3桁カンマ区切りで画面描画
        totalPriceEl.textContent = total.toLocaleString() + '円';
    }


    const modal = document.querySelector('#ticket-form-modal');
    const mask = document.querySelector('#ticket-form-mask');
    const selectButtons = document.querySelectorAll('.ticket-form__button button');
    const ticketOptions = document.querySelectorAll('.ticket-form-modal__button');
    const form = document.querySelector('#ticket-form');
    const closeBtn = document.querySelector('.ticket-form-modal__close');

    let currentItem = null;

    const updateSubmitState = () => {
        if (!form) return false;

        const hiddenInputs = form.querySelectorAll('.js-ticket-type-input');
        const submitBtn = form.querySelector('.ticket-form__submit button');
        const allSelected = hiddenInputs.length > 0 && [...hiddenInputs].every(input => input.value);

        if (submitBtn) {
            submitBtn.disabled = !allSelected;
        }

        return allSelected;
    };

    // 1. モーダルを開く
    selectButtons.forEach(button => {
        button.addEventListener('click', (e) => {
            currentItem = e.target.closest('.ticket-form__item');
            openModal();
        });
    });

    // 2. モーダルを閉じる関数
    const openModal = () => {
        modal.classList.add('is-active');
        mask.classList.add('is-active');
        document.body.classList.add('is-modal-open');
    };

    const closeModal = () => {
        modal.classList.remove('is-active');
        mask.classList.remove('is-active');
        document.body.classList.remove('is-modal-open');
        currentItem = null;
    };

    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    if (mask) mask.addEventListener('click', closeModal);

    ticketOptions.forEach(option => {
        option.addEventListener('click', () => {
            if (!currentItem) return;

            const typeId = option.dataset.id;
            const typeName = option.dataset.name;
            const typePrice = Number(option.dataset.price).toLocaleString();

            // hidden input の値をセット
            const hiddenInput = currentItem.querySelector('.js-ticket-type-input');
            if (hiddenInput) {
                hiddenInput.value = typeId;
            }

            // 座席のボタン表示テキストを変更
            const button = currentItem.querySelector('.ticket-form__button button');
            if (button) {
                button.textContent = `${typeName} : ${typePrice}円`;
                button.classList.add('is-selected'); // スタイル変更用クラス
            }

            updateSubmitState();
            calculateTotal();

            closeModal();
        });
    });

    if (form) {
        form.addEventListener('input', updateSubmitState);
        form.addEventListener('change', updateSubmitState);

        form.addEventListener('submit', (e) => {
            if (!updateSubmitState()) {
                e.preventDefault();
                alert('すべての座席の券種を選択してください');
            }
        });

        updateSubmitState();
    }


</script>
@endsection

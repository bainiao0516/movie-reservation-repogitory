@extends('layouts.app')
@section('title', '座席の選択')
@section('content')
<main>
    <div class="wrapper">
        <h2 class='heading'>座席を選択してください</h2>
        <p class="screen">{{$schedule->screen->name}}</p>

        <div id="js-error-message" class='error'></div>

        <div class="content">

            <form method='POST' action="{{ route('reservations.temporary.store', $schedule) }}" class="seat-form" id='seat-form'>
                @csrf
                <input type="hidden" name='schedule_id' value='{{ $schedule->id }}'>
                <ul class="seats-list">
                    {{-- 1. 列番号ヘッダー (1 〜 8) --}}
                    <li class="seats-list__item seats-list__empty"></li>
                    @for ($i = 1; $i <= 8; $i++)
                        <li class="seats-list__item seats-list__number">
                            {{ $i }}
                        </li>
                    @endfor

                    {{-- 2. 行ラベルと座席の表示 (8席ごとに分割) --}}
                    @php
                        $rowNumbers = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
                    @endphp

                    @foreach ($seats->chunk(8) as $rowIndex => $seatRow)
                        {{-- 行記号 (A, B, C...) --}}
                        <li class="seats-list__item seats-list__number">
                            {{ $rowNumbers[$rowIndex] ?? '' }}
                        </li>

                        {{-- 各行の座席 (8席分) --}}
                        @foreach ($seatRow as $seat)
                            <li class="seats-list__item seats-list__seat">
                                @if (in_array($seat->id, $reservedSeatIds))
                                    <span class="seats-list__reserved"></span>
                                @else
                                    <label class="seats-list__label">
                                        <input class="seats-list__checkbox" type="checkbox" name="seat_ids[]" value="{{ $seat->id }}"
                                            {{ in_array($seat->id, $mySeatIds) ? 'checked' : '' }}>
                                        <span class='seats-list__empty'></span>
                                    </label>
                                @endif
                            </li>
                        @endforeach
                    @endforeach
                </ul>
                <ul class="seat-status__list">
                    <li class="seat-status__item seat-status__item--empty">
                        <span class="seat-status__icon"></span>
                        <span>空席（選択可能）</span>
                    </li>
                    <li class="seat-status__item seat-status__item--reserved">
                        <span class="seat-status__icon"></span>
                        <span>予約済み</span>
                    </li>
                    <li class="seat-status__item seat-status__item--selected">
                        <span class="seat-status__icon"></span>
                        <span>選択中</span>
                    </li>
                </ul>

                <div id="seat-icons-container" class="seat-count">
                    @for($i = 0; $i < 6; $i++)
                        <img src="{{ asset('seat-icon.svg') }}" alt="座席アイコン" class="seat-icon-item">
                    @endfor
                </div>

                <div class="seat-submit">
                    <button type='submit' class="seat-submit__btn" id='submit-button' disabled>
                        <span id='button-text'>選択を完了する</span>
                        <span id="button-spinner">処理中...</span>
                    </button>
                </div>
            </form>
        </div>

    </div>
</main>
<script>
document.addEventListener('DOMContentLoaded', () => {

    const form = document.querySelector('.seat-form');
    const checkboxes = document.querySelectorAll('.seats-list__checkbox');
    const maxSeats = 6;

    // アイコン一覧を取得
    const iconImgs = document.querySelectorAll('.seat-icon-item');
    const redIconUrl = "{{ asset('seat-icon-blue.svg') }}";
    const defaultIconUrl = "{{ asset('seat-icon.svg') }}";
    const submitBtn = document.getElementById('submit-button')

    // 座席アイコンの表示更新処理
    function updateSeatIcons(selectedCount) {
        iconImgs.forEach((img, index) => {
            // 選択された数だけ赤色アイコンに変更（または残り枠数を赤にする場合はロジックを調整）
            if (index < selectedCount) {
                img.src = redIconUrl;
            } else {
                img.src = defaultIconUrl;
            }
        });
    }

    // チェックボックス変更時のイベント
    checkboxes.forEach(cb => {
        cb.addEventListener('change', () => {
            const checkedBoxes = Array.from(checkboxes).filter(c => c.checked);

            if (checkedBoxes.length > maxSeats) {
                cb.checked = false;
                alert(`一度に選択できる座席は最大${maxSeats}席までです。`);
                return;
            }

            if (checkedBoxes.length > 0) {
                submitBtn.disabled = false;
            } else {
                submitBtn.disabled = true;
            }

            // リアルタイムでアイコン表示を更新
            updateSeatIcons(checkedBoxes.length);
        });
    });

    // 初期表示時にも一度実行（ブラウザバック等の状態で選択状態が残っている場合対策）
    const initialCheckedCount = Array.from(checkboxes).filter(c => c.checked).length;
    updateSeatIcons(initialCheckedCount);

    form.addEventListener('submit', (e) => {
        const checkedBoxes = Array.from(checkboxes).filter(c => c.checked);

        if (checkedBoxes.length === 0) {
            e.preventDefault();
            alert('座席を1席以上選択してください。');
            return;
        }
    });
});
</script>
@endsection

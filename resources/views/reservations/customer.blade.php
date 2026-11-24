@extends('layouts.app')
@section('title', '予約者情報の入力')
@section('content')
<main>
    <div class="wrapper">
        <h2 class='heading'>
            ご購入内容を確認の上、<br>
            予約者情報を入力してください
        </h2>
        <div class="content">
            @include('reservations.partials.purchase-detail')
            <form method='POST' action='{{route('reservations.customer.store')}}' class='purchase-form' novalidate>
                @csrf
                <h3>予約者情報</h3>
                <div class="purchase-form__field">
                    <label for="name" class="purchase-form__label">氏名</label>
                    <input class='purchase-form__input' type="text" id='name' name='name' value='{{old('name', $customer['name'] ?? '')}}' maxlength='255' placeholder='氏名' required>
                </div>
                <div class="purchase-form__field">
                    <label for="tel" class="purchase-form__label">電話番号</label>
                    <input class='purchase-form__input' type="tel" id='tel' name='tel' value='{{old('tel', $customer['tel'] ?? '')}}' maxlength='20' placeholder='09012345678' required>
                </div>
                <div class="purchase-form__field">
                    <label for="email" class="purchase-form__label">メールアドレス</label>
                    <input class='purchase-form__input' type="email" id='email' name='email' value='{{old('email', $customer['email'] ?? '')}}' maxlength='255' placeholder='yoho.cinema@eiga.com' required>
                </div>
                <div class="purchase-form__submit">
                    <button type='submit' class="purchase-form__button" disabled>
                        次へ
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.querySelector('.purchase-form');
        if (!form) return;

        // 必須入力の input 要素を取得
        const inputs = form.querySelectorAll('.purchase-form__input');
        const submitBtn = document.querySelector('.purchase-form__button');

        // すべての入力項目が空でないか判定する関数
        function toggleSubmitButton() {
            // すべての input に値（空白除く）が入っていれば true
            const allFilled = Array.from(inputs).every(input => input.value.trim() !== '');

            // 全て入力されていれば disabled を解除、未入力があれば disabled を付与
            submitBtn.disabled = !allFilled;
        }

        // 各 input の入力変更時に判定を実行
        inputs.forEach(input => {
            input.addEventListener('input', toggleSubmitButton);
        });

        // 初期表示時（old値などがすでに入っている場合）にも実行
        toggleSubmitButton();
    });
</script>
@endsection

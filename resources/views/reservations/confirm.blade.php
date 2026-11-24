@extends('layouts.app')
@section('title', '予約者情報の入力')
@section('content')
<main>
    <div class="wrapper">
        <h2 class='heading'>
            入力内容を確認し、<br>
            よろしければ購入を確定してください
        </h2>
        <div class="content">
            @include('reservations.partials.purchase-detail')
            <form id='purchase-form'  method='POST' action='{{route('reservations.complete.store')}}' class='purchase-form'>
                @csrf
                @method('PUT')
                <h3>予約者情報</h3>
                <div class="purchase-form__field">
                    <p>氏名</p>
                    <p>{{ $customer['name'] }}</p>
                </div>
                <div class="purchase-form__field">
                    <p>電話</p>
                    <p>{{ $customer['tel'] }}</p>
                </div>
                <div class="purchase-form__field">
                    <p>メールアドレス</p>
                    <p>{{ $customer['email'] }}</p>
                </div>
                <div class="purchase-form__submit">
                    <button class="purchase-form__button" id='submit-button'>
                        <span id='button-text'>購入を確定する</span>
                        <span id="button-spinner">処理中...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>
<script>
    document.getElementById('purchase-form').addEventListener('submit', function(e) {

        const button = document.getElementById('submit-button');
        const text = document.getElementById('button-text');
        const spinner = document.getElementById('button-spinner');

        // 二重送信の防止（すでに無効化されている場合は送信をキャンセル）
        if (button.disabled) {
            e.preventDefault();
            return false;
        }

        // ボタンを無効化し、表示を「処理中...」に変更
        button.disabled = true;
        button.classList.add('opacity-50', 'cursor-not-allowed');
        text.style.display = 'none';
        spinner.style.display = 'inline-flex';
    });
</script>
@endsection

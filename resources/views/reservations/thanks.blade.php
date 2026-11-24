@extends('layouts.app')
@section('title', '予約者情報の入力')
@section('content')
<main>
    <div class="wrapper">
        <h2 class='heading'>
            チケットの購入が完了しました<br>
            ご来場をお待ちしております
        </h2>
        <div class="content">
            <a class='next-reservation-button' href="{{ route('theaters.index') }}">次の予約を始める</a>
            @include('reservations.partials.purchase-detail')
            <div id='purchase-form'  class='purchase-form'>
                <h3>予約者情報</h3>
                <div class="purchase-form__field">
                    <p>氏名</p>
                    <p>{{ $reservation->name }}</p>
                </div>
                <div class="purchase-form__field">
                    <p>電話</p>
                    <p>{{ $reservation->tel }}</p>
                </div>
                <div class="purchase-form__field">
                    <p>メールアドレス</p>
                    <p>{{ $reservation->email }}</p>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection
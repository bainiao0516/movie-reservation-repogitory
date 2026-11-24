@extends('layouts.app')
@section('title', '仮予約の選択')
@section('content')
<main>
    <div class="wrapper">
        <h2 class='heading'>
            座席の仮押さえがあります
        </h2>
        <p>現在、別の上映枠で仮予約が完了していません。</p>
        <div class="content">
            {{-- 選択肢１: 保持して既存予約に進む --}}
            <div class="submit">
                <a class="submit__button" href='{{ route('reservations.holding.resume') }}'>現在の仮予約手続きに進む</a>
            </div>

            <!-- 選択肢2: 破棄して新規作成に進む -->
            <form class='conflict-form' action="{{ route('reservations.holding.discard') }}" method="POST">
                @csrf
                @method('DELETE')
                <input type="hidden" name="target_schedule_id" value="{{ session('reservation.schedule_id')}}">
                <div class="submit">
                    <button type="submit" class="submit__button" onclick="return confirm('既存の仮予約は無効になります。よろしいですか？')">
                        前の仮予約を破棄して、この上映枠を選択する
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>
@endsection

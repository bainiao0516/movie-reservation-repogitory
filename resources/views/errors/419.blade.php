@extends('layouts.app')

@section('title', '操作の有効期限が切れました')

@section('content')
    <main>
        <div class="wrapper">
            <section class="error-page" aria-labelledby="error-page-title">
                <p class="error-page__status" aria-hidden="true">419</p>
                <h2 class="heading" id="error-page-title">操作の有効期限が切れました</h2>
                <p class="error-page__message">お手数ですが、最初から操作をやり直してください。</p>
                <a class="error-page__link" href="{{ route('theaters.index') }}">上映枠一覧へ戻る</a>
            </section>
        </div>
    </main>
@endsection

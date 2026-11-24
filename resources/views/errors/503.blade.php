@extends('layouts.app')

@section('title', 'メンテナンス中です')

@section('content')
    <main>
        <div class="wrapper">
            <section class="error-page" aria-labelledby="error-page-title">
                <p class="error-page__status" aria-hidden="true">503</p>
                <h2 class="heading" id="error-page-title">ただいまメンテナンス中です</h2>
                <p class="error-page__message">ご不便をおかけして申し訳ありません。しばらくしてから、もう一度お試しください。</p>
            </section>
        </div>
    </main>
@endsection

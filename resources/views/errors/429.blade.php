@extends('layouts.app')

@section('title', 'しばらくしてからお試しください')

@section('content')
    <main>
        <div class="wrapper">
            <section class="error-page" aria-labelledby="error-page-title">
                <p class="error-page__status" aria-hidden="true">429</p>
                <h2 class="heading" id="error-page-title">しばらくしてからお試しください</h2>
                <p class="error-page__message">短時間に操作が集中しています。時間をおいて、もう一度お試しください。</p>
                <a class="error-page__link" href="{{ route('home.index') }}">トップページへ戻る</a>
            </section>
        </div>
    </main>
@endsection

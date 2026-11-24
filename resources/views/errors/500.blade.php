@extends('layouts.app')

@section('title', 'システムエラーが発生しました')

@section('content')
    <main>
        <div class="wrapper">
            <section class="error-page" aria-labelledby="error-page-title">
                <p class="error-page__status" aria-hidden="true">500</p>
                <h2 class="heading" id="error-page-title">システムエラーが発生しました</h2>
                <p class="error-page__message">ご不便をおかけして申し訳ありません。時間をおいて、もう一度お試しください。</p>
                <a class="error-page__link" href="{{ route('home.index') }}">トップページへ戻る</a>
            </section>
        </div>
    </main>
@endsection

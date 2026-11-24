@extends('layouts.app')

@section('title', 'ページが見つかりません')

@section('content')
    <main>
        <div class="wrapper">
            <section class="error-page" aria-labelledby="error-page-title">
                <p class="error-page__status" aria-hidden="true">404</p>
                <h2 class="heading" id="error-page-title">ページが見つかりません</h2>
                <p class="error-page__message">お探しのページは削除されたか、URLが変更されている可能性があります。</p>
                <a class="error-page__link" href="{{ route('home.index') }}">トップページへ戻る</a>
            </section>
        </div>
    </main>
@endsection

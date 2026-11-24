@extends('layouts.app')

@section('title', 'この操作は許可されていません')

@section('content')
    <main>
        <div class="wrapper">
            <section class="error-page" aria-labelledby="error-page-title">
                <p class="error-page__status" aria-hidden="true">405</p>
                <h2 class="heading" id="error-page-title">この操作は許可されていません</h2>
                <p class="error-page__message">ページを直接開くことはできません。画面の操作からお進みください。</p>
                <a class="error-page__link" href="{{ route('home.index') }}">トップページへ戻る</a>
            </section>
        </div>
    </main>
@endsection

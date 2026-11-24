<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    {{-- title --}}
    <title>@yield('title') | YOHOシネマズ</title>
    {{-- reset css --}}
    <link rel="stylesheet" href="https://unpkg.com/ress/dist/ress.min.css" />
    {{-- favicon --}}
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    {{-- apple touch icon --}}
    <link rel="apple-touch-icon" href="{{ asset('favicon.svg') }}">
    {{-- font --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Noto+Sans+JP:wght@100..900&display=swap" rel="stylesheet">
    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

    <header class='header'>
        <div class="header__top">
            <h1 class='header__logo'>
                <a href="{{ route('home.index') }}" >
                    <img src="{{ asset('logo-trimmed.svg') }}" alt="">
                </a>
            </h1>
            @if(!request()->routeIs('reservations.*'))
                <div class="header__links">
                    <a href="{{ route('home.index') }}" class='header__link {{ request()->routeIs('home.index') ? 'is-active' : '' }}'>ホーム</a>
                    <a href="{{ route('movies.index') }}" class='header__link {{ request()->routeIs('movies.*') ? 'is-active' : '' }}'>上映中映画一覧</a>
                    <a href="{{ route('theaters.index') }}" class='header__link {{ request()->routeIs('theaters.*') ? 'is-active' : '' }}'>上映枠一覧</a>
                </div>
            @endif
            <button
                type="button"
                class="hamburger-menu"
                id="js-hamburger-menu"
                aria-label="メニューを開く"
                aria-expanded="false"
                aria-controls="js-hamburger-drawer"
            >
                <div class="hamburger-menu__lines">
                    <span class="hamburger-menu__line"></span>
                    <span class="hamburger-menu__line"></span>
                    <span class="hamburger-menu__line"></span>
                </div>
                <span class="hamburger-menu__label">メニュー</span>
            </button>
        </div>
    </header>

    <div class="hamburger-drawer__backdrop" id="js-hamburger-backdrop"></div>
    <aside class="hamburger-drawer" id="js-hamburger-drawer" aria-label="メインメニュー">
        <div class="hamburger-drawer__header">
            <p class="hamburger-drawer__title">メニュー</p>
            <button type="button" class="hamburger-drawer__close" id="js-hamburger-close" aria-label="メニューを閉じる">
                <span></span>
                <span></span>
            </button>
        </div>
        <nav>
            <ul class="hamburger-drawer__list">
                <li><a href="{{ route('home.index') }}">ホーム</a></li>
                <li><a href="{{ route('movies.index') }}">上映中映画一覧</a></li>
                <li><a href="{{ route('theaters.index') }}">劇場・上映枠一覧</a></li>
            </ul>
        </nav>
    </aside>
    {{-- 予約フローに入っている時にnavigation barを表示 --}}
    @if(request()->routeIs('reservations.*'))
        @include('components.navigation')
    @endif
    {{-- 1. 成功通知（customのsuccess ＋ Laravel標準のstatus） --}}
    @if (session('success') || session('status'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-100 p-4 text-sm text-green-700" role="alert">
            <span class="font-bold">完了：</span> {{ session('success') ?? session('status') }}
        </div>
    @endif

    {{-- 2. お知らせ・案内（info） --}}
    @if (session('info'))
        <div class="mb-4 rounded-lg border border-blue-200 bg-blue-100 p-4 text-sm text-blue-700" role="alert">
            <span class="font-bold">通知：</span> {{ session('info') }}
        </div>
    @endif

    {{-- 3. 警告・注意（warning） --}}
    @if (session('warning'))
        <div class="mb-4 rounded-lg border border-amber-200 bg-amber-100 p-4 text-sm text-amber-800" role="alert">
            <span class="font-bold">注意：</span> {{ session('warning') }}
        </div>
    @endif

    {{-- 4. 単発のエラー通知 --}}
    @if (session('error'))
        <div class="mb-4 rounded-lg border border-red-200 bg-red-100 p-4 text-sm text-red-700" role="alert">
            <span class="font-bold">エラー：</span> {{ session('error') }}
        </div>
    @endif

    {{-- 5. バリデーションエラー一覧（複数エラー発生時） --}}
    @if ($errors->any())
        <div class="mb-4 rounded-lg border border-red-200 bg-red-100 p-4 text-sm text-red-700" role="alert">
            <div class="mb-1 font-bold">入力内容をご確認ください：</div>
            <ul class="list-inside list-disc space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @yield('content')

    <footer class='footer'>
        <div class="footer__logo">
            <img src="{{ asset('logo.svg') }}" alt="YOHOシネマズ">
        </div>
    </footer>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const menuButton = document.getElementById('js-hamburger-menu');
        const drawer = document.getElementById('js-hamburger-drawer');
        const backdrop = document.getElementById('js-hamburger-backdrop');
        const closeButton = document.getElementById('js-hamburger-close');

        if (!menuButton || !drawer || !backdrop || !closeButton) return;

        const closeMenu = () => {
            menuButton.classList.remove('is-active');
            drawer.classList.remove('is-open');
            backdrop.classList.remove('is-visible');
            document.body.classList.remove('is-menu-open');
            menuButton.setAttribute('aria-expanded', 'false');
            menuButton.setAttribute('aria-label', 'メニューを開く');
        };

        const openMenu = () => {
            menuButton.classList.add('is-active');
            drawer.classList.add('is-open');
            backdrop.classList.add('is-visible');
            document.body.classList.add('is-menu-open');
            menuButton.setAttribute('aria-expanded', 'true');
            menuButton.setAttribute('aria-label', 'メニューを閉じる');
            closeButton.focus();
        };

        menuButton.addEventListener('click', () => {
            drawer.classList.contains('is-open') ? closeMenu() : openMenu();
        });

        closeButton.addEventListener('click', closeMenu);
        backdrop.addEventListener('click', closeMenu);
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && drawer.classList.contains('is-open')) {
                closeMenu();
            }
        });
    });
</script>
</body>
</html>

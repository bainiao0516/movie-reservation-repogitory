@extends('layouts.app')
@section('title', 'トップページ')
@section('content')
<main>
    <div class="wrapper">
        {{-- <h2 class='heading'></h2> --}}

        <div class="content">
            <div class="theater">
                <h3 class='sub-heading'>劇場を探す</h3>
                <div class="search-form">
                    <form action="{{ route('theaters.index') }}" method="GET" class="search-form__inner">
                        <input type="text"
                               name="location"
                               value="{{ request('location') }}"
                               placeholder="劇場名や地域名・住所で検索"
                               class="search-form__input">

                        <button type="submit" class="search-form__submit">
                            <img src="{{ asset('search-icon.svg') }}" alt="">
                        </button>

                        {{-- 検索中のみ「クリア」ボタンを表示 --}}
                        @if(request('location'))
                            <a href="{{ route('theaters.index') }}" class="search-form__clear">
                                クリア
                            </a>
                        @endif
                    </form>
                </div>
                <div class="theater__link-inner">
                    <a class='theater__link' href="{{ route('theaters.index') }}">一覧から探す</a>
                </div>
                @if ($recentTheaters->isNotEmpty())
                    <section class="recent-theaters" aria-labelledby="recent-theaters-heading">
                        <h3 id="recent-theaters-heading" class="sub-heading">
                            最近閲覧した劇場
                        </h3>

                        <ul class="recent-theaters__list">
                            @foreach ($recentTheaters as $recentTheater)
                                <li class="recent-theaters__item">
                                    <a
                                        class="recent-theaters__link"
                                        href="{{ route('theaters.schedules.index', [
                                            'theater' => $recentTheater,
                                            'date' => today()->format('Y-m-d'),
                                        ]) }}"
                                    >
                                        <span>{{ $recentTheater->name }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif
            </div>
            <div class="movie">
                <h3 class='sub-heading'>最新映画一覧</h3>
                <div class="home-movie-slider swiper" aria-label="最新映画一覧">
                    <ul class="home-movie-slider__list swiper-wrapper">
                    @foreach($latestMovies as $movie)
                        <li class="home-movie-slider__slide swiper-slide">
                            <a class='home-movie-card' href="{{ route('movies.show', $movie) }}">
                                <div class="home-movie-card__thumbnail">
                                    <img src="{{ Storage::url($movie->thumbnail_path) }}" alt="{{ $movie->title }}" loading="lazy">
                                </div>
                                <div>
                                    <h4 class="home-movie-card__title">
                                        {{ $movie->title }}
                                    </h4>
                                </div>
                            </a>
                        </li>
                    @endforeach
                    </ul>
                    <button class="home-movie-slider__button home-movie-slider__button--prev" type="button" aria-label="前の映画を表示"></button>
                    <button class="home-movie-slider__button home-movie-slider__button--next" type="button" aria-label="次の映画を表示"></button>
                    <div class="home-movie-slider__pagination swiper-pagination"></div>
                </div>
                <h3 class="sub-heading">人気映画ランキング</h3>
                <ol class="movie-ranking">
                    @foreach ($popularMovies as $movie)
                        <li class="movie-ranking__item">
                            <a class="movie-ranking__link" href="{{ route('movies.show', $movie) }}">
                                <span @class([
                                    'movie-ranking__rank',
                                    'movie-ranking__rank--gold' => $loop->iteration === 1,
                                    'movie-ranking__rank--silver' => $loop->iteration === 2,
                                    'movie-ranking__rank--bronze' => $loop->iteration === 3,
                                ]) aria-label="{{ $loop->iteration }}位">
                                    {{ $loop->iteration }}
                                </span>
                                <span class="movie-ranking__thumbnail">
                                    <img src="{{ Storage::url($movie->thumbnail_path) }}" alt="" loading="lazy">
                                </span>
                                <span class="movie-ranking__title">{{ $movie->title }}</span>
                            </a>
                        </li>
                    @endforeach
                </ol>
            </div>
        </div>

    </div>
</main>
@endsection

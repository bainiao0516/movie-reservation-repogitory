@extends('layouts.app')
@section('title', '映画詳細')
@section('content')
<main>
    <div class="wrapper">
        <h2 class='heading' id="modal-title">
            {{ $movie?->title }}
        </h2>
        <div class="content movie-detail">
            <img id="modal-thumbnail"
                 src="{{ $movie?->thumbnail_path ? Storage::url($movie->thumbnail_path) : '' }}"
                 alt="{{ $movie?->title ?? 'タイトル不明' }}"
                 class="movies-detail__thumbnail">

            <p class="movies-detail__description" id="modal-description">
                {{ $movie?->description }}
            </p>

            <dl class="movies-detail__list">
                <dt class="movies-detail__term">監督</dt>
                <dd class="movies-detail__detail" id="modal-director">
                    {{ $movie?->director }}
                </dd>

                <dt class="movies-detail__term">出演</dt>
                <dd class="movies-detail__detail" id="modal-cast">
                    {{ $movie?->cast }}
                </dd>

                <dt class="movies-detail__term">上映時間</dt>
                <dd class="movies-detail__detail" id="modal-duration">
                    {{ $movie?->duration_minutes ? "{$movie->duration_minutes}分" : '' }}
                </dd>

                <dt class="movies-detail__term">公開日</dt>
                <dd class="movies-detail__detail" id="modal-release-date">
                    {{ $movie?->release_date }}
                </dd>
            </dl>
        </div>
    </div>
</main>
@endsection

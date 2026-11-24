@extends('layouts.app')
@section('title', '上映中映画一覧')
@section('content')
<main>
    <div class="wrapper">
        <h2 class='heading'>上映中映画一覧</h2>

        <div class="content">
            <ul class="movies-list">
                @foreach($movies as $movie)
                    <li class="movies-list__item">
                        <a class='movies-list__link' href="{{ route('movies.show', $movie) }}">
                            <div class="movies-list__thumbnail">
                                <img src="{{Storage::url( $movie->thumbnail_path)}}" alt="{{$movie->name}}">
                            </div>
                            <div class="movies-list__content">
                                <h3 class="movies-list__title">
                                    {{ $movie->title }}
                                </h3>
                            </div>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>

    </div>
</main>
@endsection

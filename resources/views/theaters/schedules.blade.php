@extends('layouts.app')
@section('title', '映画の選択')
@section('content')
<main>
    <div class="wrapper">
        <span>{{ $theater->name }}</span>
        <h2 class='heading'>
            {{ $date->locale('ja')->isoFormat('M/D(ddd)') }}の上映スケジュール
        </h2>
        <div class="content">
            <div class="flex gap-2 overflow-x-auto my-6 border-b pb-4">
                @foreach($dates as $d)
                    @php
                        $formattedDate = $d->format('Y-m-d');
                        $isActive = $formattedDate === $date->format('Y-m-d');
                    @endphp
                    <a href="{{ route('theaters.schedules.index', ['theater' => $theater->id, 'date' => $formattedDate]) }}"
                       class="px-4 py-2 rounded font-medium transition-colors text-center min-w-[80px] {{ $isActive ? 'bg-blue-600 text-white font-bold' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                        <div class="text-xs">{{ $d->isoFormat('M/D') }}</div>
                        <div class="text-sm">({{ $d->isoFormat('ddd') }})</div>
                    </a>
                @endforeach
            </div>
            <ul class="schedules-list">
                @foreach($movies as $movie)
                    <li class="schedules-list__item">
                        <div class="schedules-list__top">
                            <img src="{{Storage::url( $movie->thumbnail_path)}}" alt="{{$movie->name}}" class="schedules-list__thumbnail">
                            <div class="schedules-list__info">
                                <h3 class='schedules-list__title'>
                                    {{$movie->title}}
                                </h3>
                                <p class="schedules-list__duration">
                                    上映時間 {{$movie->duration_minutes}}分
                                </p>
                                <a type="button" class="schedules-list__detail" href='{{ route('movies.show', $movie) }}'>

                                    作品詳細を見る
                                </a>
                            </div>
                        </div>
                        <div class="schedules-list__slots">
                            @foreach($movie->schedules as $schedule)
                            <div class="schedules-list__slot">
                                {{-- 予約可不可の判定 --}}
                                @if($schedule->isBookable())
                                    <a class=" schedules-list__slot-inner schedules-list__link"
                                    href='{{route(
                                    'reservations.seats.select',
                                    ['schedule' => $schedule->id]
                                    )}}'
                                    >
                                        <span class='schedules-list__schedule'>
                                            {{$schedule->start_at->format('H:i') }}〜{{ $schedule->end_at->format('H:i') }}
                                        </span>
                                        <span class="schedules-list__status">
                                            {{ $schedule->congestion_icon }}
                                            販売中
                                        </span>
                                        <span class="schedules-list__screen">
                                            {{$schedule->screen->name}}
                                        </span>
                                    </a>
                                @else
                                    <div class="schedules-list__disabled schedules-list__slot-inner"
                                    >
                                        <span class='schedules-list__schedule'>
                                             {{$schedule->start_at->format('H:i') }}〜{{ $schedule->end_at->format('H:i') }}
                                        </span>
                                        @if($schedule->isFull())
                                            <span class="schedules-list__status">
                                                {{ $schedule->congestion_icon }}
                                                満席
                                            </span>
                                        @else
                                            <span class="schedules-list__status">販売期間外</span>
                                        @endif
                                        <span class="schedules-list__screen">
                                            {{$schedule->screen->name}}
                                        </span>
                                    </div>
                                @endif
                            </div>
                            @endforeach
                        </div>
                    </li>
                @endforeach
            </ul>
            <dialog id="movie-detail-modal" class="movie-modal">
                <x-movie-detail :movie="$movie" />
                <button type='button' class='movie-modal__close' id='movie-modal-close'></button>
            </dialog>
            <div class="movie-modal-mask" id="movie-modal-mask"></div>
        </div>
    </div>
</main>
@endsection

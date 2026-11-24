@extends('layouts.app')
@section('title', '映画館の選択')
@section('content')
<main>
    <div class="wrapper">
        <h2 class='heading'>映画館一覧</h2>
        <div class="content">
            <div class="search-form">
              <form action="{{ route('theaters.index') }}" method="GET" class="search-form__inner">
                  <input type="text"
                         name="location"
                         value="{{ request('location') }}"
                         placeholder="劇場名や地域名・住所で検索"
                         class="search-form__input">

                  <div class="search-form__buttons">
                    {{-- 検索中のみ「クリア」ボタンを表示 --}}
                    @if(request('location'))
                        <a href="{{ route('theaters.index') }}" class="search-form__clear" aria-label="検索条件をクリア">
                        </a>
                    @endif
                    <button type="submit" class="search-form__submit" aria-label="映画館を検索">
                        <img src="{{ asset('search-icon.svg') }}" alt="">
                    </button>
                  </div>
              </form>
            </div>
            @if($theaters->isEmpty())
                <p class="theaters-empty">
                    「{{ request('location') }}」に一致する劇場は見つかりませんでした。
                </p>
            @else
              <ul class="theaters-list">
                  @foreach($theaters as $theater)
                      <li class="theaters-list__item">
                          <a href="{{ route('theaters.schedules.index', $theater) }}" class="theaters-list__link">
                              {{ $theater->name }}
                          </a>
                          <p class='theater-list__location'>
                              住所：{{ $theater->location }}
                          </p>
                      </li>
                  @endforeach
              </ul>
            @endif
            @if ($theaters->hasPages())
              <nav class="pagination-nav">
                <ul class="pagination">
                  {{-- 前へボタン --}}
                  @if ($theaters->onFirstPage())
                    <li class="pagination__item pagination__item--disabled"><span>&laquo;</span></li>
                  @else
                    <li class="pagination__item">
                      <a href="{{ $theaters->previousPageUrl() }}" class="pagination__link">&laquo;</a>
                    </li>
                  @endif

                  @foreach ($theaters->getUrlRange(1, $theaters->lastPage()) as $page => $url)
                    @if ($page == $theaters->currentPage())
                      <li class="pagination__item pagination__item--active"><span>{{ $page }}</span></li>
                    @else
                      <li class="pagination__item">
                        <a href="{{ $url }}" class="pagination__link">{{ $page }}</a>
                      </li>
                    @endif
                  @endforeach

                  @if ($theaters->hasMorePages())
                    <li class="pagination__item">
                      <a href="{{ $theaters->nextPageUrl() }}" class="pagination__link">&raquo;</a>
                    </li>
                  @else
                    <li class="pagination__item pagination__item--disabled"><span>&raquo;</span></li>
                  @endif
                </ul>
              </nav>
            @endif
        </div>
    </div>
</main>
@endsection

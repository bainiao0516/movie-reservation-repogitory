@props([
    'currentStep' => 1,
    'scheduleId' => null,
])

@php

    $steps = [
        1 => [
            'name'       => '座席選択',
            'route_name' => 'reservations.seats.select',
            'url'        => $scheduleId ? route('reservations.seats.select', ['schedule' => $scheduleId]) : null,
        ],
        2 => [
            'name'       => '券種選択',
            'route_name' => 'reservations.tickets.select',
            'url'        => route('reservations.tickets.select')
        ],
        3 => [
            'name'       => 'お客様情報入力',
            'route_name' => 'reservations.customer.create',
            'url'        => route('reservations.customer.create'),
        ],
        4 => [
            'name'       => '予約確認',
            'route_name' => 'reservations.confirm.show',
            'url'        => route('reservations.confirm.show'),
        ],
        5 => [
            'name'       => '完了',
            'route_name' => 'reservations.thanks.show',
            'url'        => route('reservations.thanks.show'),
        ],
    ];

    // 現在の画面に合致するステップ番号を取得
    // 現在のステップ番号を取得（一致しない場合は 1）
    $currentStepNum = collect($steps)->search(fn ($step) => request()->routeIs($step['route_name'])) ?: 1;

    // 番号（number）を追加した連想配列として取得
    $currentStep = array_merge(
        ['number' => $currentStepNum],
        $steps[$currentStepNum]
    );
@endphp

<nav aria-label="ナビゲーション" class="nav">
    <ol class="step-list">
        @foreach ($steps as $stepNumber => $step)
            @php
                $isActive = $currentStepNum === $stepNumber;
                $isPassed = $currentStepNum > $stepNumber;
                $isFuture = $currentStepNum < $stepNumber;
                // dump($step['route_name'] !== 'reservations.thanks.show');
            @endphp

            <li class="step-list__item">

                {{-- 過去のステップ：クリックして戻れる（必要に応じてリンク化） --}}
                @if ($isPassed && $step['url'] && $currentStep['route_name'] !== 'reservations.thanks.show')
                    <a href="{{ $step['url'] }}" class="step-list__label step-list__passed">
                        <span>{{ $stepNumber }}</span>
                        <span>{{ $step['name'] }}</span>
                    </a>

                {{-- 現在のステップ：アクティブ表示 --}}
                @elseif ($isActive)
                    <div class="step-list__label step-list__current">
                        <span>{{ $stepNumber }}</span>
                        <span>{{ $step['name'] }}</span>
                    </div>

                {{-- 未完了のステップ：リンク無効（スキップ防止） --}}
                @else
                    <div class="step-list__label step-list__future">
                        <span class="">{{ $stepNumber }}</span>
                        <span>{{ $step['name'] }}</span>
                    </div>
                @endif

            </li>
        @endforeach
    </ol>
</nav>

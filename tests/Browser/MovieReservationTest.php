<?php

use App\Models\Movie;
use App\Models\Schedule;
use App\Models\ScheduleSeat;
use App\Models\Seat;
use App\Models\TicketType;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

test('example', function () {
    /** @var DuskTestCase $this */
    $this->browse(function (Browser $browser) {
        $browser->visit('/theaters')
            ->assertSee('映画館一覧');
    });
});

// 正常系

test('ユーザーは映画選択から予約完了までの一連のフローを完了できる', function () {
    // 1. テストに必要なマスタデータ・可変データを事前生成
    $movie = Movie::factory()->create();
    $seat = Seat::factory()->create();
    $screen = $seat->screen;
    $theater = $screen->theater;
    $schedule = Schedule::factory()->create([
        'screen_id' => $screen->id,
        'movie_id' => $movie->id,
        'start_at' => $todayEvening = now()->setTime(18, 0, 0)->format('Y-m-d H:i:s'),
    ]);
    $scheduleSeat = ScheduleSeat::factory()->create([
        'seat_id' => $seat->id,
        'schedule_id' => $schedule->id,
        'status' => 'available',
    ]);
    $ticketType = TicketType::factory()->create([
        'is_active' => true,
    ]);

    // 2. ブラウザ操作による一連の予約フロー実行
    /** @var DuskTestCase $this */
    $this->browse(function (Browser $browser) use ($scheduleSeat, $theater, $movie, $schedule, $ticketType) {
        $today_jp = now()->isoFormat('M/D(ddd)');
        $today = now()->format('Y-m-d');

        $browser// (1) 映画館選択画面にアクセス
            ->visit('/theaters')
            ->assertSee($theater->name)

            // (2) 映画館を選択し、日付選択画面に移動
            ->click('.theaters-list__link')
            ->assertSee($today_jp)

            // (3) 日付を選択し、上映枠選択画面に移動
            ->click('a[href*="/theaters/' . $theater->id . '/schedules?date=' . $today . '"]')
            ->assertSee($movie->title);

        // HTMLリソースの表示
        // dump($browser->driver->getPageSource());

        // (4) 上映枠を選択し、座席選択画面に移動
        $browser->click('a[href*="/reservations/seats/' . $schedule->id . '"]')
            ->assertSee('座席を選択してください')

            // (5) 座席を選択し、券種を選択した後、予約者情報入力画面に移動
            ->click('label:has(input[value="' . $scheduleSeat->id . '"])')
            ->pause(300);

        $browser->press('選択を完了する')
            ->waitForLocation('/reservations/tickets')
            ->assertPathIs('/reservations/tickets')
            ->assertSee('券種を選択してください')
            ->click('.ticket-form__button button')
            ->waitFor('.ticket-form-modal__button')
            ->click('.ticket-form-modal__button[data-id="' . $ticketType->id . '"]')
            ->press('次へ進む')
            ->waitForLocation('/reservations/customer')
            ->assertPathIs('/reservations/customer')
            ->assertSee('ご購入内容を確認の上')
            // (5) 予約者情報を入力し、予約確認画面に移動
            ->type('name', '山田太郎')
            ->type('email', 'test@example.com')
            ->type('tel', '09012345678')
            // ->pause(300)
            ->press('次へ')
            ->waitForLocation('/reservations/confirm')
            ->assertPathIs('/reservations/confirm')
            ->assertSee($movie->title)
            ->assertSee('山田太郎')
            ->assertSee('09012345678')
        // (6) 予約確定ボタンを押して、購入完了画面に移動
            ->press('購入を確定する')
            ->waitForLocation('/reservations/thanks')
            ->assertPathIs('/reservations/thanks')
            ->assertSee('チケットの購入が完了しました');
    });
});

//　異常系

test('ユーザーは不正な予約者情報を送信することができない', function () {

    // 1.初期設定
    [$movie, $seat, $screen, $theater, $schedule, $scheduleSeat, $today_jp, $today] = setupData();
    $ticketType = TicketType::factory()->create([
        'is_active' => true,
    ]);

    /** @var DuskTestCase $this */
    $this->browse(function (Browser $browser) use ($scheduleSeat, $theater, $schedule, $today, $ticketType) {

        $browser
            ->visit('/theaters')
            ->click('.theaters-list__link')
            ->click('a[href*="/theaters/' . $theater->id . '/schedules?date=' . $today . '"]');
        $browser->click('a[href*="/reservations/seats/' . $schedule->id . '"]')
            ->click('label:has(input[value="' . $scheduleSeat->id . '"])')
            ->pause(300);
        $browser->press('選択を完了する')
            ->waitForLocation('/reservations/tickets')
            ->assertPathIs('/reservations/tickets')
            ->assertSee('券種を選択してください')
            ->click('.ticket-form__button button')
            ->waitFor('.ticket-form-modal__button')
            ->click('.ticket-form-modal__button[data-id="' . $ticketType->id . '"]')
            ->press('次へ進む')
            ->waitForLocation('/reservations/customer')
            // 2.不正入力防止の確認
            ->type('name', 'あ')
            ->type('email', 'test')
            ->type('tel', '090')
            ->press('次へ')
            ->pause(300)
            ->assertSee('入力内容をご確認ください')
            ->assertSee('メールアドレスは、有効なメールアドレス形式で指定してください。')
            ->assertSee('電話番号には、正しい形式を指定してください。');
    });
});

test('ユーザーは予約済みの座席を選択することができない', function () {
    [$movie, $seat, $screen, $theater, $schedule, $scheduleSeat, $today_jp, $today] = setupData();

    $secondSeat = Seat::factory()->create([
        'screen_id' => $screen->id,
    ]);

    $reservedSeat = ScheduleSeat::factory()->create([
        'schedule_id' => $schedule->id,
        'seat_id' => $secondSeat->id,
        'status' => 'reserved',
    ]);

    /** @var DuskTestCase $this */
    $this->browse(function (Browser $browser) use ($theater, $schedule, $today, $reservedSeat) {

        $browser
            ->visit('/theaters')
            ->click('.theaters-list__link')
            ->click('a[href*="/theaters/' . $theater->id . '/schedules?date=' . $today . '"]')
            ->click('a[href*="/reservations/seats/' . $schedule->id . '"]')
            ->assertMissing('label:has(input[value="' . $reservedSeat->id . '"])');

    });
});

function setupData(): array
{
    $movie = Movie::factory()->create();
    $seat = Seat::factory()->create();
    $screen = $seat->screen;
    $theater = $screen->theater;
    $schedule = Schedule::factory()->create([
        'screen_id' => $screen->id,
        'movie_id' => $movie->id,
        'start_at' => now()->setTime(18, 0, 0)->format('Y-m-d H:i:s'),
    ]);
    $scheduleSeat = ScheduleSeat::factory()->create([
        'seat_id' => $seat->id,
        'schedule_id' => $schedule->id,
        'status' => 'available',
    ]);

    $today_jp = now()->isoFormat('M/D(ddd)');
    $today = now()->format('Y-m-d');

    return [$movie, $seat, $screen, $theater, $schedule, $scheduleSeat, $today_jp, $today];
}

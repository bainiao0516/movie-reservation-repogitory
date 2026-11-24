<?php

use App\Http\Controllers\HomeController as ControllersHomeController;
use App\Http\Controllers\MovieController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\TheaterController;
use Illuminate\Support\Facades\Route;

// HomeController
Route::get('/', [ControllersHomeController::class, 'index'])->name('home.index');

// TheaterController
Route::get('/theaters', [TheaterController::class, 'index'])->name('theaters.index');
Route::get('/theaters/{theater}/schedules', [TheaterController::class, 'indexSchedules'])->name('theaters.schedules.index');

// MovieController
Route::get('/movies', [MovieController::class, 'index'])->name('movies.index');
Route::get('/movies/{movie}', [MovieController::class, 'show'])->name('movies.show');

// 予約フロー、仮予約の判定
Route::middleware(['ensure.reservation.session', 'no-cache'])->group(function () {
    Route::get('/reservations/seats/{schedule}',
        [ReservationController::class, 'selectSeats'])->name('reservations.seats.select');

    // Reservations
    Route::post('/reservations/temporary', [ReservationController::class, 'storeTemporary'])
        // ->middleware('throttle:5,1')
        ->name('reservations.temporary.store');
    Route::get('/reservations/tickets',
        [ReservationController::class, 'selectTickets'])->name('reservations.tickets.select');
    Route::post('/reservations/tickets', [ReservationController::class, 'storeTickets'])->name('reservations.tickets.store');

    Route::get('/reservations/customer', [ReservationController::class, 'createCustomer'])->name('reservations.customer.create');
    Route::post('/reservations/customer', [ReservationController::class, 'storeCustomer'])->name('reservations.customer.store');
    Route::get('/reservations/confirm', [ReservationController::class, 'showConfirm'])->name('reservations.confirm.show');
    Route::put('/reservations/complete', [ReservationController::class, 'storeComplete'])
        ->middleware('throttle:5,1')
        ->name('reservations.complete.store');

    Route::get('/reservations/holding', [ReservationController::class, 'showHolding'])->name('reservations.holding.show');
    Route::get('/reservations/holding/resume', [ReservationController::class, 'resumeHolding'])->name('reservations.holding.resume');
    Route::delete('/reservations/holding/discard', [ReservationController::class, 'discardHolding'])->name('reservations.holding.discard');
});

// Route::middleware('no-cache')->group(function () {
//     Route::get('/reservations/holding', [ReservationController::class, 'showHolding'])->name('reservations.holding.show');
//     Route::get('/reservations/holding/resume', [ReservationController::class, 'resumeHolding'])->name('reservations.holding.resume');
//     Route::delete('/reservations/holding/discard', [ReservationController::class, 'discardHolding'])->name('reservations.holding.discard');
// });

// 決済完了画面
Route::get('/reservations/thanks', [ReservationController::class, 'showThanks'])
    ->middleware('no-cache')
    ->name('reservations.thanks.show');

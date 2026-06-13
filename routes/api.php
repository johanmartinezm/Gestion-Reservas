<?php

declare(strict_types=1);

use App\Http\Controllers\ReservationController;
use App\Http\Controllers\UserReservationController;
use Illuminate\Support\Facades\Route;

Route::post('reservations', [ReservationController::class, 'store']);
Route::get('reservations/{reservation}', [ReservationController::class, 'show']);
Route::post('reservations/{reservation}/cancel', [ReservationController::class, 'cancel']);

Route::get('users/{user}/reservations', [UserReservationController::class, 'index']);

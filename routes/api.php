<?php

declare(strict_types=1);

use App\Http\Controllers\ProfessionalAvailabilityController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\UserReservationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('throttle:api')->group(function () {
    Route::post('reservations', [ReservationController::class, 'store']);
    Route::get('reservations/{reservation}', [ReservationController::class, 'show']);
    Route::post('reservations/{reservation}/cancel', [ReservationController::class, 'cancel']);

    Route::get('users/{user}/reservations', [UserReservationController::class, 'index']);

    Route::get('professionals/{professional}/availability', [ProfessionalAvailabilityController::class, 'index']);
});

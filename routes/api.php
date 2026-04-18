<?php

use App\Http\Controllers\Admin\CinemaController as AdminCinemaController;
use App\Http\Controllers\Admin\MovieController as AdminMovieController;
use App\Http\Controllers\Api\AdminComboController;
use App\Http\Controllers\Api\AdminGenreController;
use App\Http\Controllers\Api\AdminRoomController;
use App\Http\Controllers\Api\AdminShowtimeController;
use App\Http\Controllers\Api\AdminUserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\CinemaController;
use App\Http\Controllers\Api\ComboController;
use App\Http\Controllers\Api\GenreController;
use App\Http\Controllers\Api\ManagerRoomController;
use App\Http\Controllers\Api\ManagerSeatController;
use App\Http\Controllers\Api\ManagerShowtimeController;
use App\Http\Controllers\Api\ManagerStaffController;
use App\Http\Controllers\Api\MovieController;
use App\Http\Controllers\Api\StaffBookingController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\SeatHoldController;
use App\Http\Controllers\Api\ShowtimeController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);

    Route::get('cinemas', [CinemaController::class, 'index']);
    Route::get('cinemas/{cinema_id}', [CinemaController::class, 'show']);
    Route::get('movies', [MovieController::class, 'index']);
    Route::get('movies/{movie_id}', [MovieController::class, 'show']);
    Route::get('movies/{movie_id}/showtimes', [MovieController::class, 'showtimes']);
    Route::get('showtimes', [ShowtimeController::class, 'index']);
    Route::get('showtimes/{showtime_id}', [ShowtimeController::class, 'show']);
    Route::get('showtimes/{showtime_id}/seats', [ShowtimeController::class, 'seats']);
    Route::get('showtimes/{showtime_id}/availability', [ShowtimeController::class, 'availability']);

    Route::get('combos', [ComboController::class, 'index']);
    Route::get('genres', [GenreController::class, 'index']);
    Route::get('genres/{genre_id}', [GenreController::class, 'show']);

    Route::middleware(['auth:sanctum', 'account_status'])->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('users/me', [UserController::class, 'me']);
        Route::put('users/me', [UserController::class, 'update']);

        Route::post('showtimes/{showtime_id}/holds', [SeatHoldController::class, 'store']);
        Route::get('showtimes/{showtime_id}/holds', [SeatHoldController::class, 'index']);
        Route::delete('holds/{hold_id}', [SeatHoldController::class, 'destroy']);

        Route::post('bookings', [BookingController::class, 'store']);
        Route::get('bookings/{booking_id}', [BookingController::class, 'show']);
        Route::get('users/me/bookings', [BookingController::class, 'userBookings']);
        Route::post('bookings/{booking_id}/payment', [PaymentController::class, 'store']);
        Route::get('bookings/{booking_id}/payment', [PaymentController::class, 'show']);

        Route::middleware('manager')->prefix('manager')->group(function () {
            Route::apiResource('staff', ManagerStaffController::class);
            Route::apiResource('rooms', ManagerRoomController::class);
            Route::apiResource('showtimes', ManagerShowtimeController::class);
            Route::get('rooms/{room_id}/seats', [ManagerSeatController::class, 'index']);
            Route::post('rooms/{room_id}/seats/bulk', [ManagerSeatController::class, 'bulkStore']);
        });

        Route::middleware('staff')->prefix('staff')->group(function () {
            Route::get('bookings', [StaffBookingController::class, 'index']);
            Route::get('bookings/{booking_id}', [StaffBookingController::class, 'show']);
            Route::patch('bookings/{booking_id}', [StaffBookingController::class, 'update']);
        });

        Route::middleware('super_admin')->prefix('admin')->group(function () {
            Route::get('cinemas', [AdminCinemaController::class, 'index']);
            Route::post('cinemas', [AdminCinemaController::class, 'store']);
            Route::get('cinemas/{cinema}', [AdminCinemaController::class, 'show']);
            Route::put('cinemas/{cinema}', [AdminCinemaController::class, 'update']);
            Route::patch('cinemas/{cinema}/status', [AdminCinemaController::class, 'changeStatus']);

            Route::apiResource('movies', AdminMovieController::class)->except(['destroy']);
            
            Route::apiResource('regions', \App\Http\Controllers\Admin\RegionController::class);
            
            Route::apiResource('managers', \App\Http\Controllers\Admin\ManagerAccountController::class);
            Route::patch('managers/{manager}/status', [\App\Http\Controllers\Admin\ManagerAccountController::class, 'toggleStatus']);

            Route::apiResource('genres', AdminGenreController::class);
        });
    });
});

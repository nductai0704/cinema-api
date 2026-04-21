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

    // SIÊU NÚT BẤM V2: Fresh Migrate & Seed (Xoá sạch và xây lại)
    Route::get('setup-database', function() {
        try {
            // Bước 1: Xoá sạch và tạo lại toàn bộ bảng
            \Illuminate\Support\Facades\Artisan::call('migrate:fresh', ['--force' => true]);
            
            // Bước 2: Nạp lại dữ liệu Admin/Role mẫu
            \Illuminate\Support\Facades\Artisan::call('db:seed', ['--force' => true]);
            
            return response()->json([
                'status' => 'success',
                'message' => 'Hệ thống đã được làm mới hoàn toàn! Bảng "regions" và dữ liệu Admin đã sẵn sàng.',
                'admin_account' => [
                    'username' => 'admin',
                    'password' => '123456'
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    });

    // ==========================================
    // CUSTOMER PUBLIC ROUTES (PHASE 1)
    // ==========================================
    Route::get('movies', [\App\Http\Controllers\Api\CustomerPublicController::class, 'getMovies']);
    Route::get('movies/{id}', [\App\Http\Controllers\Api\CustomerPublicController::class, 'getMovieDetail']);
    Route::get('movies/{id}/showtimes', [\App\Http\Controllers\Api\CustomerPublicController::class, 'getShowtimesByMovie']);
    Route::get('showtimes/{id}/layout', [\App\Http\Controllers\Api\CustomerPublicController::class, 'getRoomLayout']);

    Route::get('cinemas', [CinemaController::class, 'index']);
    Route::get('cinemas/{cinema_id}', [CinemaController::class, 'show']);
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

        Route::get('users/me/bookings', [BookingController::class, 'userBookings']);
        
        // ==========================================
        // CUSTOMER BOOKING FLOW (PHASE 2)
        // ==========================================
        Route::post('holds', [\App\Http\Controllers\Api\CustomerBookingController::class, 'holdSeats']);
        Route::post('bookings', [\App\Http\Controllers\Api\CustomerBookingController::class, 'createBooking']);
        Route::post('bookings/{bookingId}/confirm', [\App\Http\Controllers\Api\CustomerBookingController::class, 'confirmPayment']);

        // Các route Staff (Nhân viên) sẽ được chuyển xuống dưới cùng để thống nhất

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

            Route::apiResource('combos', \App\Http\Controllers\Api\AdminComboController::class);
            Route::apiResource('genres', AdminGenreController::class);
        });
        // ==========================================
        // ROUTES DÀNH CHO MANAGER (QUẢN LÝ RẠP)
        // ==========================================
        Route::middleware(['account_status'])->prefix('manager')->group(function () {
            Route::apiResource('rooms', \App\Http\Controllers\Manager\ManagerRoomController::class);
            Route::get('rooms/{roomId}/seats', [\App\Http\Controllers\Manager\ManagerSeatController::class, 'index']);
            Route::post('rooms/{roomId}/seats/bulk', [\App\Http\Controllers\Manager\ManagerSeatController::class, 'bulkStore']);

            Route::apiResource('showtimes', \App\Http\Controllers\Manager\ManagerShowtimeController::class);
            Route::apiResource('staffs', \App\Http\Controllers\Manager\ManagerStaffController::class);
            Route::apiResource('news', \App\Http\Controllers\Manager\ManagerNewsController::class);
            
            Route::get('combos', [\App\Http\Controllers\Manager\ManagerComboController::class, 'index']);
            Route::put('combos/{comboId}', [\App\Http\Controllers\Manager\ManagerComboController::class, 'updateSetting']);
        });

        // ==========================================
        // ROUTES DÀNH CHO STAFF (NHÂN VIÊN BÁN VÉ)
        // ==========================================
        Route::middleware(['account_status'])->prefix('staff')->group(function () {
            Route::get('bookings', [\App\Http\Controllers\Api\StaffBookingController::class, 'index']);
            Route::get('bookings/{booking_id}', [\App\Http\Controllers\Api\StaffBookingController::class, 'show']);
            Route::patch('bookings/{booking_id}', [\App\Http\Controllers\Api\StaffBookingController::class, 'update']);
        });
    });
});

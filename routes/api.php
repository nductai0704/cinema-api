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
    Route::post('login', [AuthController::class, 'login'])->name('login');
    Route::post('register', [AuthController::class, 'register']);

    // Xác thực Email
    Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('/email/verification-notification', [AuthController::class, 'resendVerificationEmail'])
        ->middleware(['auth:sanctum', 'throttle:6,1'])
        ->name('verification.send');

    Route::get('setup-database', function() {
        try {
            $output = "--- BẮT ĐẦU CHẨN ĐOÁN & SỬA LỖI ---<br>";
            
            // 1. Chạy cập nhật database (giữ lại dữ liệu cũ)
            \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
            $output .= "<b>1. Migrate:</b> Đã cập nhật cấu trúc mới nhất!<br>";
            
            // 2. Kiểm tra cột region_id trong cinemas
            if (\Illuminate\Support\Facades\Schema::hasColumn('cinemas', 'region_id')) {
                $output .= "<b>2. cinemas.region_id:</b> ĐÃ TỒN TẠI.<br>";
            } else {
                \Illuminate\Support\Facades\DB::statement("ALTER TABLE cinemas ADD region_id BIGINT UNSIGNED NULL AFTER cinema_name");
                $output .= "<b>2. cinemas.region_id:</b> ĐÃ TẠO MỚI.<br>";
            }

            // 3. Kiểm tra cột status trong regions
            if (\Illuminate\Support\Facades\Schema::hasColumn('regions', 'status')) {
                $output .= "<b>3. regions.status:</b> ĐÃ TỒN TẠI.<br>";
            } else {
                \Illuminate\Support\Facades\DB::statement("ALTER TABLE regions ADD status VARCHAR(50) NOT NULL DEFAULT 'active' AFTER district");
                $output .= "<b>3. regions.status:</b> ĐÃ TẠO MỚI.<br>";
            }

            // 4. Kiểm tra bảng room_types
            if (\Illuminate\Support\Facades\Schema::hasTable('room_types')) {
                $output .= "<b>4. room_types table:</b> ĐÃ TỒN TẠI.<br>";
            } else {
                $output .= "<b>4. room_types table:</b> CHƯA TỒN TẠI - Đã được migrate ở bước 1.<br>";
            }

            // 5. Kiểm tra cột room_type_id trong rooms
            if (\Illuminate\Support\Facades\Schema::hasColumn('rooms', 'room_type_id')) {
                $output .= "<b>5. rooms.room_type_id:</b> ĐÃ TỒN TẠI.<br>";
            } else {
                \Illuminate\Support\Facades\DB::statement("ALTER TABLE rooms ADD room_type_id BIGINT UNSIGNED NULL AFTER room_name");
                $output .= "<b>5. rooms.room_type_id:</b> ĐÃ TẠO MỚI.<br>";
            }

            // 6. Kiểm tra cột seat_layout_id trong rooms ← QUAN TRỌNG
            if (\Illuminate\Support\Facades\Schema::hasColumn('rooms', 'seat_layout_id')) {
                $output .= "<b>6. rooms.seat_layout_id:</b> ĐÃ TỒN TẠI.<br>";
            } else {
                \Illuminate\Support\Facades\DB::statement("ALTER TABLE rooms ADD seat_layout_id BIGINT UNSIGNED NULL AFTER cinema_id");
                $output .= "<b>6. rooms.seat_layout_id:</b> ĐÃ TẠO MỚI (đây là lý do seat_layout_id bị null!).<br>";
            }

            // 7. Kiểm tra cột room_type cũ đã bị xóa chưa
            if (\Illuminate\Support\Facades\Schema::hasColumn('rooms', 'room_type')) {
                \Illuminate\Support\Facades\DB::statement("ALTER TABLE rooms DROP COLUMN room_type");
                $output .= "<b>7. rooms.room_type (cũ):</b> ĐÃ XÓA.<br>";
            } else {
                $output .= "<b>7. rooms.room_type (cũ):</b> Không tồn tại (OK).<br>";
            }

            return $output . "<br>--- HỆ THỐNG ĐÃ ĐƯỢC FIX ---";
        } catch (\Exception $e) {
            return "<b>LỖI:</b> " . $e->getMessage();
        }
    });

    // Nút bấm nạp lại 4 Roles (Không mất dữ liệu cũ)
    Route::get('seed-roles', function() {
        try {
            \Illuminate\Support\Facades\Artisan::call('db:seed', ['--force' => true]);
            return response()->json(['status' => 'success', 'message' => 'Đã nạp đủ 4 quyền tiêu chuẩn!']);
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
    Route::get('cinemas/{cinema_id}/movies', [\App\Http\Controllers\Api\CustomerPublicController::class, 'getMoviesByCinema']);
    Route::get('combos', [ComboController::class, 'index']);
    Route::get('genres', [GenreController::class, 'index']);
    Route::get('genres/{genre_id}', [GenreController::class, 'show']);
    Route::get('regions', [\App\Http\Controllers\Api\CustomerPublicController::class, 'getRegions']);

    Route::middleware(['auth:sanctum', 'account_status'])->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('users/me', [UserController::class, 'me']);
        Route::put('users/me', [UserController::class, 'update']);
        Route::post('users/me/change-password', [UserController::class, 'changePassword']);

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
        Route::middleware(['manager', 'account_status'])->prefix('manager')->group(function () {
            Route::apiResource('rooms', \App\Http\Controllers\Manager\ManagerRoomController::class);
            Route::patch('rooms/{room}/status', [\App\Http\Controllers\Manager\ManagerRoomController::class, 'toggleStatus']);
            Route::get('rooms/{roomId}/seats', [\App\Http\Controllers\Api\ManagerSeatController::class, 'index']);
            Route::post('rooms/{roomId}/seats/bulk', [\App\Http\Controllers\Api\ManagerSeatController::class, 'bulkStore']);
            Route::post('rooms/{roomId}/seats/sync', [\App\Http\Controllers\Api\ManagerSeatController::class, 'syncLayout']);

            Route::apiResource('room-types', \App\Http\Controllers\Manager\ManagerRoomTypeController::class);
            Route::patch('room-types/{id}/status', [\App\Http\Controllers\Manager\ManagerRoomTypeController::class, 'toggleStatus']);

            Route::apiResource('showtimes', \App\Http\Controllers\Manager\ManagerShowtimeController::class);
            Route::apiResource('staffs', \App\Http\Controllers\Manager\ManagerStaffController::class);
            Route::apiResource('seat-layouts', \App\Http\Controllers\Manager\ManagerSeatLayoutController::class);
            Route::patch('seat-layouts/{id}/status', [\App\Http\Controllers\Manager\ManagerSeatLayoutController::class, 'toggleStatus']);
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

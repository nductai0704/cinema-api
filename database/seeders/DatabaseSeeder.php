<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use App\Models\Cinema;
use App\Models\Movie;
use App\Models\Room;
use App\Models\Seat;
use App\Models\Showtime;
use App\Models\Genre;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Tạo 4 Roles tiêu chuẩn với ID cố định (Khớp với local MySQL)
        $adminRole = Role::updateOrCreate(['role_id' => 1], ['role_name' => 'admin', 'description' => 'Quản trị viên toàn hệ thống']);
        Role::updateOrCreate(['role_id' => 2], ['role_name' => 'manager', 'description' => 'Quản lý rạp']);
        Role::updateOrCreate(['role_id' => 3], ['role_name' => 'staff', 'description' => 'Nhân viên bán vé']);
        Role::updateOrCreate(['role_id' => 4], ['role_name' => 'customer', 'description' => 'Khách hàng xem phim']);

        // 2. Tạo Admin tiêu chuẩn
        User::updateOrCreate(
            ['username' => 'admin'],
            [
                'full_name' => 'Admin Hệ Thống',
                'email' => 'admin@gmail.com',
                'password_hash' => Hash::make('123456'),
                'role_id' => $adminRole->role_id,
                'status' => 'active'
            ]
        );

        // 3. Tạo 1 Rạp mẫu
        $cinema = Cinema::firstOrCreate(
            ['cinema_name' => 'Galaxy Test'],
            ['address' => 'Hà Nội', 'status' => 'active']
        );

        // 4. Tạo 1 Phim mẫu
        $movie = Movie::firstOrCreate(
            ['title' => 'Phim Test Railway'],
            [
                'duration' => 120,
                'release_date' => now(),
                'end_date' => now()->addDays(30),
                'status' => 'active'
            ]
        );

        echo "--- SEED DỮ LIỆU CỐT LÕI HOÀN TẤT ---";
    }
}

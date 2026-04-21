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
        echo "--- ĐANG BẮT ĐẦU SEED DỮ LIỆU --- \n";

        // 1. Tạo Roles
        $adminRole = Role::firstOrCreate(['role_name' => 'admin']);
        $managerRole = Role::firstOrCreate(['role_name' => 'manager']);
        $customerRole = Role::firstOrCreate(['role_name' => 'customer']);

        // 2. Tạo Admin
        User::firstOrCreate(
            ['username' => 'admin_galaxy'],
            [
                'full_name' => 'Hệ thống Admin',
                'email' => 'admin@galaxy.com',
                'password_hash' => Hash::make('password123'),
                'role_id' => $adminRole->role_id,
                'status' => 'active'
            ]
        );

        // 3. Tạo Rạp & Manager
        $cinema = Cinema::firstOrCreate(
            ['cinema_name' => 'Galaxy Bến Thành'],
            [
                'address' => 'Quận 1, TP.HCM',
                'description' => 'Rạp chiếu phim hiện đại bậc nhất',
                'status' => 'active'
            ]
        );

        User::firstOrCreate(
            ['username' => 'manager_galaxy'],
            [
                'full_name' => 'Quản lý Galaxy',
                'email' => 'manager@galaxy.com',
                'password_hash' => Hash::make('password123'),
                'role_id' => $managerRole->role_id,
                'cinema_id' => $cinema->cinema_id,
                'status' => 'active'
            ]
        );

        // 4. Tạo Thể loại & Phim
        $action = Genre::firstOrCreate(['genre_name' => 'Hành động']);
        $scifi = Genre::firstOrCreate(['genre_name' => 'Viễn tưởng']);

        $movies = [
            [
                'title' => 'Avatar: The Way of Water',
                'duration' => 192,
                'release_date' => now()->subDays(5),
                'end_date' => now()->addDays(30),
                'age_limit' => 13,
                'status' => 'active'
            ],
            [
                'title' => 'Avengers: Endgame',
                'duration' => 181,
                'release_date' => now()->subDays(10),
                'end_date' => now()->addDays(20),
                'age_limit' => 13,
                'status' => 'active'
            ],
            [
                'title' => 'Phim Sắp Chiếu 2026',
                'duration' => 120,
                'release_date' => now()->addDays(15),
                'end_date' => now()->addDays(45),
                'age_limit' => 16,
                'status' => 'active'
            ]
        ];

        foreach ($movies as $m) {
            $movie = Movie::firstOrCreate(['title' => $m['title']], $m);
            $movie->genres()->sync([$action->genre_id, $scifi->genre_id]);
        }

        // 5. Tạo Phòng & Ghế
        $room = Room::firstOrCreate(
            ['room_name' => 'P01 - IMAX', 'cinema_id' => $cinema->cinema_id],
            ['capacity' => 50, 'room_type' => 'IMAX', 'status' => 'active']
        );

        if ($room->seats()->count() == 0) {
            $seats = [];
            foreach (range('A', 'E') as $row) {
                foreach (range(1, 10) as $num) {
                    $seats[] = [
                        'room_id' => $room->room_id,
                        'row_label' => $row,
                        'seat_number' => $num,
                        'seat_type' => ($row == 'E') ? 'vip' : 'normal',
                        'status' => 'active',
                        'created_at' => now(),
                        'updated_at' => now()
                    ];
                }
            }
            Seat::insert($seats);
        }

        // 6. Tạo Suất chiếu
        $currentMovie = Movie::where('title', 'Avatar: The Way of Water')->first();
        Showtime::firstOrCreate(
            ['movie_id' => $currentMovie->movie_id, 'room_id' => $room->room_id, 'start_time' => '19:00:00'],
            [
                'cinema_id' => $cinema->cinema_id,
                'show_date' => now()->toDateString(),
                'end_time' => '22:12:00',
                'ticket_price' => 120000,
                'status' => 'active'
            ]
        );

        echo "--- SEED DỮ LIỆU HOÀN TẤT --- \n";
    }
}

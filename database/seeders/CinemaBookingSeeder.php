<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Cinema;
use App\Models\Combo;
use App\Models\Genre;
use App\Models\Movie;
use App\Models\News;
use App\Models\Role;
use App\Models\Room;
use App\Models\Showtime;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class CinemaBookingSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $roles = [
            ['role_name' => 'admin', 'description' => 'Quản trị hệ thống'],
            ['role_name' => 'manager', 'description' => 'Quản lý cụm rạp'],
            ['role_name' => 'staff', 'description' => 'Nhân viên rạp'],
            ['role_name' => 'customer', 'description' => 'Khách hàng'],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(['role_name' => $role['role_name']], $this->filterColumns(new Role(), $role));
        }

        $cinema = Cinema::updateOrCreate(
            ['cinema_name' => 'Galaxy Cinema'],
            $this->filterColumns(new Cinema(), [
                'address' => '123 Lê Lai, Quận 1, TP.HCM',
                'city' => 'Hồ Chí Minh',
                'district' => 'Quận 1',
                'phone' => '02812345678',
                'status' => 'active',
            ])
        );

        $managerRole = Role::where('role_name', 'manager')->first();
        $staffRole = Role::where('role_name', 'staff')->first();
        $customerRole = Role::where('role_name', 'customer')->first();
        $adminRole = Role::where('role_name', 'admin')->first();

        $adminUser = User::updateOrCreate(
            ['username' => 'admin'],
            $this->filterColumns(new User(), [
                'password_hash' => Hash::make('Admin@123'),
                'full_name' => 'Admin Cinema',
                'email' => 'admin@cinema.local',
                'phone' => '0909000000',
                'role_id' => $adminRole?->role_id,
                'cinema_id' => $cinema->cinema_id,
                'status' => 'active',
            ])
        );

        $managerUser = User::updateOrCreate(
            ['username' => 'manager'],
            $this->filterColumns(new User(), [
                'password_hash' => Hash::make('Manager@123'),
                'full_name' => 'Manager demo',
                'email' => 'manager@cinema.local',
                'phone' => '0909222222',
                'role_id' => $managerRole?->role_id,
                'cinema_id' => $cinema->cinema_id,
                'status' => 'active',
            ])
        );

        $staffUser = User::updateOrCreate(
            ['username' => 'staff'],
            $this->filterColumns(new User(), [
                'password_hash' => Hash::make('Staff@123'),
                'full_name' => 'Staff demo',
                'email' => 'staff@cinema.local',
                'phone' => '0909333333',
                'role_id' => $staffRole?->role_id,
                'cinema_id' => $cinema->cinema_id,
                'status' => 'active',
            ])
        );

        $customerUser = User::updateOrCreate(
            ['username' => 'customer'],
            $this->filterColumns(new User(), [
                'password_hash' => Hash::make('Customer@123'),
                'full_name' => 'Khách hàng demo',
                'email' => 'customer@cinema.local',
                'phone' => '0909111111',
                'role_id' => $customerRole?->role_id,
                'status' => 'active',
            ])
        );

        $genre = Genre::updateOrCreate(
            ['genre_name' => 'Hành động'],
            $this->filterColumns(new Genre(), ['status' => 'active'])
        );

        $movie = Movie::updateOrCreate(
            ['title' => 'Fast & Furious 14'],
            $this->filterColumns(new Movie(), [
                'duration' => 140,
                'description' => 'Hành trình mới của đội Dom tiếp tục với những pha hành động nghẹt thở.',
                'language' => 'Tiếng Anh',
                'release_date' => now()->toDateString(),
                'end_date' => now()->addWeeks(4)->toDateString(),
                'age_limit' => 16,
                'poster_url' => 'https://example.com/poster.jpg',
                'trailer_url' => 'https://example.com/trailer.mp4',
                'rating' => 8.5,
                'director' => 'Justin Lin',
                'country' => 'USA',
                'producer' => 'Universal Pictures',
                'status' => 'active',
            ])
        );

        $movie->genres()->syncWithoutDetaching([$genre->genre_id]);

        $room = $cinema->rooms()->updateOrCreate(
            ['room_name' => 'Phòng 1'],
            $this->filterColumns(new Room(), [
                'capacity' => 120,
                'room_type' => '2D',
                'status' => 'active',
            ])
        );

        Showtime::updateOrCreate(
            ['movie_id' => $movie->movie_id, 'room_id' => $room->room_id, 'show_date' => now()->toDateString(), 'start_time' => '18:00:00'],
            $this->filterColumns(new Showtime(), [
                'end_time' => '20:20:00',
                'ticket_price' => 120000,
                'status' => 'active',
            ])
        );

        Combo::updateOrCreate(
            ['combo_name' => 'Combo Popcorn + Nước'],
            $this->filterColumns(new Combo(), [
                'price' => 95000,
                'description' => 'Bắp rang và nước ngọt cỡ vừa.',
                'status' => 'active',
            ])
        );

        News::updateOrCreate(
            ['title' => 'Mở bán vé ngày cuối tuần'],
            $this->filterColumns(new News(), [
                'content' => 'Galaxy Cinema mở bán vé online cho suất chiếu cuối tuần với ưu đãi đặc biệt.',
                'image_url' => 'https://example.com/news.jpg',
                'created_by' => $adminUser->user_id,
                'status' => 'active',
            ])
        );
    }

    private function filterColumns($model, array $data): array
    {
        return array_filter($data, fn($value, $key) => Schema::hasColumn($model->getTable(), $key), ARRAY_FILTER_USE_BOTH);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Movie;
use App\Models\Showtime;
use App\Models\Seat;
use App\Models\Ticket;
use App\Models\SeatHold;
use App\Models\Cinema;
use App\Models\Genre;
use Illuminate\Http\Request;
use App\Http\Resources\Customer\MovieDetailResource;
use App\Http\Resources\Customer\ShowtimeResource;
use App\Http\Resources\Customer\SeatLayoutResource;
use Illuminate\Support\Carbon;

class CustomerPublicController extends Controller
{
    /**
     * Lấy danh sách phim phân loại Now Showing và Coming Soon
     */
    public function getMovies()
    {
        $nowShowing = Movie::showing()->orderBy('release_date', 'desc')->get();
        $comingSoon = Movie::upcoming()->orderBy('release_date', 'asc')->get();

        return response()->json([
            'now_showing' => MovieDetailResource::collection($nowShowing),
            'coming_soon' => MovieDetailResource::collection($comingSoon),
        ]);
    }

    /**
     * Chi tiết một bộ phim
     */
    public function getMovieDetail($id)
    {
        $movie = Movie::with('genres')->findOrFail($id);
        return new MovieDetailResource($movie);
    }

    /**
     * Lấy lịch chiếu của một phim, nhóm theo rạp
     * Ràng buộc: start_time > now()
     */
    public function getShowtimesByMovie($id)
    {
        $now = now();

        // Lấy tất cả các rạp có suất chiếu của phim này và còn hiệu lực
        $cinemas = Cinema::whereHas('rooms.showtimes', function ($query) use ($id, $now) {
            $query->where('movie_id', $id)
                  ->where('status', 'active');
        })->with([
            'rooms.showtimes' => function ($query) use ($id, $now) {
                $query->where('movie_id', $id)
                      ->where('status', 'active')
                      ->with('movie', 'roomType', 'room') // Load directly here
                      ->orderBy('show_date')
                      ->orderBy('start_time');
            }
        ])->get();

        $result = $cinemas->map(function ($cinema) use ($now) {
            // Lấy tất cả showtimes từ tất cả các phòng của rạp này
            $allShowtimes = $cinema->rooms->flatMap->showtimes->filter(function ($showtime) use ($now) {
                // Kiểm tra start_time > now() sử dụng accessor start_date_time
                return $showtime->start_date_time->gt($now);
            });

            return [
                'cinema_id' => $cinema->cinema_id,
                'cinema_name' => $cinema->cinema_name,
                'address' => $cinema->address,
                'showtimes' => ShowtimeResource::collection($allShowtimes->values()),
            ];
        });

        return response()->json($result);
    }

    /**
     * Lấy sơ đồ ghế và trạng thái thời gian thực
     */
    /**
     * Lấy lịch chiếu của rạp, nhóm theo phim
     */
    public function getMoviesByCinema($cinemaId)
    {
        $now = now();
        $cinema = Cinema::findOrFail($cinemaId);

        // Lấy các phim có suất chiếu tại rạp này
        $movies = Movie::whereHas('showtimes', function ($query) use ($cinemaId, $now) {
            $query->whereHas('room', function($q) use ($cinemaId) {
                $q->where('cinema_id', $cinemaId);
            })
            ->where('status', 'active')
            ->where(function($q) use ($now) {
                $q->where('show_date', '>', $now->toDateString())
                  ->orWhere(function($q2) use ($now) {
                      $q2->where('show_date', $now->toDateString())
                         ->where('start_time', '>', $now->toTimeString());
                  });
            });
        })->with([
            'showtimes' => function ($query) use ($cinemaId, $now) {
                $query->whereHas('room', function($q) use ($cinemaId) {
                    $q->where('cinema_id', $cinemaId);
                })
                ->where('status', 'active')
                ->where(function($q) use ($now) {
                    $q->where('show_date', '>', $now->toDateString())
                      ->orWhere(function($q2) use ($now) {
                          $q2->where('show_date', $now->toDateString())
                             ->where('start_time', '>', $now->toTimeString());
                      });
                })
                ->orderBy('show_date')
                ->orderBy('start_time');
            },
            'showtimes.movie',
            'showtimes.room',
            'showtimes.roomType'
        ])->get();

        $result = $movies->map(function ($movie) {
            return [
                'movie_id'   => $movie->movie_id,
                'movie_title' => $movie->title,
                'poster_url' => $movie->poster_url,
                'duration'   => $movie->duration,
                'age_limit'  => $movie->age_limit,
                'showtimes'  => ShowtimeResource::collection($movie->showtimes),
            ];
        });

        return response()->json([
            'cinema_name' => $cinema->cinema_name,
            'movies' => $result
        ]);
    }

    public function getRegions()
    {
        $regions = \App\Models\Region::active()->get();
        return \App\Http\Resources\RegionResource::collection($regions);
    }

    public function getRoomLayout($showtimeId)
    {
        $showtime = Showtime::with('room')->findOrFail($showtimeId);
        $roomId = $showtime->room_id;

        // 1. Lấy tất cả ghế trong phòng
        $seats = Seat::where('room_id', $roomId)
                     ->where('status', 'active')
                     ->orderBy('row_label')
                     ->orderBy('seat_number')
                     ->get();

        // 2. Lấy danh sách ID ghế đã bán
        $soldSeatIds = Ticket::where('showtime_id', $showtimeId)
                             ->where('status', '!=', 'cancelled')
                             ->pluck('seat_id')
                             ->toArray();

        // 3. Lấy danh sách ID ghế đang được giữ (chưa hết hạn)
        $heldSeatIds = SeatHold::where('showtime_id', $showtimeId)
                               ->where('expired_time', '>', now())
                               ->where('status', 'held')
                               ->pluck('seat_id')
                               ->toArray();

        // Trả về Resource Collection kèm theo nhãn status đã gán trực tiếp
        $seats->map(function($seat) use ($soldSeatIds, $heldSeatIds) {
            $seat->is_sold = in_array($seat->seat_id, $soldSeatIds);
            $seat->is_held = in_array($seat->seat_id, $heldSeatIds);
            return $seat;
        });

        return SeatLayoutResource::collection($seats);
    }
}

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
            'showtimes.roomType',
            'genres'
        ])->get();

        $result = $movies->map(function ($movie) {
            return [
                'movie_id'   => $movie->movie_id,
                'movie_title' => $movie->title,
                'poster_url' => $movie->poster_url,
                'trailer_url'=> $movie->trailer_url,
                'duration'   => $movie->duration,
                'age_limit'  => $movie->age_limit,
                'genres'     => $movie->genres->map(fn($g) => [
                    'genre_id'   => $g->genre_id,
                    'genre_name' => $g->genre_name
                ]),
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

    public function getShowtimeDetails($showtimeId)
    {
        $showtime = Showtime::with([
            'movie',
            'room.cinema',
            'room.roomType',
            'room.seatLayout'
        ])->findOrFail($showtimeId);

        // 1. Seats that are physically broken/inactive in the room
        $brokenSeats = \App\Models\Seat::where('room_id', $showtime->room_id)
            ->where('status', '!=', 'active')
            ->get();
        $brokenData = $brokenSeats->pluck('seat_id')->concat($brokenSeats->map(fn($s) => $s->row_label . $s->seat_number))->toArray();

        // 2. Seats that are sold for this showtime
        $tickets = \App\Models\Ticket::where('showtime_id', $showtimeId)
            ->where('status', '!=', 'cancelled')
            ->with('seat')
            ->get();
        $soldData = $tickets->map(fn($t) => $t->seat_id)->concat($tickets->map(fn($t) => $t->seat ? ($t->seat->row_label . $t->seat->seat_number) : null))->filter()->toArray();

        // 3. (Optional Debug) Seats that are held for this showtime - NO LONGER included in sold_seats
        // FE will now call /holds API to get these and color them YELLOW
        
        // Final merged array now ONLY contains Broken and Sold seats (Color: Gray)
        $unavailableSeats = array_values(array_unique(array_merge($brokenData, $soldData)));

        $data = [
            'showtime_id' => $showtime->showtime_id,
            'start_time' => \Carbon\Carbon::parse($showtime->start_time)->format('H:i'),
            'show_date' => $showtime->show_date ? $showtime->show_date->format('Y-m-d') : null,
            'price_standard' => (float)$showtime->price_standard,
            'price_vip' => (float)$showtime->price_vip,
            'price_double' => (float)$showtime->price_double,
            'movie' => [
                'title' => $showtime->movie ? $showtime->movie->title : null,
                'poster_url' => $showtime->movie ? $showtime->movie->poster_url : null,
            ],
            'room' => [
                'room_name' => $showtime->room ? $showtime->room->room_name : null,
                'room_type' => [
                    'name' => ($showtime->room && $showtime->room->roomType) ? $showtime->room->roomType->name : null
                ],
                'cinema' => [
                    'cinema_id' => ($showtime->room && $showtime->room->cinema) ? $showtime->room->cinema->cinema_id : null,
                    'cinema_name' => ($showtime->room && $showtime->room->cinema) ? $showtime->room->cinema->cinema_name : null
                ],
                'seat_layout' => [
                    'layout_data' => ($showtime->room && $showtime->room->seatLayout) ? $showtime->room->seatLayout->layout_data : []
                ]
            ],
            'sold_seats' => $unavailableSeats
        ];

        return response()->json(['data' => $data]);
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

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Movie;
use Illuminate\Http\Request;

class MovieController extends Controller
{
    public function index(Request $request)
    {
        $query = Movie::with('genres')
            ->where('status', 'active');

        if ($request->filled('genre_id')) {
            $query->whereHas('genres', function ($query) use ($request) {
                $query->where('genre_id', $request->input('genre_id'));
            });
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('title', 'like', "%{$search}%");
        }

        $movies = $query->orderBy('release_date', 'desc')
            ->paginate(12);

        return response()->json($movies);
    }

    public function show(int $movie_id)
    {
        $movie = Movie::with(['genres', 'showtimes.room.cinema'])
            ->findOrFail($movie_id);

        return response()->json($movie);
    }

    public function showtimes(int $movie_id)
    {
        $movie = Movie::with(['showtimes.room.cinema'])
            ->findOrFail($movie_id);

        $showtimes = $movie->showtimes->map(function ($showtime) {
            return [
                'showtime_id' => $showtime->showtime_id,
                'show_date' => $showtime->show_date,
                'start_time' => $showtime->start_time,
                'end_time' => $showtime->end_time,
                'session_label' => $showtime->session_label,
                'display_status' => $showtime->display_status,
                'ticket_price' => $showtime->ticket_price,
                'status' => $showtime->status,
                'room' => [
                    'room_id' => $showtime->room->room_id,
                    'room_name' => $showtime->room->room_name,
                    'room_type' => $showtime->room->room_type,
                ],
                'cinema' => [
                    'cinema_id' => $showtime->room->cinema->cinema_id,
                    'cinema_name' => $showtime->room->cinema->cinema_name,
                    'address' => $showtime->room->cinema->address,
                ],
            ];
        });

        $grouped = $showtimes->groupBy('show_date')->map(function ($items, $date) {
            return [
                'date' => $date,
                'sessions' => $items->sortBy('start_time')->values(),
            ];
        })->values();

        return response()->json([
            'movie_id' => $movie->movie_id,
            'title' => $movie->title,
            'showtimes_by_date' => $grouped,
        ]);
    }
}

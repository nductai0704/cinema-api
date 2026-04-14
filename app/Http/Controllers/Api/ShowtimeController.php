<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Seat;
use App\Models\SeatHold;
use App\Models\Showtime;
use App\Models\Ticket;

class ShowtimeController extends Controller
{
    public function index(Request $request)
    {
        $query = Showtime::with(['movie', 'room.cinema'])
            ->where('status', 'active');

        if ($request->filled('cinema_id')) {
            $query->whereHas('room', fn($q) => $q->where('cinema_id', $request->input('cinema_id')));
        }

        if ($request->filled('movie_id')) {
            $query->where('movie_id', $request->input('movie_id'));
        }

        if ($request->filled('date')) {
            $query->where('show_date', $request->input('date'));
        }

        if ($request->filled('display_status')) {
            $filterStatus = strtolower($request->input('display_status'));
            $query->get()->filter(fn($showtime) => $showtime->display_status === $filterStatus);
        }

        $showtimes = $query->get()->map(function ($showtime) {
            return [
                'showtime_id' => $showtime->showtime_id,
                'movie_id' => $showtime->movie_id,
                'movie_title' => $showtime->movie->title,
                'cinema' => [
                    'cinema_id' => $showtime->room->cinema->cinema_id,
                    'cinema_name' => $showtime->room->cinema->cinema_name,
                ],
                'room' => [
                    'room_id' => $showtime->room->room_id,
                    'room_name' => $showtime->room->room_name,
                ],
                'show_date' => $showtime->show_date,
                'start_time' => $showtime->start_time,
                'end_time' => $showtime->end_time,
                'session_label' => $showtime->session_label,
                'display_status' => $showtime->display_status,
                'ticket_price' => $showtime->ticket_price,
                'status' => $showtime->status,
            ];
        });

        $grouped = $showtimes->groupBy('show_date')->map(function ($items, $date) {
            return [
                'date' => $date,
                'sessions' => $items->sortBy('start_time')->values(),
            ];
        })->values();

        return response()->json(['showtimes_by_date' => $grouped]);
    }

    public function show(int $showtime_id)
    {
        $showtime = Showtime::with(['movie', 'room.cinema'])
            ->findOrFail($showtime_id);

        $showtime->loadMissing(['movie', 'room.cinema']);

        return response()->json([
            'showtime_id' => $showtime->showtime_id,
            'movie_id' => $showtime->movie_id,
            'movie_title' => $showtime->movie->title,
            'cinema' => [
                'cinema_id' => $showtime->room->cinema->cinema_id,
                'cinema_name' => $showtime->room->cinema->cinema_name,
            ],
            'room' => [
                'room_id' => $showtime->room->room_id,
                'room_name' => $showtime->room->room_name,
            ],
            'show_date' => $showtime->show_date,
            'start_time' => $showtime->start_time,
            'end_time' => $showtime->end_time,
            'session_label' => $showtime->session_label,
            'display_status' => $showtime->display_status,
            'ticket_price' => $showtime->ticket_price,
            'status' => $showtime->status,
        ]);
    }

    public function seats(int $showtime_id)
    {
        $showtime = Showtime::findOrFail($showtime_id);
        $bookedSeatIds = Ticket::where('showtime_id', $showtime_id)->pluck('seat_id')->toArray();
        $heldSeatIds = SeatHold::where('showtime_id', $showtime_id)
            ->where('status', 'active')
            ->pluck('seat_id')
            ->toArray();

        $seats = Seat::where('room_id', $showtime->room_id)
            ->orderBy('row_label')
            ->orderBy('seat_number')
            ->get()
            ->map(function ($seat) use ($bookedSeatIds, $heldSeatIds) {
                if (strcasecmp($seat->status, 'available') !== 0) {
                    $availability = 'blocked';
                } elseif (in_array($seat->seat_id, $bookedSeatIds, true)) {
                    $availability = 'booked';
                } elseif (in_array($seat->seat_id, $heldSeatIds, true)) {
                    $availability = 'held';
                } else {
                    $availability = 'available';
                }

                return [
                    'seat_id' => $seat->seat_id,
                    'row_label' => $seat->row_label,
                    'seat_number' => $seat->seat_number,
                    'seat_type' => $seat->seat_type,
                    'seat_status' => $seat->status,
                    'availability_status' => $availability,
                ];
            });

        return response()->json([ 'showtime' => $showtime, 'seats' => $seats ]);
    }

    public function availability(int $showtime_id)
    {
        $showtime = Showtime::findOrFail($showtime_id);
        $totalSeats = Seat::where('room_id', $showtime->room_id)->count();
        $bookedSeats = Ticket::where('showtime_id', $showtime_id)->count();
        $heldSeats = SeatHold::where('showtime_id', $showtime_id)
            ->where('status', 'active')
            ->count();

        return response()->json([
            'showtime' => [
                'showtime_id' => $showtime->showtime_id,
                'show_date' => $showtime->show_date,
                'start_time' => $showtime->start_time,
                'end_time' => $showtime->end_time,
                'session_label' => $showtime->session_label,
                'display_status' => $showtime->display_status,
            ],
            'total_seats' => $totalSeats,
            'booked_seats' => $bookedSeats,
            'held_seats' => $heldSeats,
            'available_seats' => $totalSeats - $bookedSeats - $heldSeats,
        ]);
    }
}

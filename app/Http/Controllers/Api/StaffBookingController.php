<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;

class StaffBookingController extends Controller
{
    public function index(Request $request)
    {
        $cinemaId = $request->user()->cinema_id;

        $bookings = Booking::with(['user', 'tickets.showtime.room.cinema', 'payment'])
            ->whereHas('tickets.showtime.room', fn($query) => $query->where('cinema_id', $cinemaId))
            ->paginate(20);

        return response()->json($bookings);
    }

    public function show(Request $request, int $booking_id)
    {
        $cinemaId = $request->user()->cinema_id;

        $booking = Booking::with(['user', 'tickets.showtime.room.cinema', 'payment'])
            ->whereHas('tickets.showtime.room', fn($query) => $query->where('cinema_id', $cinemaId))
            ->findOrFail($booking_id);

        return response()->json($booking);
    }

    public function update(Request $request, int $booking_id)
    {
        $cinemaId = $request->user()->cinema_id;

        $booking = Booking::whereHas('tickets.showtime.room', fn($query) => $query->where('cinema_id', $cinemaId))
            ->findOrFail($booking_id);

        $data = $request->validate([
            'status' => 'required|string|in:pending,confirmed,cancelled,checked_in',
        ]);

        $booking->update(['status' => $data['status']]);

        return response()->json($booking->fresh()->load(['user', 'tickets.showtime.room.cinema', 'payment']));
    }
}

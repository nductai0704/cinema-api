<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Showtime;
use Illuminate\Http\Request;

class AdminShowtimeController extends Controller
{
    public function index(Request $request)
    {
        $query = Showtime::with(['movie', 'room.cinema']);

        if ($request->filled('movie_id')) {
            $query->where('movie_id', $request->input('movie_id'));
        }

        if ($request->filled('room_id')) {
            $query->where('room_id', $request->input('room_id'));
        }

        return response()->json($query->paginate(20));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'movie_id' => 'required|integer|exists:movies,movie_id',
            'room_id' => 'required|integer|exists:rooms,room_id',
            'show_date' => 'required|date',
            'start_time' => 'required|date_format:H:i:s',
            'end_time' => 'required|date_format:H:i:s|after:start_time',
            'ticket_price' => 'required|numeric|min:0',
            'status' => 'nullable|string|max:50',
        ]);

        $showtime = Showtime::create($data);

        return response()->json($showtime->load(['movie', 'room.cinema']), 201);
    }

    public function show(int $showtime_id)
    {
        $showtime = Showtime::with(['movie', 'room.cinema'])->findOrFail($showtime_id);

        return response()->json($showtime);
    }

    public function update(Request $request, int $showtime_id)
    {
        $showtime = Showtime::findOrFail($showtime_id);

        $data = $request->validate([
            'movie_id' => 'nullable|integer|exists:movies,movie_id',
            'room_id' => 'nullable|integer|exists:rooms,room_id',
            'show_date' => 'nullable|date',
            'start_time' => 'nullable|date_format:H:i:s',
            'end_time' => 'nullable|date_format:H:i:s|after:start_time',
            'ticket_price' => 'nullable|numeric|min:0',
            'status' => 'nullable|string|max:50',
        ]);

        $showtime->update($data);

        return response()->json($showtime->load(['movie', 'room.cinema']));
    }

    public function destroy(int $showtime_id)
    {
        $showtime = Showtime::findOrFail($showtime_id);
        $showtime->delete();

        return response()->json(['message' => 'Suất chiếu đã được xóa.']);
    }
}

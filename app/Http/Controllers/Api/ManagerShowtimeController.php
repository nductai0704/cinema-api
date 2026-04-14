<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\Showtime;
use Illuminate\Http\Request;

class ManagerShowtimeController extends Controller
{
    public function index(Request $request)
    {
        $manager = $request->user();

        $query = Showtime::with(['movie', 'room.cinema'])
            ->whereHas('room', fn($query) => $query->where('cinema_id', $manager->cinema_id));

        if ($request->filled('movie_id')) {
            $query->where('movie_id', $request->input('movie_id'));
        }

        return response()->json($query->paginate(20));
    }

    public function store(Request $request)
    {
        $manager = $request->user();

        $data = $request->validate([
            'movie_id' => 'required|integer|exists:movies,movie_id',
            'room_id' => 'required|integer|exists:rooms,room_id',
            'show_date' => 'required|date',
            'start_time' => 'required|date_format:H:i:s',
            'end_time' => 'required|date_format:H:i:s|after:start_time',
            'ticket_price' => 'required|numeric|min:0',
            'status' => 'nullable|string|max:50',
        ]);

        $room = Room::where('room_id', $data['room_id'])
            ->where('cinema_id', $manager->cinema_id)
            ->firstOrFail();

        $showtime = Showtime::create($data);

        return response()->json($showtime->load(['movie', 'room.cinema']), 201);
    }

    public function show(Request $request, int $showtime_id)
    {
        $manager = $request->user();

        $showtime = Showtime::with(['movie', 'room.cinema'])
            ->whereHas('room', fn($query) => $query->where('cinema_id', $manager->cinema_id))
            ->findOrFail($showtime_id);

        return response()->json($showtime);
    }

    public function update(Request $request, int $showtime_id)
    {
        $manager = $request->user();

        $showtime = Showtime::whereHas('room', fn($query) => $query->where('cinema_id', $manager->cinema_id))
            ->findOrFail($showtime_id);

        $data = $request->validate([
            'movie_id' => 'nullable|integer|exists:movies,movie_id',
            'room_id' => 'nullable|integer|exists:rooms,room_id',
            'show_date' => 'nullable|date',
            'start_time' => 'nullable|date_format:H:i:s',
            'end_time' => 'nullable|date_format:H:i:s|after:start_time',
            'ticket_price' => 'nullable|numeric|min:0',
            'status' => 'nullable|string|max:50',
        ]);

        if (! empty($data['room_id'])) {
            Room::where('room_id', $data['room_id'])
                ->where('cinema_id', $manager->cinema_id)
                ->firstOrFail();
        }

        $showtime->update($data);

        return response()->json($showtime->load(['movie', 'room.cinema']));
    }

    public function destroy(Request $request, int $showtime_id)
    {
        $manager = $request->user();

        $showtime = Showtime::whereHas('room', fn($query) => $query->where('cinema_id', $manager->cinema_id))
            ->findOrFail($showtime_id);

        $showtime->delete();

        return response()->json(['message' => 'Suất chiếu đã được xóa.']);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cinema;
use App\Models\Room;
use Illuminate\Http\Request;

class AdminRoomController extends Controller
{
    public function index(Request $request)
    {
        $query = Room::with('cinema');

        if ($request->filled('cinema_id')) {
            $query->where('cinema_id', $request->input('cinema_id'));
        }

        return response()->json($query->paginate(20));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'cinema_id' => 'required|integer|exists:cinemas,cinema_id',
            'room_name' => 'required|string|max:100',
            'capacity' => 'nullable|integer',
            'room_type' => 'nullable|string|max:50',
            'status' => 'nullable|string|max:50',
        ]);

        $room = Room::create($data);

        return response()->json($room->load('cinema'), 201);
    }

    public function show(int $room_id)
    {
        $room = Room::with('cinema', 'seats')->findOrFail($room_id);

        return response()->json($room);
    }

    public function update(Request $request, int $room_id)
    {
        $room = Room::findOrFail($room_id);

        $data = $request->validate([
            'cinema_id' => 'nullable|integer|exists:cinemas,cinema_id',
            'room_name' => 'sometimes|required|string|max:100',
            'capacity' => 'nullable|integer',
            'room_type' => 'nullable|string|max:50',
            'status' => 'nullable|string|max:50',
        ]);

        if (isset($data['status']) && strcasecmp($data['status'], 'active') !== 0 && $room->hasFutureShowtimes()) {
            return response()->json(['message' => 'Không thể khóa phòng khi còn suất chiếu đang hoạt động hoặc sắp diễn ra.'], 422);
        }

        $room->update($data);

        return response()->json($room);
    }

    public function destroy(int $room_id)
    {
        $room = Room::findOrFail($room_id);
        $room->delete();

        return response()->json(['message' => 'Phòng chiếu đã được xóa.']);
    }
}

<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\Request;
use App\Http\Resources\RoomResource;

class ManagerRoomController extends Controller
{
    public function index()
    {
        // Trait autoscopes to the manager's cinema
        return RoomResource::collection(Room::withCount('seats')->paginate(10));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'room_name' => 'required|string|max:100',
            'capacity' => 'required|integer|min:1',
            'room_type' => 'required|string|max:50',
            'status' => 'nullable|string|in:active,inactive,maintenance',
        ]);

        // cinema_id is auto-assigned by BelongsToCinema creating event
        $room = Room::create($data);

        return new RoomResource($room);
    }

    public function show($id)
    {
        $room = Room::with('seats')->findOrFail($id);
        return response()->json($room); // Hoặc Resource nêú có
    }

    public function update(Request $request, $id)
    {
        $room = Room::findOrFail($id);

        $data = $request->validate([
            'room_name' => 'sometimes|required|string|max:100',
            'capacity' => 'sometimes|required|integer|min:1',
            'room_type' => 'sometimes|required|string|max:50',
            'status' => 'nullable|string|in:active,inactive,maintenance',
        ]);

        $room->update($data);

        return new RoomResource($room);
    }

    // Optional destroy if no showtimes attached, but let's keep it simple
}

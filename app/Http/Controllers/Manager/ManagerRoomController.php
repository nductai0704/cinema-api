<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\SeatLayout;
use App\Models\Seat;
use App\Models\Ticket;
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
            'seat_layout_id' => 'nullable|exists:seat_layouts,layout_id',
        ]);

        $roomData = \Illuminate\Support\Arr::except($data, ['seat_layout_id']);
        // cinema_id is auto-assigned by BelongsToCinema creating event
        $room = Room::create($roomData);

        if ($request->filled('seat_layout_id')) {
            $this->applySeatLayout($room->room_id, $request->seat_layout_id);
        }

        return new RoomResource($room);
    }

    private function applySeatLayout($roomId, $layoutId)
    {
        $layout = SeatLayout::find($layoutId);
        if (!$layout || !$layout->layout_data) return;

        $seatsToInsert = [];
        $now = now();

        foreach ($layout->layout_data as $row) {
            if (!is_array($row)) continue;
            foreach ($row as $cell) {
                if ($cell && isset($cell['row_label']) && isset($cell['seat_number'])) {
                    $seatsToInsert[] = [
                        'room_id' => $roomId,
                        'row_label' => $cell['row_label'],
                        'seat_number' => $cell['seat_number'],
                        'seat_type' => $cell['type'] ?? 'standard',
                        'grid_x' => $cell['grid_x'] ?? null,
                        'grid_y' => $cell['grid_y'] ?? null,
                        'pair_uuid' => $cell['pair_uuid'] ?? null,
                        'status' => 'active',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
        }

        if (count($seatsToInsert) > 0) {
            Seat::insert($seatsToInsert);
        }
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
            'seat_layout_id' => 'nullable|exists:seat_layouts,layout_id',
        ]);

        $roomData = \Illuminate\Support\Arr::except($data, ['seat_layout_id']);
        $room->update($roomData);

        if ($request->filled('seat_layout_id')) {
            $hasTickets = Ticket::whereHas('showtime', function($q) use ($room) {
                $q->where('room_id', $room->room_id);
            })->exists();

            if ($hasTickets) {
                return response()->json([
                    'message' => 'Không thể thay đổi sơ đồ vì phòng đã có giao dịch phát sinh.'
                ], 400);
            }

            // Xóa ghế cũ
            Seat::where('room_id', $room->room_id)->delete();
            // Bơm ghế mới
            $this->applySeatLayout($room->room_id, $request->seat_layout_id);
        }

        return new RoomResource($room);
    }

    // Optional destroy if no showtimes attached, but let's keep it simple
}

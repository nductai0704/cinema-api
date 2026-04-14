<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\Seat;
use Illuminate\Http\Request;

class ManagerSeatController extends Controller
{
    public function index(Request $request, int $room_id)
    {
        $manager = $request->user();

        $room = Room::where('room_id', $room_id)
            ->where('cinema_id', $manager->cinema_id)
            ->firstOrFail();

        return response()->json($room->seats()->orderBy('row_label')->orderBy('seat_number')->get());
    }

    public function bulkStore(Request $request, int $room_id)
    {
        $manager = $request->user();

        $room = Room::where('room_id', $room_id)
            ->where('cinema_id', $manager->cinema_id)
            ->firstOrFail();

        $data = $request->validate([
            'seats' => 'required|array|min:1',
            'seats.*.row_label' => 'required|string|max:10',
            'seats.*.seat_number' => 'required|integer|min:1',
            'seats.*.seat_type' => 'nullable|string|max:50',
            'seats.*.status' => 'nullable|string|max:50',
        ]);

        $seatDefinitions = [];
        foreach ($data['seats'] as $seat) {
            $key = strtoupper(trim($seat['row_label'])) . ':' . $seat['seat_number'];
            if (isset($seatDefinitions[$key])) {
                continue;
            }
            $seatDefinitions[$key] = [
                'row_label' => strtoupper(trim($seat['row_label'])),
                'seat_number' => $seat['seat_number'],
                'seat_type' => $seat['seat_type'] ?? 'standard',
                'status' => $seat['status'] ?? 'available',
            ];
        }

        $existingSeats = Seat::where('room_id', $room->room_id)
            ->whereIn('row_label', array_unique(array_column($seatDefinitions, 'row_label')))
            ->whereIn('seat_number', array_unique(array_column($seatDefinitions, 'seat_number')))
            ->get()
            ->mapWithKeys(fn($seat) => [strtoupper($seat->row_label) . ':' . $seat->seat_number => true])
            ->toArray();

        $createdSeats = [];
        foreach ($seatDefinitions as $key => $seatInfo) {
            if (isset($existingSeats[$key])) {
                continue;
            }

            $createdSeats[] = Seat::create(array_merge($seatInfo, [
                'room_id' => $room->room_id,
            ]));
        }

        if (empty($createdSeats)) {
            return response()->json(['message' => 'Không có ghế mới để tạo, tất cả ghế đã tồn tại.'], 409);
        }

        return response()->json($createdSeats, 201);
    }
}

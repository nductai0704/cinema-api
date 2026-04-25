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
            'seats.*.row_label' => 'sometimes|nullable|string|max:10',
            'seats.*.seat_number' => 'sometimes|nullable|integer|min:1',
            'seats.*.seat_type' => 'required|string|max:50',
            'seats.*.grid_x' => 'required|integer',
            'seats.*.grid_y' => 'required|integer',
            'seats.*.pair_uuid' => 'nullable|string|max:100',
            'seats.*.status' => 'nullable|string|max:50',
        ]);

        $seatDefinitions = [];
        foreach ($data['seats'] as $seat) {
            // Bỏ qua nếu đây là Lối đi (Aisle)
            if (isset($seat['seat_type']) && $seat['seat_type'] === 'aisle') {
                continue;
            }

            $key = strtoupper(trim($seat['row_label'])) . ':' . $seat['seat_number'];
            if (isset($seatDefinitions[$key])) {
                continue;
            }

            $seatDefinitions[$key] = [
                'row_label' => strtoupper(trim($seat['row_label'])),
                'seat_number' => $seat['seat_number'],
                'seat_type' => $seat['seat_type'] ?? 'standard',
                'grid_x' => $seat['grid_x'] ?? null,
                'grid_y' => $seat['grid_y'] ?? null,
                'pair_uuid' => $seat['pair_uuid'] ?? null,
                'status' => $seat['status'] ?? 'available',
            ];
        }

        // Kiểm tra trùng lặp dựa trên row_label và seat_number trong phòng
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

    public function syncLayout(Request $request, int $room_id)
    {
        $request->validate([
            'layout_id' => 'required|exists:seat_layouts,layout_id'
        ]);

        $manager = $request->user();
        $room = Room::where('room_id', $room_id)
            ->where('cinema_id', $manager->cinema_id)
            ->firstOrFail();

        $layout = \App\Models\SeatLayout::where('layout_id', $request->layout_id)
            ->where('cinema_id', $manager->cinema_id)
            ->firstOrFail();

        return \Illuminate\Support\Facades\DB::transaction(function () use ($room, $layout) {
            // 1. Xóa tất cả ghế cũ của phòng này để làm mới theo sơ đồ mới
            // Lưu ý: Chỉ nên làm việc này nếu rạp chưa có vé bán. Nếu có vé bán cần logic phức tạp hơn.
            if ($room->tickets()->exists()) {
                return response()->json(['message' => 'Không thể đổi sơ đồ vì rạp đã có vé được bán ra.'], 422);
            }

            $room->seats()->delete();

            $createdSeats = [];
            foreach ($layout->layout_data as $seat) {
                // QUAN TRỌNG: Bỏ qua nếu là Lối đi (Aisle)
                if (isset($seat['type']) && $seat['type'] === 'aisle') {
                    continue;
                }

                $createdSeats[] = Seat::create([
                    'room_id' => $room->room_id,
                    'row_label' => $seat['label'] ? substr($seat['label'], 0, 1) : null, // A, B, C...
                    'seat_number' => $seat['label'] ? (int)substr($seat['label'], 1) : 0, // 1, 2, 3...
                    'seat_type' => $seat['type'] ?? 'regular',
                    'grid_x' => $seat['col'],
                    'grid_y' => $seat['row'],
                    'pair_uuid' => $seat['pair'] ?? null,
                    'status' => 'available'
                ]);
            }

            // Cập nhật sức chứa của phòng dựa trên số ghế thật
            $room->update(['capacity' => count($createdSeats)]);

            return response()->json([
                'message' => 'Đã đồng bộ sơ đồ thành công!',
                'seats_count' => count($createdSeats)
            ]);
        });
    }
}

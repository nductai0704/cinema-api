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
        return RoomResource::collection(Room::with('cinema', 'roomType', 'seatLayout')->withCount('seats')->paginate(10));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'room_name'       => 'required|string|max:100',
            'capacity'        => 'required|integer|min:1',
            'room_type_id'    => 'required|exists:room_types,room_type_id',
            'seat_layout_id'  => 'nullable|exists:seat_layouts,layout_id',
            'status'          => 'nullable|string|in:active,inactive,maintenance',
        ]);

        // seat_layout_id đã có trong $fillable nên tạo thẳng vào 1 lần luôn
        $room = Room::create($data); // cinema_id auto-assigned by BelongsToCinema

        // Đảm bảo seat_layout_id được lưu đúng vào DB
        if (!empty($data['seat_layout_id'])) {
            // Dùng DB raw để chắc chắn 100% ghi vào DB
            \Illuminate\Support\Facades\DB::table('rooms')
                ->where('room_id', $room->room_id)
                ->update(['seat_layout_id' => $data['seat_layout_id']]);

            $this->applySeatLayout($room->room_id, $data['seat_layout_id']);
        }

        // Reload từ DB để response trả về dữ liệu thật (không phải in-memory)
        $room = Room::with('roomType', 'seatLayout')->find($room->room_id);
        return new RoomResource($room);
    }

    private function applySeatLayout($roomId, $layoutId)
    {
        $layout = SeatLayout::find($layoutId);
        if (!$layout || empty($layout->layout_data)) return;

        $seatsToInsert = [];
        $now = now();

        // layout_data là mảng 2 chiều thẳng từ state grid của FE:
        // [ { label: 'A', seats: [ { id: 'A1', type: 'regular', pair: null }, ... ] }, ... ]
        foreach ($layout->layout_data as $y => $row) {
            if (!is_array($row) || !isset($row['seats']) || !is_array($row['seats'])) continue;

            $rowLabel = strtoupper(trim($row['label'] ?? ''));
            $rowPairUuids = []; // Lưu trữ uuid cho ghế đôi trong cùng 1 hàng
            $seatCounter = 1;   // Đánh số tuần tự, BỎ QUA lối đi và ô trống

            foreach ($row['seats'] as $x => $seat) {
                // Bỏ qua nếu type null/empty (ô trống)
                if (empty($seat['type'])) continue;

                $type = strtolower($seat['type']);

                // ✅ BỎ QUA LỐI ĐI - không tạo ghế cho ô aisle
                if ($type === 'aisle') continue;

                if ($type === 'regular') $type = 'standard';

                $pairUuid = null;
                if ($type === 'double') {
                    if (isset($rowPairUuids[$x])) {
                        // Đã được ghế kia đặt UUID từ vòng lặp trước
                        $pairUuid = $rowPairUuids[$x];
                    } else {
                        // Tự cấp UUID mới và chia sẻ cho ghế cặp
                        $pairUuid = (string) \Illuminate\Support\Str::uuid();
                        $rowPairUuids[$x] = $pairUuid;

                        if (isset($seat['pair']) && $seat['pair'] !== null) {
                            $rowPairUuids[$seat['pair']] = $pairUuid;
                        }
                    }
                }

                $seatsToInsert[] = [
                    'room_id'    => $roomId,
                    'row_label'  => $rowLabel,
                    'seat_number'=> $seatCounter, // ✅ Số tuần tự (A1, A2, A3...) - không bị lệch bởi aisle
                    'seat_type'  => $type,
                    'grid_x'     => $x,           // Vẫn giữ vị trí grid để FE render đúng vị trí
                    'grid_y'     => $y,
                    'pair_uuid'  => $pairUuid,
                    'status'     => 'active',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                // Ghế đôi tốn 2 số (1 + 2 = A1, A2)
                $seatCounter += ($type === 'double') ? 2 : 1;
            }
        }

        if (count($seatsToInsert) > 0) {
            Seat::insert($seatsToInsert);
        }
    }

    public function show($id)
    {
        $room = Room::with('seats', 'roomType')->findOrFail($id);
        return response()->json($room); // Hoặc Resource nêú có
    }

    public function update(Request $request, $id)
    {
        $room = Room::findOrFail($id);

        $data = $request->validate([
            'room_name' => 'sometimes|required|string|max:100',
            'capacity' => 'sometimes|required|integer|min:1',
            'room_type_id' => 'sometimes|required|exists:room_types,room_type_id',
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

    public function toggleStatus(Request $request, $id)
    {
        $hasFuture = Room::findOrFail($id)->showtimes()
            ->where('status', 'active')
            ->where(function ($query) {
                $query->where('show_date', '>', now()->toDateString())
                    ->orWhere(function ($query) {
                        $query->where('show_date', now()->toDateString())
                            ->where('end_time', '>', now()->format('H:i:s'));
                    });
            })
            ->exists();

        $room = Room::findOrFail($id);
        $newStatus = strtolower($room->status) === 'active' ? 'inactive' : 'active';

        if ($newStatus === 'inactive' && $hasFuture) {
            return response()->json(['message' => 'Không thể khóa phòng khi còn suất chiếu đang hoạt động hoặc sắp diễn ra.'], 422);
        }

        $room->update(['status' => $newStatus]);

        return response()->json([
            'message' => $newStatus === 'active' ? 'Phòng đã chuyển sang trạng thái Hoạt động.' : 'Phòng đã chuyển sang Bản nháp.',
            'status' => $newStatus
        ]);
    }
}

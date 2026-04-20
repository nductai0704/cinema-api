<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\Seat;
use Illuminate\Http\Request;

class ManagerSeatController extends Controller
{
    public function index($roomId)
    {
        $room = Room::findOrFail($roomId);
        return response()->json($room->seats);
    }

    /**
     * Thuật toán sinh ma trận ghế ảo diệu
     * VD Payload:
     * {
     *    "rows": 10,                 // Số hàng (VD: 10 = A đến J)
     *    "seats_per_row": 15,        // 15 ghế mỗi hàng
     *    "vip_rows": ["G", "H"],     // Khai báo hàng VIP
     *    "couple_rows": ["J"]        // Khai báo hàng Ghế Đôi ở cuối
     * }
     */
    public function bulkStore(Request $request, $roomId)
    {
        $room = Room::findOrFail($roomId);

        // Kiểm tra xem phòng đã có ghế chưa hoặc có vé đã bán chưa
        if ($room->seats()->count() > 0) {
            // Giả sử chỉ kiểm tra cơ bản. Nếu có vé, chặn lại.
            // Nếu không, xoá đi làm lại
            $hasTickets = \App\Models\Ticket::whereHas('showtime', function($q) use ($roomId) {
                $q->where('room_id', $roomId);
            })->exists();

            if ($hasTickets) {
                return response()->json(['message' => 'Phòng này đã có vé bán ra, không thể thay đổi sơ đồ ghế!'], 409);
            }

            // Xoá ghế cũ để reset
            $room->seats()->delete();
        }

        $data = $request->validate([
            'rows' => 'required|integer|min:1|max:26', // Tối đa 26 hàng A-Z
            'seats_per_row' => 'required|integer|min:1|max:50',
            'vip_rows' => 'nullable|array',
            'couple_rows' => 'nullable|array',
        ]);

        $alphabet = range('A', 'Z');
        $numberOfRows = $data['rows'];
        $seatsPerRow = $data['seats_per_row'];
        
        $vipRows = $data['vip_rows'] ?? [];
        $coupleRows = $data['couple_rows'] ?? [];

        $seatsToInsert = [];
        $now = now();

        for ($i = 0; $i < $numberOfRows; $i++) {
            $rowLabel = $alphabet[$i];
            
            // Xác định loại ghế của hàng này
            $seatType = 'normal';
            if (in_array($rowLabel, $vipRows)) {
                $seatType = 'vip';
            } elseif (in_array($rowLabel, $coupleRows)) {
                $seatType = 'couple';
            }

            // Nếu hàng là couple, số ghế có thể chỉ bằng một nửa (tuỳ setup thực tế), 
            // nhưng ở đây cứ tạo đủ cột, giao diện FE sẽ rải ghế
            for ($j = 1; $j <= $seatsPerRow; $j++) {
                $seatsToInsert[] = [
                    'room_id' => $room->room_id,
                    'row_label' => $rowLabel,
                    'seat_number' => $j,
                    'seat_type' => $seatType,
                    'status' => 'active',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        // Tối ưu Bulk Insert để tạo 500 ghế chỉ mất 0.1s
        Seat::insert($seatsToInsert);

        // Update room capacity
        $room->update(['capacity' => count($seatsToInsert)]);

        return response()->json([
            'message' => 'Tự động tạo sơ đồ ghế thành công!',
            'total_seats' => count($seatsToInsert)
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SeatHold;
use App\Models\Showtime;
use App\Models\Ticket;
use Illuminate\Http\Request;

class SeatHoldController extends Controller
{
    public function index(int $showtime_id)
    {
        $showtime = Showtime::findOrFail($showtime_id);

        $holds = SeatHold::where('showtime_id', $showtime->showtime_id)
            ->whereIn('status', ['active', 'held', 'hold'])
            ->where(function ($query) {
                $query->where('expired_time', '>', now()->subMinutes(10))
                      ->orWhereNull('expired_time');
            })
            ->with('seat')
            ->get();

        // Map lại dữ liệu để trả về seat_label chuẩn cho FE
        $result = $holds->map(function ($hold) {
            return [
                'hold_id' => $hold->hold_id,
                'showtime_id' => $hold->showtime_id,
                'seat_id' => $hold->seat_id,
                'seat_label' => $hold->seat ? ($hold->seat->row_label . $hold->seat->seat_number) : null,
                'status' => $hold->status,
                'expired_time' => $hold->expired_time
            ];
        });

        return response()->json($result);
    }

    public function store(Request $request, int $showtime_id)
    {
        $request->validate([
            'seat_ids' => 'required_without:seat_labels|array',
            'seat_labels' => 'required_without:seat_ids|array',
        ]);

        $showtime = Showtime::findOrFail($showtime_id);

        // Chuyển đổi seat_labels thành seat_ids nếu cần
        if ($request->has('seat_labels')) {
            $allSeatsInRoom = \App\Models\Seat::where('room_id', $showtime->room_id)->get();
            $inputLabels = array_map(function($l) { return strtoupper(trim((string)$l)); }, $request->seat_labels);
            
            $seatIds = $allSeatsInRoom->filter(function($seat) use ($inputLabels) {
                $currentLabel = strtoupper(trim($seat->row_label . $seat->seat_number));
                $currentId = (string)$seat->seat_id;
                
                // Chấp nhận nếu input khớp với Label (A4) HOẶC khớp với ID (747)
                return in_array($currentLabel, $inputLabels) || in_array($currentId, $inputLabels);
            })
            ->pluck('seat_id')
            ->toArray();
            
            // Nếu gửi label lên mà không tìm thấy bất kỳ ghế nào khớp
            if (empty($seatIds)) {
                return response()->json([
                    'message' => 'Không tìm thấy ghế tương ứng với các nhãn: ' . implode(', ', $request->seat_labels),
                    'debug' => [
                        'room_id' => $showtime->room_id,
                        'room_name' => $showtime->room?->room_name,
                        'received_labels' => $request->seat_labels,
                        'available_seats_in_room' => $allSeatsInRoom->map(function($s) {
                            return $s->row_label . $s->seat_number;
                        })->take(20)->toArray() // Trả về 20 nhãn đầu tiên để kiểm tra định dạng
                    ]
                ], 404);
            }
        } else {
            $seatIds = $request->seat_ids;
        }

        $seatIds = array_unique($seatIds);

        $existingBooked = Ticket::where('showtime_id', $showtime->showtime_id)
            ->whereIn('seat_id', $seatIds)
            ->exists();

        if ($existingBooked) {
            return response()->json(['message' => 'Một hoặc nhiều ghế đã được đặt trước.'], 422);
        }

        $existingHold = SeatHold::where('showtime_id', $showtime->showtime_id)
            ->whereIn('seat_id', $seatIds)
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('expired_time')
                    ->orWhere('expired_time', '>', now());
            })
            ->where('user_id', '!=', $request->user()->user_id)
            ->exists();

        if ($existingHold) {
            return response()->json(['message' => 'Một hoặc nhiều ghế đang bị giữ bởi khách khác.'], 422);
        }

        $seats = \App\Models\Seat::whereIn('seat_id', $seatIds)->get()->keyBy('seat_id');
        $holds = [];
        foreach ($seatIds as $seatId) {
            $seat = $seats->get($seatId);
            $holds[] = SeatHold::create([
                'showtime_id' => $showtime->showtime_id,
                'seat_id' => $seatId,
                'user_id' => $request->user()->user_id,
                'hold_time' => now(),
                'expired_time' => now()->addMinutes(7),
                'status' => 'active',
            ]);

            event(new \App\Events\SeatStatusChanged(
                $showtime->showtime_id,
                $seatId,
                'held',
                $request->user()->user_id,
                $seat ? ($seat->row_label . $seat->seat_number) : null
            ));
        }

        return response()->json($holds, 201);
    }

    /**
     * Hủy giữ ghế hàng loạt cho một suất chiếu
     */
    public function bulkDestroy(Request $request, int $showtime_id)
    {
        $request->validate([
            'seat_ids' => 'required_without:seat_labels|array',
            'seat_labels' => 'required_without:seat_ids|array',
        ]);

        $showtime = Showtime::findOrFail($showtime_id);
        
        if ($request->has('seat_labels')) {
            $seatIds = \App\Models\Seat::where('room_id', $showtime->room_id)
                ->get()
                ->filter(function($seat) use ($request) {
                    return in_array($seat->row_label . $seat->seat_number, $request->seat_labels);
                })
                ->pluck('seat_id')
                ->toArray();
        } else {
            $seatIds = $request->seat_ids;
        }

        $holds = SeatHold::where('showtime_id', $showtime_id)
                        ->where('user_id', $request->user()->user_id)
                        ->whereIn('seat_id', $seatIds)
                        ->get();

        foreach ($holds as $hold) {
            $seat = \App\Models\Seat::find($hold->seat_id);
            event(new \App\Events\SeatStatusChanged(
                $showtime_id,
                $hold->seat_id,
                'released',
                null,
                $seat ? ($seat->row_label . $seat->seat_number) : null
            ));
            $hold->delete();
        }

        return response()->json(['message' => 'Đã giải phóng ghế.']);
    }

    public function destroy(Request $request, int $hold_id)
    {
        $hold = SeatHold::findOrFail($hold_id);

        if ($request->user()->user_id !== $hold->user_id && ! $request->user()->isAdmin()) {
            return response()->json(['message' => 'Bạn không có quyền hủy giữ ghế này.'], 403);
        }

        $hold->status = 'cancelled';
        $hold->save();

        $seat = \App\Models\Seat::find($hold->seat_id);
        event(new \App\Events\SeatStatusChanged(
            $hold->showtime_id,
            $hold->seat_id,
            'released',
            null,
            $seat ? ($seat->row_label . $seat->seat_number) : null
        ));

        return response()->json(['message' => 'Giữ ghế đã được hủy.']);
    }
}

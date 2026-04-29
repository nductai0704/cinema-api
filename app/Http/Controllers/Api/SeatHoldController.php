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
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('expired_time')
                    ->orWhere('expired_time', '>', now());
            })
            ->with('user')
            ->get();

        return response()->json($holds);
    }

    public function store(Request $request, int $showtime_id)
    {
        $data = $request->validate([
            'seat_ids' => 'required|array|min:1',
            'seat_ids.*' => 'required|integer|exists:seats,seat_id',
        ]);

        $showtime = Showtime::findOrFail($showtime_id);
        $seatIds = array_unique($data['seat_ids']);

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

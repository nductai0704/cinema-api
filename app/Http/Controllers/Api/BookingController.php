<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookingRequest;
use App\Models\Booking;
use App\Models\BookingCombo;
use App\Models\Combo;
use App\Models\SeatHold;
use App\Models\Showtime;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BookingController extends Controller
{
    public function store(StoreBookingRequest $request)
    {
        $data = $request->validated();
        $user = $request->user();
        $showtime = Showtime::findOrFail($data['showtime_id']);
        $seatIds = array_unique($data['seat_ids']);

        $alreadyBooked = Ticket::where('showtime_id', $showtime->showtime_id)
            ->whereIn('seat_id', $seatIds)
            ->exists();

        if ($alreadyBooked) {
            return response()->json(['message' => 'Một hoặc nhiều ghế đã bị đặt trước.'], 422);
        }

        $alreadyHeld = SeatHold::where('showtime_id', $showtime->showtime_id)
            ->whereIn('seat_id', $seatIds)
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('expired_time')
                    ->orWhere('expired_time', '>', now());
            })
            ->where('user_id', '!=', $user->user_id)
            ->exists();

        if ($alreadyHeld) {
            return response()->json(['message' => 'Một hoặc nhiều ghế đang được giữ.'], 422);
        }

        $seats = \App\Models\Seat::whereIn('seat_id', $seatIds)->get()->keyBy('seat_id');
        $totalTicketAmount = 0;
        $ticketDataList = [];

        foreach ($seatIds as $seatId) {
            $seat = $seats->get($seatId);
            $type = $seat ? strtolower($seat->seat_type) : 'normal';

            if (in_array($type, ['couple', 'double'])) {
                $price = $showtime->price_double ?: $showtime->ticket_price;
            } elseif ($type === 'vip') {
                $price = $showtime->price_vip ?: $showtime->ticket_price;
            } else {
                $price = $showtime->price_standard ?: $showtime->ticket_price;
            }

            $totalTicketAmount += $price;
            $ticketDataList[$seatId] = $price;
        }

        $totalAmount = $totalTicketAmount;
        
        $booking = Booking::create([
            'user_id' => $user->user_id,
            'booking_time' => now(),
            'total_amount' => $totalAmount,
            'status' => 'confirmed',
        ]);

        foreach ($seatIds as $seatId) {
            Ticket::create([
                'booking_id' => $booking->booking_id,
                'showtime_id' => $showtime->showtime_id,
                'seat_id' => $seatId,
                'ticket_code' => Str::upper(Str::random(10)),
                'qr_code' => (string) Str::uuid(),
                'ticket_price' => $ticketDataList[$seatId],
                'status' => 'booked',
            ]);
        }

        SeatHold::where('showtime_id', $showtime->showtime_id)
            ->whereIn('seat_id', $seatIds)
            ->where('user_id', $user->user_id)
            ->where('status', 'active')
            ->update(['status' => 'booked']);

        if (! empty($data['combos'])) {
            foreach ($data['combos'] as $comboItem) {
                $combo = Combo::findOrFail($comboItem['combo_id']);
                BookingCombo::create([
                    'booking_id' => $booking->booking_id,
                    'combo_id' => $combo->combo_id,
                    'quantity' => $comboItem['quantity'],
                    'price' => $combo->price * $comboItem['quantity'],
                ]);
                $totalAmount += $combo->price * $comboItem['quantity'];
            }

            $booking->update(['total_amount' => $totalAmount]);
        }

        if (! empty($data['payment_method'])) {
            $booking->payment()->create([
                'payment_method' => $data['payment_method'],
                'amount' => $totalAmount,
                'payment_status' => 'paid',
                'payment_time' => now(),
            ]);
        }

        $booking->load(['tickets.seat', 'tickets.showtime.movie', 'combos.combo', 'payment', 'user']);

        return response()->json($booking, 201);
    }

    public function show(int $booking_id)
    {
        $booking = Booking::with(['user', 'tickets.seat', 'tickets.showtime.movie', 'combos.combo', 'payment'])
            ->findOrFail($booking_id);

        return response()->json($booking);
    }

    public function userBookings(Request $request)
    {
        $user = $request->user();

        $bookings = Booking::with(['tickets.showtime.movie', 'combos.combo'])
            ->where('user_id', $user->user_id)
            ->orderBy('booking_time', 'desc')
            ->get();

        return response()->json($bookings);
    }
}

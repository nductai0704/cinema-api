<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Showtime;
use App\Models\Seat;
use App\Models\SeatHold;
use App\Models\Booking;
use App\Models\BookingCombo;
use App\Models\Ticket;
use App\Models\CinemaCombo;
use App\Models\Combo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CustomerBookingController extends Controller
{
    /**
     * 1. API Giữ ghế (Hold Seats)
     * Khoá ghế trong 5 phút để thanh toán
     */
    public function holdSeats(Request $request)
    {
        $request->validate([
            'showtime_id' => 'required|exists:showtimes,showtime_id',
            'seat_ids' => 'required|array',
            'seat_ids.*' => 'exists:seats,seat_id'
        ]);

        $user = Auth::user();
        $showtimeId = $request->showtime_id;
        $seatIds = $request->seat_ids;

        return DB::transaction(function () use ($user, $showtimeId, $seatIds) {
            // Pessimistic Locking: Khoá các hàng này để kiểm tra trạng thái duy nhất
            // Kiểm tra xem có ghế nào đã được bán (Tickets) hoặc đang được giữ (SeatHold) bởi người khác hay không
            
            $soldSeats = Ticket::where('showtime_id', $showtimeId)
                               ->whereIn('seat_id', $seatIds)
                               ->where('status', '!=', 'cancelled')
                               ->lockForUpdate()
                               ->exists();

            if ($soldSeats) {
                return response()->json(['message' => 'Một hoặc nhiều ghế bạn chọn đã có người mua.'], 422);
            }

            $heldByOthers = SeatHold::where('showtime_id', $showtimeId)
                                    ->whereIn('seat_id', $seatIds)
                                    ->where('expired_time', '>', now())
                                    ->where('status', 'held')
                                    ->where('user_id', '!=', $user->user_id) // Cho phép chính mình giữ lại hoặc cập nhật
                                    ->lockForUpdate()
                                    ->exists();

            if ($heldByOthers) {
                return response()->json(['message' => 'Ghế đã có người khác nhanh tay hơn chọn trước.'], 422);
            }

            // Xoá các lượt giữ cũ của User cho chính các ghế này (nếu có) để làm mới thời gian
            SeatHold::where('user_id', $user->user_id)
                    ->whereIn('seat_id', $seatIds)
                    ->where('showtime_id', $showtimeId)
                    ->delete();

            $holds = [];
            $expiresAt = now()->addMinutes(5);

            foreach ($seatIds as $id) {
                $holds[] = [
                    'user_id' => $user->user_id,
                    'showtime_id' => $showtimeId,
                    'seat_id' => $id,
                    'hold_time' => now()->toDateTimeString(),
                    'expired_time' => $expiresAt->toDateTimeString(),
                    'status' => 'held'
                ];
            }

            SeatHold::insert($holds);

            return response()->json([
                'message' => 'Giữ ghế thành công! Bạn có 5 phút để hoàn tất đặt vé.',
                'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
                'seat_ids' => $seatIds
            ]);
        });
    }

    /**
     * 2. API Tạo Đơn hàng (Create Booking)
     */
    public function createBooking(Request $request)
    {
        $request->validate([
            'showtime_id' => 'required|exists:showtimes,showtime_id',
            'seat_ids' => 'required|array',
            'combos' => 'nullable|array',
            'combos.*.combo_id' => 'required|exists:combos,combo_id',
            'combos.*.quantity' => 'required|integer|min:1',
        ]);

        $user = Auth::user();
        $showtime = Showtime::with('room.cinema')->findOrFail($request->showtime_id);
        $cinemaId = $showtime->room->cinema_id;
        $seatIds = $request->seat_ids;

        return DB::transaction(function () use ($user, $showtime, $seatIds, $request, $cinemaId) {
            // Kiểm tra xem ghế còn đang được giữ bởi chính User này không
            $activeHolds = SeatHold::where('user_id', $user->user_id)
                                   ->where('showtime_id', $showtime->showtime_id)
                                   ->whereIn('seat_id', $seatIds)
                                   ->where('expired_time', '>', now())
                                   ->count();

            if ($activeHolds < count($seatIds)) {
                return response()->json(['message' => 'Thời gian giữ ghế đã hết, vui lòng chọn lại ghế.'], 422);
            }

            $totalAmount = 0;
            $ticketData = [];
            $basePrice = $showtime->ticket_price;
            $seatConfigs = config('cinema.seat_types');

            // Tính tiền vé + Phụ thu
            $seats = Seat::whereIn('seat_id', $seatIds)->get();
            foreach ($seats as $seat) {
                $surcharge = $seatConfigs[$seat->seat_type]['surcharge'] ?? 0;
                $finalTicketPrice = $basePrice + $surcharge;

                $totalAmount += $finalTicketPrice;
                $ticketCode = 'TKT-' . strtoupper(Str::random(10));
                $ticketData[] = [
                    'showtime_id' => $showtime->showtime_id,
                    'seat_id' => $seat->seat_id,
                    'ticket_code' => $ticketCode,
                    'qr_code' => 'QR-' . $ticketCode,
                    'ticket_price' => $finalTicketPrice,
                    'status' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // Tính tiền Combo (Lấy giá local từ cinema_combos)
            $bookingCombos = [];
            if ($request->has('combos')) {
                foreach ($request->combos as $c) {
                    // Ưu tiên giá tại rạp đó
                    $localCombo = CinemaCombo::where('cinema_id', $cinemaId)
                                             ->where('combo_id', $c['combo_id'])
                                             ->first();
                    
                    if (!$localCombo) {
                        $globalCombo = Combo::findOrFail($c['combo_id']);
                        $price = $globalCombo->price;
                    } else {
                        $price = $localCombo->price;
                    }

                    $totalAmount += ($price * $c['quantity']);
                    $bookingCombos[] = [
                        'combo_id' => $c['combo_id'],
                        'quantity' => $c['quantity'],
                        'price' => $price,
                    ];
                }
            }

            // Tạo Booking
            $booking = Booking::create([
                'user_id' => $user->user_id,
                'booking_time' => now(),
                'total_amount' => $totalAmount,
                'status' => 'pending'
            ]);

            // Tạo Tickets và BookingCombos
            foreach ($ticketData as &$t) {
                $t['booking_id'] = $booking->booking_id;
            }
            Ticket::insert($ticketData);

            foreach ($bookingCombos as $bc) {
                $booking->combos()->create($bc);
            }

            return response()->json([
                'message' => 'Đơn hàng đã được khởi tạo!',
                'booking_id' => $booking->booking_id,
                'total_amount' => $totalAmount,
                'status' => 'pending'
            ]);
        });
    }

    /**
     * 3. API Xác nhận thanh toán (Mock)
     */
    public function confirmPayment(Request $request, $bookingId)
    {
        $user = Auth::user();
        $booking = Booking::where('user_id', $user->user_id)
                          ->where('booking_id', $bookingId)
                          ->firstOrFail();

        if ($booking->status === 'completed') {
            return response()->json(['message' => 'Đơn hàng này đã thanh toán rồi.'], 400);
        }

        return DB::transaction(function () use ($booking) {
            // Update trạng thái
            $booking->update(['status' => 'completed']);
            $booking->tickets()->update(['status' => 'active']);

            // Xoá sạch SeatHolds để giải phóng database (Giải phóng ghế thật sự)
            $ticketSeatIds = $booking->tickets->pluck('seat_id')->toArray();
            $showtimeId = $booking->tickets->first()->showtime_id;

            SeatHold::where('showtime_id', $showtimeId)
                    ->whereIn('seat_id', $ticketSeatIds)
                    ->delete();

            return response()->json([
                'message' => 'Thanh toán thành công! Chúc bạn xem phim vui vẻ.',
                'booking_id' => $booking->booking_id,
                'status' => 'completed'
            ]);
        });
    }
}

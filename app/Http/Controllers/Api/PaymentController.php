<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function store(Request $request, int $booking_id)
    {
        $booking = Booking::with('payment')->findOrFail($booking_id);
        $user = $request->user();

        if ($booking->user_id !== $user->user_id && ! $user->isAdmin()) {
            return response()->json(['message' => 'Bạn không có quyền thanh toán booking này.'], 403);
        }

        if ($booking->payment) {
            return response()->json(['message' => 'Booking này đã thanh toán.'], 409);
        }

        $data = $request->validate([
            'payment_method' => 'required|string|max:50',
            'amount' => 'nullable|numeric|min:0',
        ]);

        $amount = $data['amount'] ?? $booking->total_amount;

        $payment = $booking->payment()->create([
            'payment_method' => $data['payment_method'],
            'amount' => $amount,
            'payment_status' => 'paid',
            'payment_time' => now(),
        ]);

        $booking->update(['status' => 'confirmed']);

        return response()->json(['booking' => $booking, 'payment' => $payment], 201);
    }

    public function show(Request $request, int $booking_id)
    {
        $booking = Booking::with('payment')->findOrFail($booking_id);
        $user = $request->user();

        if ($booking->user_id !== $user->user_id && ! $user->isAdmin()) {
            return response()->json(['message' => 'Bạn không có quyền xem payment này.'], 403);
        }

        return response()->json($booking->payment);
    }
}

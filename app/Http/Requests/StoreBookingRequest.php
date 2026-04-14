<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'showtime_id' => 'required|integer|exists:showtimes,showtime_id',
            'seat_ids' => 'required|array|min:1',
            'seat_ids.*' => 'required|integer|exists:seats,seat_id',
            'combos' => 'sometimes|array',
            'combos.*.combo_id' => 'required_with:combos|integer|exists:combos,combo_id',
            'combos.*.quantity' => 'required_with:combos|integer|min:1',
            'payment_method' => 'required|string',
        ];
    }
}

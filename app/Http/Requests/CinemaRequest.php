<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CinemaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Phân quyền do Middleware và Policy lo
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $cinemaId = $this->route('cinema') ? $this->route('cinema')->cinema_id : null;

        return [
            'cinema_name' => 'required|string|max:255|unique:cinemas,cinema_name,' . $cinemaId . ',cinema_id',
            'region_id' => 'required|exists:regions,region_id',
            'address' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'status' => 'nullable|string|in:active,inactive',
        ];
    }

    public function messages(): array
    {
        return [
            'cinema_name.required' => 'Tên rạp không được để trống.',
            'cinema_name.unique' => 'Tên rạp này đã tồn tại.',
            'region_id.required' => 'Vui lòng chọn khu vực.',
            'region_id.exists' => 'Khu vực không hợp lệ.',
            'address.required' => 'Địa chỉ không được để trống.',
            'phone.required' => 'Số điện thoại không được để trống.',
        ];
    }
}

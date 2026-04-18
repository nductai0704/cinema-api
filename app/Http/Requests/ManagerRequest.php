<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ManagerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $userId = $this->route('manager') ? $this->route('manager')->user_id : null;

        $rules = [
            'username' => 'required|string|max:100|unique:users,username,' . $userId . ',user_id',
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $userId . ',user_id',
            'phone' => 'nullable|string|max:20',
            'cinema_id' => 'required|exists:cinemas,cinema_id',
        ];

        if ($this->isMethod('post')) {
            $rules['password'] = 'required|string|min:6';
        } else {
            $rules['password'] = 'nullable|string|min:6';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'username.required' => 'Tên đăng nhập không được để trống.',
            'username.unique' => 'Tên đăng nhập đã tồn tại.',
            'email.required' => 'Email không được để trống.',
            'email.unique' => 'Email đã tồn tại.',
            'cinema_id.required' => 'Bắt buộc phải gán Manager vào một rạp.',
            'cinema_id.exists' => 'Rạp không hợp lệ.',
            'password.required' => 'Mật khẩu không được để trống.',
        ];
    }
}

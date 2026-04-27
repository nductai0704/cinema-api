<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function me(Request $request)
    {
        return response()->json($request->user()->load('role', 'cinema'));
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'full_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255|unique:users,email,' . $user->user_id . ',user_id',
            'phone' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|string|max:10',
            'current_password' => 'nullable|required_with:password|string',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        if (isset($data['password'])) {
            // Xác thực mật khẩu cũ - Dùng password_hash theo DB của dự án
            if (!\Illuminate\Support\Facades\Hash::check($data['current_password'], $user->password_hash)) {
                return response()->json([
                    'message' => 'Mật khẩu hiện tại không chính xác.'
                ], 422);
            }
            $data['password_hash'] = \Illuminate\Support\Facades\Hash::make($data['password']);
        }

        // Loại bỏ các field không có trong DB hoặc đã xử lý
        unset($data['current_password']);
        unset($data['password']);
        
        $user->update($data);

        return response()->json($user->fresh()->load('role', 'cinema'));
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $request->user();

        if (!\Illuminate\Support\Facades\Hash::check($request->current_password, $user->password_hash)) {
            return response()->json([
                'message' => 'Mật khẩu hiện tại không chính xác.'
            ], 422);
        }

        $user->update([
            'password_hash' => \Illuminate\Support\Facades\Hash::make($request->new_password)
        ]);

        return response()->json([
            'message' => 'Đổi mật khẩu thành công.'
        ]);
    }
}

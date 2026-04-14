<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        $user = User::where('username', $request->input('username'))
            ->with('cinema')
            ->first();

        if (! $user || ! Hash::check($request->input('password'), $user->password_hash)) {
            return response()->json(['message' => 'Thông tin đăng nhập không chính xác.'], 401);
        }

        if (! $user->isActive()) {
            return response()->json(['message' => 'Tài khoản của bạn đã bị khóa.'], 403);
        }

        if (($user->isManager() || $user->isStaff()) && ! $user->isCinemaActive()) {
            return response()->json(['message' => 'Rạp của bạn đang bị khóa hoặc không tồn tại.'], 403);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);
    }

    public function register(RegisterRequest $request)
    {
        $data = $request->validated();
        $data['password_hash'] = Hash::make($data['password']);
        unset($data['password']);

        $customerRoleId = Role::where('role_name', User::ROLE_CUSTOMER)->value('role_id');
        $data['role_id'] = $customerRoleId ?: null;

        $user = User::create($data);
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Đã đăng xuất.']);
    }
}

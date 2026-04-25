<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;

class AuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        // Manager/Staff có thể dùng username, nhưng Customer nên dùng Email như mục tiêu của bạn
        $loginField = $request->input('username'); 
        
        $user = User::where('username', $loginField)
            ->orWhere('email', $loginField)
            ->with(['cinema', 'role'])
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
        $data['username'] = $data['username'] ?? explode('@', $data['email'])[0];
        unset($data['password']);

        $customerRoleId = Role::where('role_name', User::ROLE_CUSTOMER)->value('role_id');
        $data['role_id'] = $customerRoleId ?: null;
        $data['status'] = 'active';

        $user = User::create($data);

        // ✅ KÍCH HOẠT SỰ KIỆN GỬI MAIL XÁC THỰC
        event(new Registered($user));

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'message' => 'Đăng ký thành công! Vui lòng kiểm tra Gmail để xác thực tài khoản.',
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    public function verify(Request $request) {
        $user = User::findOrFail($request->route('id'));

        if (! hash_equals((string) $request->route('hash'), sha1($user->getEmailForVerification()))) {
             return response()->json(['message' => 'Mã xác thực không hợp lệ.'], 400);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email đã được xác thực trước đó.']);
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return response()->json(['message' => 'Xác thực Email thành công!']);
    }

    public function resendVerificationEmail(Request $request) {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email đã được xác thực.']);
        }

        $request->user()->sendEmailVerificationNotification();

        return response()->json(['message' => 'Đã gửi lại link xác thực qua Email.']);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Đã đăng xuất.']);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureManager
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isManager()) {
            return response()->json(['message' => 'Bạn không có quyền truy cập.'], 403);
        }

        if (! $user->isActive()) {
            return response()->json(['message' => 'Tài khoản của bạn đã bị khóa.'], 403);
        }

        if (! $user->isCinemaActive()) {
            return response()->json(['message' => 'Rạp của bạn đang bị khóa hoặc không tồn tại.'], 403);
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cinema;
use Illuminate\Http\Request;

class CinemaController extends Controller
{
    public function index()
    {
        $cinemas = Cinema::where('status', 'active')
            ->orderBy('city')
            ->orderBy('cinema_name')
            ->get();

        return response()->json($cinemas);
    }

    public function show(int $cinema_id)
    {
        $cinema = Cinema::with(['rooms', 'users.role'])
            ->findOrFail($cinema_id);

        return response()->json($cinema);
    }
}

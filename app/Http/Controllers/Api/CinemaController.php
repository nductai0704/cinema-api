<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cinema;
use Illuminate\Http\Request;

class CinemaController extends Controller
{
    public function index(Request $request)
    {
        $query = Cinema::where('status', 'active');

        if ($request->has('region_id')) {
            $query->where('region_id', $request->region_id);
        }

        $cinemas = $query->orderBy('cinema_name')->get();

        return response()->json($cinemas);
    }

    public function show(int $cinema_id)
    {
        $cinema = Cinema::with(['rooms', 'users.role'])
            ->findOrFail($cinema_id);

        return response()->json($cinema);
    }
}

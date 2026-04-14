<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cinema;
use Illuminate\Http\Request;

class AdminCinemaController extends Controller
{
    public function index()
    {
        return response()->json(Cinema::orderBy('city')->orderBy('cinema_name')->paginate(20));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'cinema_name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'district' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:20',
            'status' => 'nullable|string|max:50',
        ]);

        $cinema = Cinema::create($data);

        return response()->json($cinema, 201);
    }

    public function show(int $cinema_id)
    {
        $cinema = Cinema::with('rooms')->findOrFail($cinema_id);

        return response()->json($cinema);
    }

    public function update(Request $request, int $cinema_id)
    {
        $cinema = Cinema::findOrFail($cinema_id);

        $data = $request->validate([
            'cinema_name' => 'sometimes|required|string|max:255',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'district' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:20',
            'status' => 'nullable|string|max:50',
        ]);

        $cinema->update($data);

        return response()->json($cinema);
    }

    public function destroy(int $cinema_id)
    {
        $cinema = Cinema::findOrFail($cinema_id);
        $cinema->delete();

        return response()->json(['message' => 'Cụm rạp đã được xóa.']);
    }
}

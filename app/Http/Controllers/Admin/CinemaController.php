<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cinema;
use App\Http\Requests\CinemaRequest;
use App\Http\Resources\CinemaResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CinemaController extends Controller
{
    /**
     * Display a listing of cinemas with their regions.
     */
    public function index()
    {
        $cinemas = Cinema::with('region')->get();
        return CinemaResource::collection($cinemas);
    }

    /**
     * Store a newly created cinema.
     */
    public function store(CinemaRequest $request)
    {
        $cinema = Cinema::create($request->validated());
        return new CinemaResource($cinema->load('region'));
    }

    /**
     * Display the specified cinema with its rooms and managers.
     */
    public function show(Cinema $cinema)
    {
        $cinema->load(['region', 'rooms', 'users']);
        return new CinemaResource($cinema);
    }

    /**
     * Update the specified cinema.
     */
    public function update(CinemaRequest $request, Cinema $cinema)
    {
        // Kiểm tra status nếu rạp đang active mà muốn chuyển sang inactive
        if ($request->has('status') && $request->status !== $cinema->status) {
            $response = Gate::inspect('updateStatus', [$cinema, $request->status]);
            
            if ($response->denied()) {
                return response()->json([
                    'message' => $response->message()
                ], 409);
            }
        }

        $cinema->update($request->validated());
        return new CinemaResource($cinema->load('region'));
    }

    /**
     * Change the status of the cinema specifically.
     */
    public function changeStatus(Request $request, Cinema $cinema)
    {
        $request->validate([
            'status' => 'required|string|in:active,inactive'
        ]);

        // Gọi Policy để kiểm tra điều kiện ẩn rạp
        $response = Gate::inspect('updateStatus', $cinema);

        if ($response->denied()) {
            return response()->json([
                'message' => $response->message()
            ], 409);
        }

        $cinema->update(['status' => $request->status]);

        return response()->json([
            'message' => 'Cập nhật trạng thái rạp thành công.',
            'data' => new CinemaResource($cinema->load('region'))
        ]);
    }
}

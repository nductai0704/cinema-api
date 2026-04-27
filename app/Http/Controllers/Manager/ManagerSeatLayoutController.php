<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\SeatLayout;
use Illuminate\Http\Request;

class ManagerSeatLayoutController extends Controller
{
    public function index(Request $request)
    {
        $query = SeatLayout::query();
        
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        return response()->json([
            'data' => $query->get()
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'row_count' => 'required|integer|min:1',
            'column_count' => 'required|integer|min:1',
            'layout_data' => 'required|array',
            'status' => 'nullable|string|in:active,inactive',
        ]);

        $layout = SeatLayout::create($data); // cinema_id auto attached
        return response()->json(['data' => $layout], 201);
    }

    public function show($id)
    {
        $layout = SeatLayout::findOrFail($id);
        return response()->json(['data' => $layout]);
    }

    public function update(Request $request, $id)
    {
        $layout = SeatLayout::findOrFail($id);

        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|nullable|string',
            'row_count' => 'sometimes|required|integer|min:1',
            'column_count' => 'sometimes|required|integer|min:1',
            'layout_data' => 'sometimes|required|array',
            'status' => 'nullable|string|in:active,inactive',
        ]);

        $layout->update($data);

        // Tự động đồng bộ lại tất cả các phòng đang sử dụng LAYOUT này
        $rooms = \App\Models\Room::where('seat_layout_id', $layout->layout_id)->get();
        foreach ($rooms as $room) {
            $room->syncSeatsFromLayout();
        }

        return response()->json(['data' => $layout]);
    }

    public function destroy($id)
    {
        $layout = SeatLayout::findOrFail($id);
        $layout->delete();
        return response()->json(['message' => 'Xóa mẫu sơ đồ ghế thành công']);
    }

    public function toggleStatus($id)
    {
        $layout = SeatLayout::findOrFail($id);
        $newStatus = $layout->status === 'active' ? 'inactive' : 'active';
        $layout->update(['status' => $newStatus]);
        
        return response()->json([
            'message' => $newStatus === 'active' ? 'Đã kích hoạt sơ đồ.' : 'Đã tạm ngưng sơ đồ.',
            'status' => $newStatus
        ]);
    }
}

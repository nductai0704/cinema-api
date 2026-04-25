<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\Request;

class ManagerRoomController extends Controller
{
    public function index(Request $request)
    {
        $manager = $request->user();

        $query = Room::with('cinema')
            ->where('cinema_id', $manager->cinema_id);

        return response()->json($query->paginate(20));
    }

    public function store(Request $request)
    {
        $manager = $request->user();

        $data = $request->validate([
            'room_name' => 'required|string|max:100',
            'capacity' => 'nullable|integer',
            'room_type' => 'nullable|string|max:50',
            'status' => 'nullable|string|max:50',
        ]);

        $data['cinema_id'] = $manager->cinema_id;

        $room = Room::create($data);

        return response()->json($room->load('cinema'), 201);
    }

    public function show(Request $request, int $room_id)
    {
        $manager = $request->user();

        $room = Room::with('cinema', 'seats')
            ->where('cinema_id', $manager->cinema_id)
            ->findOrFail($room_id);

        return response()->json($room);
    }

    public function update(Request $request, int $room_id)
    {
        $manager = $request->user();

        $room = Room::where('cinema_id', $manager->cinema_id)->findOrFail($room_id);

        $data = $request->validate([
            'room_name' => 'sometimes|required|string|max:100',
            'capacity' => 'nullable|integer',
            'room_type' => 'nullable|string|max:50',
            'status' => 'nullable|string|max:50',
        ]);

        if (isset($data['status']) && strcasecmp($data['status'], 'active') !== 0 && $room->hasFutureShowtimes()) {
            return response()->json(['message' => 'Không thể khóa phòng khi còn suất chiếu đang hoạt động hoặc sắp diễn ra.'], 422);
        }

        $room->update($data);

        return response()->json($room);
    }

    public function destroy(Request $request, int $room_id)
    {
        $manager = $request->user();

        $room = Room::where('cinema_id', $manager->cinema_id)->findOrFail($room_id);
        $room->delete();

        return response()->json(['message' => 'Phòng chiếu đã được xóa.']);
    }

    public function toggleStatus(Request $request, int $room_id)
    {
        $manager = $request->user();
        $room = Room::where('cinema_id', $manager->cinema_id)->findOrFail($room_id);

        $newStatus = strtolower($room->status) === 'active' ? 'inactive' : 'active';

        if ($newStatus === 'inactive' && $room->hasFutureShowtimes()) {
            return response()->json(['message' => 'Không thể khóa phòng khi còn suất chiếu đang hoạt động hoặc sắp diễn ra.'], 422);
        }

        $room->update(['status' => $newStatus]);

        return response()->json([
            'message' => $newStatus === 'active' ? 'Phòng đã chuyển sang trạng thái Hoạt động.' : 'Phòng đã trở thành Bản nháp.',
            'status' => $newStatus
        ]);
    }
}

<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RoomType;

class ManagerRoomTypeController extends Controller
{
    public function index()
    {
        // Trait autoscopes to manager's cinema
        $types = RoomType::all();
        return response()->json($types);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
        ]);

        $data['status'] = 'active';

        $type = RoomType::create($data); // cinema_id auto attached
        return response()->json($type, 201);
    }

    public function show($id)
    {
        $type = RoomType::findOrFail($id);
        return response()->json($type);
    }

    public function update(Request $request, $id)
    {
        $type = RoomType::findOrFail($id);

        $data = $request->validate([
            'name' => 'sometimes|required|string|max:100',
            'description' => 'nullable|string',
        ]);

        $type->update($data);
        return response()->json($type);
    }

    public function destroy($id)
    {
        $type = RoomType::findOrFail($id);

        if ($type->rooms()->exists()) {
            return response()->json(['message' => 'Không thể xóa loại phòng đã được gán cho phòng chiếu.'], 400);
        }

        $type->delete();
        return response()->json(['message' => 'Đã xóa loại phòng.']);
    }

    public function toggleStatus($id)
    {
        $type = RoomType::findOrFail($id);
        $newStatus = $type->status === 'active' ? 'inactive' : 'active';
        $type->update(['status' => $newStatus]);
        
        // Cần xem xét nếu inactive loại phòng thì nó có làm phòng nào đó ngưng hoạt động không,
        // Nhưng tạm thời chỉ toggle status độc lập.
        return response()->json([
            'message' => $newStatus === 'active' ? 'Đã kích hoạt loại phòng.' : 'Đã tạm ngưng loại phòng.',
            'status' => $newStatus
        ]);
    }
}

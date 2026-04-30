<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Combo;
use App\Models\CinemaCombo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ManagerComboController extends Controller
{
    /**
     * Lấy danh sách toàn bộ combo (kết hợp giá và trạng thái tuỳ chỉnh của rạp cục bộ)
     */
    public function index()
    {
        $cinemaId = Auth::user()->cinema_id;
        $now = now()->toDateString();
        
        // Manager chỉ quản lý những combo mà Admin đang cho phép và còn hạn
        $globalCombos = Combo::where('status', 'active')
            ->where(function ($query) use ($now) {
                $query->whereNull('start_date')->orWhere('start_date', '<=', $now);
            })
            ->where(function ($query) use ($now) {
                $query->whereNull('end_date')->orWhere('end_date', '>=', $now);
            })
            ->get();
        
        $localSettings = CinemaCombo::where('cinema_id', $cinemaId)->get()->keyBy('combo_id');

        $result = $globalCombos->map(function ($combo) use ($localSettings) {
            $local = $localSettings->get($combo->combo_id);
            return [
                'combo_id' => $combo->combo_id,
                'combo_name' => $combo->combo_name,
                'description' => $combo->description,
                'target_audience' => $combo->target_audience,
                'start_date' => $combo->start_date ? $combo->start_date->toDateString() : null,
                'end_date' => $combo->end_date ? $combo->end_date->toDateString() : null,
                'image_url' => $combo->image_url,
                'original_price' => $combo->price,
                // Giá hiện tại tại rạp này
                'current_price' => $local ? $local->price : $combo->price,
                // Trạng thái bật/tắt tại rạp này
                'status' => $local ? $local->status : 'active',
            ];
        });

        return response()->json($result);
    }

    public function updateSetting(Request $request, $comboId)
    {
        $data = $request->validate([
            'price' => 'required|numeric|min:0',
            'status' => 'required|string|in:active,inactive',
        ]);

        $cinemaId = Auth::user()->cinema_id;

        // Sử dụng updateOrInsert để bỏ qua các ràng buộc Global Scope nếu có, đảm bảo lưu được 100%
        \Illuminate\Support\Facades\DB::table('cinema_combos')->updateOrInsert(
            ['combo_id' => $comboId, 'cinema_id' => $cinemaId],
            [
                'price' => $data['price'], 
                'status' => $data['status'],
                'updated_at' => now()
            ]
        );

        return response()->json([
            'message' => 'Cập nhật giá và trạng thái cho rạp thành công!',
            'cinema_id' => $cinemaId,
            'combo_id' => $comboId
        ]);
    }
}

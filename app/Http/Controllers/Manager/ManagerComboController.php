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
        
        $globalCombos = Combo::all();
        
        // Nhờ BelongsToCinema Trait, tự động chỉ lấy CinemaCombo của rạp hiện tại
        $localSettings = CinemaCombo::all()->keyBy('combo_id');

        $result = $globalCombos->map(function ($combo) use ($localSettings) {
            $local = $localSettings->get($combo->combo_id);
            return [
                'combo_id' => $combo->combo_id,
                'combo_name' => $combo->combo_name,
                'description' => $combo->description,
                'target_audience' => $combo->target_audience,
                'image_url' => $combo->image_url,
                'original_price' => $combo->price,
                // Ưu tiên sử dụng setting của rạp, nếu không thì dùng mặc định
                'current_price' => $local ? $local->price : $combo->price,
                'status' => $local ? $local->status : 'active', // Combo mặc định là active
            ];
        });

        return response()->json($result);
    }

    /**
     * Tuỳ chỉnh giá hoặc trạng thái ẩn/hiện của Combo tại rạp của mình
     */
    public function updateSetting(Request $request, $comboId)
    {
        $data = $request->validate([
            'price' => 'required|numeric|min:0',
            'status' => 'required|string|in:active,inactive',
        ]);

        $cinemaId = Auth::user()->cinema_id;

        // Cập nhật hoặc tạo mới bản ghi custom cho rạp (upsert)
        // cinema_id sẽ được Trait tự gán nếu là create
        $cinemaCombo = CinemaCombo::updateOrCreate(
            ['combo_id' => $comboId, 'cinema_id' => $cinemaId], // Điều kiện tìm kiếm
            ['price' => $data['price'], 'status' => $data['status']] // Dữ liệu cập nhật
        );

        return response()->json([
            'message' => 'Cập nhật thiết lập Combo thành công!',
            'data' => $cinemaCombo
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Combo;

class ComboController extends Controller
{
    public function index(\Illuminate\Http\Request $request)
    {
        $cinemaId = $request->query('cinema_id');
        
        $now = now()->toDateString();
        
        $combos = Combo::where('status', 'active')
            ->where(function ($query) use ($now) {
                $query->whereNull('start_date')
                      ->orWhere('start_date', '<=', $now);
            })
            ->where(function ($query) use ($now) {
                $query->whereNull('end_date')
                      ->orWhere('end_date', '>=', $now);
            })
            ->orderBy('combo_name')
            ->get();

        // Nếu có truyền cinema_id, chúng ta sẽ map giá tiền và trạng thái theo rạp đó
        if ($cinemaId) {
            $localSettings = \App\Models\CinemaCombo::where('cinema_id', $cinemaId)
                ->get()
                ->keyBy('combo_id');

            $combos = $combos->map(function ($combo) use ($localSettings) {
                $local = $localSettings->get($combo->combo_id);
                
                // 1. Kiểm tra trạng thái tại rạp: Nếu Manager tắt thì ẩn luôn
                if ($local && $local->status === 'inactive') {
                    return null;
                }

                // 2. Bổ sung trường current_price để FE User dễ đọc (giống FE Manager)
                $combo->current_price = $local ? $local->price : $combo->price;
                
                // 3. Đồng bộ lại trường price chính (để dự phòng)
                $combo->price = $combo->current_price;

                return $combo;
            })->filter(); // Loại bỏ hoàn toàn các combo bị ẩn
        } else {
            // Nếu không có cinema_id, mặc định current_price là price gốc
            $combos->each(function($c) {
                $c->current_price = $c->price;
            });
        }

        return response()->json($combos->values());
    }
}

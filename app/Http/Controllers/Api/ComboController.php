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
                
                // 1. Nếu Manager rạp này đã tắt combo này (inactive), 
                // chúng ta ép effective_status thành 'inactive' để FE lọc bỏ
                if ($local && $local->status === 'inactive') {
                    $combo->status = 'inactive';
                    $combo->effective_status = 'inactive';
                    return null; // Ẩn luôn khỏi danh sách trả về
                }

                // 2. Ép giá bán thực tế của rạp vào cả 2 trường để FE chắc chắn đọc được
                $actualPrice = $local ? $local->price : $combo->price;
                
                // Sử dụng setAttribute và append để đảm bảo Laravel đưa vào JSON
                $combo->setAttribute('current_price', $actualPrice);
                $combo->setAttribute('price', $actualPrice);

                return $combo;
            })->filter(); // Loại bỏ hoàn toàn các combo bị ẩn
        } else {
            // Dự phòng: Nếu không có cinema_id, giá hiện tại bằng giá gốc
            $combos->each(function($c) {
                $c->setAttribute('current_price', $c->price);
            });
        }

        return response()->json($combos->values());
    }
}

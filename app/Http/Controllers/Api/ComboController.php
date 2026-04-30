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

        // Nếu có truyền cinema_id, chúng ta sẽ map giá tiền theo rạp đó
        if ($cinemaId) {
            $localSettings = \App\Models\CinemaCombo::where('cinema_id', $cinemaId)
                ->get()
                ->keyBy('combo_id');

            $combos = $combos->map(function ($combo) use ($localSettings) {
                $local = $localSettings->get($combo->combo_id);
                
                // Nếu rạp đã tắt combo này (inactive), chúng ta đánh dấu để FE ẩn đi
                if ($local && $local->status === 'inactive') {
                    return null;
                }

                // Gán giá thực tế cho rạp đó
                $combo->price = $local ? $local->price : $combo->price;
                return $combo;
            })->filter(); // Loại bỏ những combo bị ẩn
        }

        return response()->json($combos->values());
    }
}

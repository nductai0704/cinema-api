<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Combo;

class ComboController extends Controller
{
    public function index()
    {
        $combos = Combo::where('status', 'active')
            ->orderBy('combo_name')
            ->get();

        return response()->json($combos);
    }
}

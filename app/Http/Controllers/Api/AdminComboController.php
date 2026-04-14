<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Combo;
use Illuminate\Http\Request;

class AdminComboController extends Controller
{
    public function index()
    {
        return response()->json(Combo::orderBy('combo_name')->paginate(20));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'combo_name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'image_url' => 'nullable|url|max:255',
            'status' => 'nullable|string|max:50',
        ]);

        $combo = Combo::create($data);

        return response()->json($combo, 201);
    }

    public function show(int $combo_id)
    {
        $combo = Combo::findOrFail($combo_id);

        return response()->json($combo);
    }

    public function update(Request $request, int $combo_id)
    {
        $combo = Combo::findOrFail($combo_id);

        $data = $request->validate([
            'combo_name' => 'sometimes|required|string|max:255',
            'price' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'image_url' => 'nullable|url|max:255',
            'status' => 'nullable|string|max:50',
        ]);

        $combo->update($data);

        return response()->json($combo);
    }

    public function destroy(int $combo_id)
    {
        $combo = Combo::findOrFail($combo_id);
        $combo->delete();

        return response()->json(['message' => 'Combo đã được xóa.']);
    }
}

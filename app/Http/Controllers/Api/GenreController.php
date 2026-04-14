<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Genre;
use Illuminate\Http\Request;

class GenreController extends Controller
{
    public function index(Request $request)
    {
        $query = Genre::query();

        if ($request->filled('search')) {
            $query->where('genre_name', 'like', '%'.$request->input('search').'%');
        }

        return response()->json($query->orderBy('genre_name')->paginate(20));
    }

    public function show(int $genre_id)
    {
        $genre = Genre::findOrFail($genre_id);

        return response()->json($genre);
    }
}

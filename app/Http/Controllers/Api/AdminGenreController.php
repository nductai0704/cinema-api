<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Genre;
use Illuminate\Http\Request;

class AdminGenreController extends Controller
{
    public function index()
    {
        return response()->json(Genre::orderBy('genre_name')->paginate(20));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'genre_name' => 'required|string|max:100|unique:genres,genre_name',
            'status' => 'nullable|string|max:50',
        ]);

        $genre = Genre::create($data);

        return response()->json($genre, 201);
    }

    public function show(int $genre_id)
    {
        return response()->json(Genre::findOrFail($genre_id));
    }

    public function update(Request $request, int $genre_id)
    {
        $genre = Genre::findOrFail($genre_id);

        $data = $request->validate([
            'genre_name' => 'sometimes|required|string|max:100|unique:genres,genre_name,'.$genre->genre_id.',genre_id',
            'status' => 'nullable|string|max:50',
        ]);

        $genre->update($data);

        return response()->json($genre);
    }

    public function destroy(int $genre_id)
    {
        $genre = Genre::findOrFail($genre_id);
        $genre->delete();

        return response()->json(['message' => 'Thể loại đã được xóa.']);
    }
}

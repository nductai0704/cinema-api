<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Genre;
use App\Models\Movie;
use Illuminate\Http\Request;

class AdminMovieController extends Controller
{
    public function index(Request $request)
    {
        $query = Movie::with('genres');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        return response()->json($query->orderBy('release_date', 'desc')->paginate(20));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'duration' => 'nullable|integer',
            'description' => 'nullable|string',
            'language' => 'nullable|string|max:100',
            'release_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:release_date',
            'age_limit' => 'nullable|integer',
            'poster_url' => 'nullable|url|max:255',
            'trailer_url' => 'nullable|url|max:255',
            'rating' => 'nullable|numeric|min:0|max:10',
            'backdrop_url' => 'nullable|url|max:255',
            'actors' => 'nullable|string',
            'director' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:100',
            'producer' => 'nullable|string|max:255',
            'status' => 'nullable|string|max:50',
            'genre_ids' => 'sometimes|array',
            'genre_ids.*' => 'integer|exists:genres,genre_id',
        ]);

        $movie = Movie::create($data);

        if (! empty($data['genre_ids'])) {
            $movie->genres()->sync($data['genre_ids']);
        }

        return response()->json($movie->load('genres'), 201);
    }

    public function show(int $movie_id)
    {
        $movie = Movie::with('genres')->findOrFail($movie_id);

        return response()->json($movie);
    }

    public function update(Request $request, int $movie_id)
    {
        $movie = Movie::findOrFail($movie_id);

        $data = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'duration' => 'nullable|integer',
            'description' => 'nullable|string',
            'language' => 'nullable|string|max:100',
            'release_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:release_date',
            'age_limit' => 'nullable|integer',
            'poster_url' => 'nullable|url|max:255',
            'trailer_url' => 'nullable|url|max:255',
            'rating' => 'nullable|numeric|min:0|max:10',
            'backdrop_url' => 'nullable|url|max:255',
            'actors' => 'nullable|string',
            'director' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:100',
            'producer' => 'nullable|string|max:255',
            'status' => 'nullable|string|max:50',
            'genre_ids' => 'sometimes|array',
            'genre_ids.*' => 'integer|exists:genres,genre_id',
        ]);

        $movie->update($data);

        if (array_key_exists('genre_ids', $data)) {
            $movie->genres()->sync($data['genre_ids'] ?? []);
        }

        return response()->json($movie->load('genres'));
    }

    public function destroy(int $movie_id)
    {
        $movie = Movie::findOrFail($movie_id);
        $movie->delete();

        return response()->json(['message' => 'Phim đã được xóa.']);
    }
}

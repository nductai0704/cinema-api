<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Movie;
use App\Http\Requests\MovieRequest;
use App\Http\Resources\MovieResource;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Storage;

class MovieController extends Controller
{
    /**
     * Display a listing of movies.
     */
    public function index()
    {
        $movies = Movie::with('genres')->paginate(15);
        return MovieResource::collection($movies);
    }

    /**
     * Store a newly created movie.
     */
    public function store(MovieRequest $request)
    {
        $data = $request->validated();

        // Ưu tiên 1: Nếu có file upload thì lưu file
        if ($request->hasFile('poster')) {
            $data['poster_url'] = $request->file('poster')->store('movies/posters', 'public');
        } 
        // Ưu tiên 2: Nếu không có file nhưng có gửi link poster_url trực tiếp
        elseif ($request->has('poster_url')) {
            $data['poster_url'] = $request->input('poster_url');
        }

        // Tương tự cho Backdrop
        if ($request->hasFile('backdrop')) {
            $data['backdrop_url'] = $request->file('backdrop')->store('movies/backdrops', 'public');
        } elseif ($request->has('backdrop_url')) {
            $data['backdrop_url'] = $request->input('backdrop_url');
        }

        $movie = Movie::create($data);
        
        if ($request->has('genre_ids')) {
            $movie->genres()->sync($request->genre_ids);
        }

        return new MovieResource($movie->load('genres'));
    }

    /**
     * Display the movie details.
     */
    public function show(Movie $movie)
    {
        return new MovieResource($movie->load('genres'));
    }

    /**
     * Update the movie information.
     */
    public function update(MovieRequest $request, Movie $movie)
    {
        $data = $request->validated();

        // Xử lý upload Poster mới
        if ($request->hasFile('poster')) {
            if ($movie->poster_url) {
                Storage::disk('public')->delete($movie->poster_url);
            }
            $data['poster_url'] = $request->file('poster')->store('movies/posters', 'public');
        }

        // Xử lý upload Backdrop mới
        if ($request->hasFile('backdrop')) {
            if ($movie->backdrop_url) {
                Storage::disk('public')->delete($movie->backdrop_url);
            }
            $data['backdrop_url'] = $request->file('backdrop')->store('movies/backdrops', 'public');
        }

        $movie->update($data);

        if ($request->has('genre_ids')) {
            $movie->genres()->sync($request->genre_ids);
        }

        return new MovieResource($movie->load('genres'));
    }

    /**
     * Change film status (active/inactive)
     */
    public function changeStatus(Request $request, Movie $movie)
    {
        $request->validate([
            'status' => 'required|string|in:active,inactive'
        ]);

        $movie->update(['status' => $request->status]);

        return response()->json([
            'message' => 'Cập nhật trạng thái phim thành công.',
            'data' => new MovieResource($movie)
        ]);
    }
}

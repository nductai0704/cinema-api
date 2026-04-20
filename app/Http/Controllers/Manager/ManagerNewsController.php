<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\News;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ManagerNewsController extends Controller
{
    public function index()
    {
        // Trait autoscoped to current Manager's cinema. Lists all news by users in this cinema.
        $news = News::with('author')->latest()->paginate(10);
        return response()->json($news);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'status' => 'nullable|string|in:active,inactive',
        ]);

        if ($request->hasFile('image')) {
            $data['image_url'] = $request->file('image')->store('news', 'public');
        }

        $data['created_by'] = Auth::id();

        $news = News::create($data);

        return response()->json([
            'message' => 'Đăng tin tức thành công!',
            'data' => $news
        ]);
    }

    public function show($id)
    {
        $news = News::findOrFail($id);
        return response()->json($news);
    }

    public function update(Request $request, $id)
    {
        $news = News::findOrFail($id);

        $data = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'content' => 'sometimes|required|string',
            'status' => 'nullable|string|in:active,inactive',
        ]);

        if ($request->hasFile('image')) {
            if ($news->image_url) {
                Storage::disk('public')->delete($news->image_url);
            }
            $data['image_url'] = $request->file('image')->store('news', 'public');
        }

        $news->update($data);

        return response()->json([
            'message' => 'Cập nhật tin tức thành công!',
            'data' => $news
        ]);
    }
}

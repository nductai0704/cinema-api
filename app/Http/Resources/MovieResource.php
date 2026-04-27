<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MovieResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'movie_id' => $this->movie_id,
            'title' => $this->title,
            'duration' => $this->duration,
            'description' => $this->description,
            'language' => $this->language,
            'release_date' => $this->release_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'age_limit' => $this->age_limit,
            'rating' => $this->rating,
            'poster_url' => $this->formatUrl($this->poster_url),
            'backdrop_url' => $this->formatUrl($this->backdrop_url),
            'trailer_url' => $this->trailer_url,
            'director' => $this->director,
            'actors' => $this->actors,
            'country' => $this->country,
            'producer' => $this->producer,
            'status' => $this->display_status,
            'genres' => $this->whenLoaded('genres', function() {
                return $this->genres->pluck('genre_name');
            }),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Định dạng URL: Nếu là link tuyệt đối (http...) thì giữ nguyên, ngược lại thì dùng Storage::url()
     */
    protected function formatUrl($path)
    {
        if (!$path) return null;
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }
        return asset(\Illuminate\Support\Facades\Storage::url($path));
    }
}

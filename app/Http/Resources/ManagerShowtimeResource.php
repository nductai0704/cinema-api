<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ManagerShowtimeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'showtime_id' => $this->showtime_id,
            'movie_id' => $this->movie_id,
            'movie_title' => $this->movie?->title,
            'movie_poster' => $this->formatUrl($this->movie?->poster_url),
            'movie_genres' => $this->movie?->genres ? $this->movie->genres->pluck('genre_name') : [],
            'room_id' => $this->room_id,
            'room_name' => $this->room?->room_name,
            'room_type_name' => $this->roomType?->name ?? $this->room?->roomType?->name, // Ưu tiên loại phòng của SUẤT CHIẾU, fallback về PHÒNG
            'show_date' => $this->show_date?->format('Y-m-d'),
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'ticket_price' => $this->ticket_price,
            'status' => $this->status,
            'display_status' => $this->display_status,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }

    protected function formatUrl($path)
    {
        if (!$path) return null;
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }
        return asset(\Illuminate\Support\Facades\Storage::url($path));
    }
}

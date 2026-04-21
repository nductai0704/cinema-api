<?php

namespace App\Http\Resources\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MovieDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'movie_id' => $this->movie_id,
            'title' => $this->title,
            'duration' => $this->duration,
            'description' => $this->description,
            'language' => $this->language,
            'release_date' => $this->release_date ? $this->release_date->format('Y-m-d') : null,
            'end_date' => $this->end_date ? $this->end_date->format('Y-m-d') : null,
            'age_limit' => $this->age_limit,
            'poster_url' => $this->poster_url,
            'trailer_url' => $this->trailer_url,
            'rating' => $this->rating,
            'backdrop_url' => $this->backdrop_url,
            'actors' => $this->actors,
            'director' => $this->director,
            'country' => $this->country,
            'status' => $this->display_status,
            'genres' => $this->genres->map(fn($genre) => [
                'genre_id' => $genre->genre_id,
                'genre_name' => $genre->genre_name
            ]),
        ];
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Movie extends Model
{
    use HasFactory;

    protected $table = 'movies';
    protected $primaryKey = 'movie_id';
    public $timestamps = true;

    protected $fillable = [
        'title',
        'duration',
        'description',
        'language',
        'release_date',
        'end_date',
        'age_limit',
        'poster_url',
        'trailer_url',
        'rating',
        'backdrop_url',
        'actors',
        'director',
        'country',
        'producer',
        'status',
    ];

    protected $casts = [
        'release_date' => 'date',
        'end_date' => 'date',
        'rating' => 'decimal:1',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('active', function ($query) {
            $query->where('status', '!=', 'inactive');
        });
    }

    /**
     * Get the movie's status based on release and end dates.
     */
    public function getDisplayStatusAttribute(): string
    {
        $now = now()->startOfDay();
        $releaseDate = $this->release_date ? $this->release_date->startOfDay() : null;
        $endDate = $this->end_date ? $this->end_date->startOfDay() : null;

        if (!$releaseDate || !$endDate) {
            return 'Không xác định';
        }

        if ($now->lt($releaseDate)) {
            return 'Sắp chiếu';
        }

        if ($now->gt($endDate)) {
            return 'Ngưng chiếu';
        }

        return 'Đang chiếu';
    }

    public function genres()
    {
        return $this->belongsToMany(Genre::class, 'movie_genres', 'movie_id', 'genre_id');
    }

    public function showtimes()
    {
        return $this->hasMany(Showtime::class, 'movie_id', 'movie_id');
    }
}

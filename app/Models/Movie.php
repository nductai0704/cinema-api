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

    public function genres()
    {
        return $this->belongsToMany(Genre::class, 'movie_genres', 'movie_id', 'genre_id');
    }

    public function showtimes()
    {
        return $this->hasMany(Showtime::class, 'movie_id', 'movie_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToCinema;
use Illuminate\Database\Eloquent\Builder;

class News extends Model
{
    use HasFactory, BelongsToCinema;

    public static function applyCinemaScope(Builder $builder, $cinemaId)
    {
        $builder->whereHas('author', function ($query) use ($cinemaId) {
            $query->where('cinema_id', $cinemaId);
        });
    }

    protected $table = 'news';
    protected $primaryKey = 'news_id';
    public $timestamps = true;

    protected $fillable = [
        'title',
        'content',
        'image_url',
        'created_by',
        'status',
    ];

    public function author()
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }
}

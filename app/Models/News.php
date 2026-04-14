<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class News extends Model
{
    use HasFactory;

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

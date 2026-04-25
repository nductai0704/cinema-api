<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Traits\BelongsToCinema;
use Illuminate\Database\Eloquent\Builder;

class RoomType extends Model
{
    use BelongsToCinema;

    protected $table = 'room_types';
    protected $primaryKey = 'room_type_id';

    protected $fillable = [
        'cinema_id',
        'name',
        'description',
        'status',
    ];

    public static function applyCinemaScope(Builder $builder, $cinemaId)
    {
        $builder->where('cinema_id', $cinemaId);
    }

    public function rooms()
    {
        return $this->hasMany(Room::class, 'room_type_id', 'room_type_id');
    }
}

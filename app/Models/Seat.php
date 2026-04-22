<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToCinema;
use Illuminate\Database\Eloquent\Builder;

class Seat extends Model
{
    use HasFactory, BelongsToCinema;

    public static function applyCinemaScope(Builder $builder, $cinemaId)
    {
        $builder->whereHas('room', function ($query) use ($cinemaId) {
            $query->where('cinema_id', $cinemaId);
        });
    }

    protected $table = 'seats';
    protected $primaryKey = 'seat_id';
    public $timestamps = true;

    protected $fillable = [
        'room_id',
        'row_label',
        'seat_number',
        'seat_type',
        'grid_x',
        'grid_y',
        'pair_uuid',
        'status',
    ];

    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id', 'room_id');
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'seat_id', 'seat_id');
    }

    public function holds()
    {
        return $this->hasMany(SeatHold::class, 'seat_id', 'seat_id');
    }
}

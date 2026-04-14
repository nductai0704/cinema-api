<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Seat extends Model
{
    use HasFactory;

    protected $table = 'seats';
    protected $primaryKey = 'seat_id';
    public $timestamps = true;

    protected $fillable = [
        'room_id',
        'row_label',
        'seat_number',
        'seat_type',
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

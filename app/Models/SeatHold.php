<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SeatHold extends Model
{
    use HasFactory;

    protected $table = 'seat_holds';
    protected $primaryKey = 'hold_id';
    public $timestamps = false;

    protected $fillable = [
        'showtime_id',
        'seat_id',
        'user_id',
        'hold_time',
        'expired_time',
        'status',
    ];

    protected $casts = [
        'hold_time' => 'datetime',
        'expired_time' => 'datetime',
    ];

    public function showtime()
    {
        return $this->belongsTo(Showtime::class, 'showtime_id', 'showtime_id');
    }

    public function seat()
    {
        return $this->belongsTo(Seat::class, 'seat_id', 'seat_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;

    protected $table = 'rooms';
    protected $primaryKey = 'room_id';
    public $timestamps = true;

    protected $fillable = [
        'room_name',
        'cinema_id',
        'capacity',
        'room_type',
        'status',
    ];

    public function cinema()
    {
        return $this->belongsTo(Cinema::class, 'cinema_id', 'cinema_id');
    }

    public function seats()
    {
        return $this->hasMany(Seat::class, 'room_id', 'room_id');
    }

    public function showtimes()
    {
        return $this->hasMany(Showtime::class, 'room_id', 'room_id');
    }

    public function hasFutureShowtimes(): bool
    {
        return $this->showtimes()
            ->where('status', 'active')
            ->where(function ($query) {
                $query->where('show_date', '>', now()->toDateString())
                    ->orWhere(function ($query) {
                        $query->where('show_date', now()->toDateString())
                            ->where('end_time', '>', now()->format('H:i:s'));
                    });
            })
            ->exists();
    }
}

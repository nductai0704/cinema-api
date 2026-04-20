<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use App\Traits\BelongsToCinema;

class Showtime extends Model
{
    use HasFactory, BelongsToCinema;

    protected $table = 'showtimes';
    protected $primaryKey = 'showtime_id';
    public $timestamps = true;

    protected $fillable = [
        'movie_id',
        'room_id',
        'cinema_id',
        'show_date',
        'start_time',
        'end_time',
        'ticket_price',
        'status',
    ];

    protected $casts = [
        'show_date' => 'date',
        'ticket_price' => 'decimal:2',
    ];

    public function movie()
    {
        return $this->belongsTo(Movie::class, 'movie_id', 'movie_id');
    }

    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id', 'room_id');
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'showtime_id', 'showtime_id');
    }

    public function holds()
    {
        return $this->hasMany(SeatHold::class, 'showtime_id', 'showtime_id');
    }

    public function getStartDateTimeAttribute()
    {
        $date = $this->show_date instanceof \Carbon\Carbon ? $this->show_date->toDateString() : $this->show_date;
        return Carbon::parse("{$date} {$this->start_time}");
    }

    public function getEndDateTimeAttribute()
    {
        $date = $this->show_date instanceof \Carbon\Carbon ? $this->show_date->toDateString() : $this->show_date;
        return Carbon::parse("{$date} {$this->end_time}");
    }

    public function getSessionLabelAttribute()
    {
        return sprintf('%s - %s', $this->start_time, $this->end_time);
    }

    public function getDisplayStatusAttribute()
    {
        if (strcasecmp($this->status, 'active') !== 0) {
            return strtolower($this->status ?: 'inactive');
        }

        $now = Carbon::now();
        $start = $this->start_date_time;
        $end = $this->end_date_time;

        if ($now->between($start, $end)) {
            return 'now_showing';
        }

        if ($now->lt($start)) {
            return 'upcoming';
        }

        return 'finished';
    }
}

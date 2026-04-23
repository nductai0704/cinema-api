<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToCinema;
use Illuminate\Database\Eloquent\Builder;

class SeatLayout extends Model
{
    use BelongsToCinema;

    public static function applyCinemaScope(Builder $builder, $cinemaId)
    {
        $builder->where('cinema_id', $cinemaId);
    }

    protected $table = 'seat_layouts';
    protected $primaryKey = 'layout_id';

    protected $fillable = [
        'cinema_id',
        'name',
        'description',
        'row_count',
        'column_count',
        'layout_data',
        'status',
    ];

    protected $casts = [
        'layout_data' => 'array',
    ];

    public function cinema()
    {
        return $this->belongsTo(Cinema::class, 'cinema_id', 'cinema_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookingCombo extends Model
{
    use HasFactory;

    protected $table = 'booking_combos';
    protected $primaryKey = 'booking_combo_id';
    public $timestamps = false;

    protected $fillable = [
        'booking_id',
        'combo_id',
        'quantity',
        'price',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id', 'booking_id');
    }

    public function combo()
    {
        return $this->belongsTo(Combo::class, 'combo_id', 'combo_id');
    }
}

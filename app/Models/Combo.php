<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Combo extends Model
{
    use HasFactory;

    protected $table = 'combos';
    protected $primaryKey = 'combo_id';
    public $timestamps = true;

    protected $fillable = [
        'combo_name',
        'price',
        'description',
        'target_audience',
        'start_date',
        'end_date',
        'image_url',
        'status',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function bookings()
    {
        return $this->hasMany(BookingCombo::class, 'combo_id', 'combo_id');
    }
}

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
        'image_url',
        'status',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    public function bookings()
    {
        return $this->hasMany(BookingCombo::class, 'combo_id', 'combo_id');
    }
}

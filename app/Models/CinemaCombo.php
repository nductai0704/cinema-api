<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\BelongsToCinema;

class CinemaCombo extends Model
{
    use HasFactory, BelongsToCinema;

    protected $table = 'cinema_combos';
    protected $primaryKey = 'cinema_combo_id';
    public $timestamps = true;

    protected $fillable = [
        'cinema_id',
        'combo_id',
        'price',
        'status',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    public function cinema()
    {
        return $this->belongsTo(Cinema::class, 'cinema_id', 'cinema_id');
    }

    public function combo()
    {
        return $this->belongsTo(Combo::class, 'combo_id', 'combo_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Region extends Model
{
    use HasFactory;

    protected $table = 'regions';
    protected $primaryKey = 'region_id';
    public $timestamps = true;

    protected $fillable = [
        'city',
        'district',
        'status',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function cinemas()
    {
        return $this->hasMany(Cinema::class, 'region_id', 'region_id');
    }
}

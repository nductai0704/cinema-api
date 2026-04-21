<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cinema extends Model
{
    use HasFactory;

    protected $table = 'cinemas';
    protected $primaryKey = 'cinema_id';
    public $timestamps = true;

    protected $fillable = [
        'cinema_name',
        'region_id',
        'address',
        'city',
        'district',
        'phone',
        'status',
    ];

    public function region()
    {
        return $this->belongsTo(Region::class, 'region_id', 'region_id');
    }

    public function rooms()
    {
        return $this->hasMany(Room::class, 'cinema_id', 'cinema_id');
    }

    public function users()
    {
        return $this->hasMany(User::class, 'cinema_id', 'cinema_id');
    }
}

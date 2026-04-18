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
    ];

    public function cinemas()
    {
        return $this->hasMany(Cinema::class, 'region_id', 'region_id');
    }
}

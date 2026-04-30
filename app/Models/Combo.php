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

    protected $appends = ['effective_status', 'current_price'];

    public function getCurrentPriceAttribute()
    {
        return $this->attributes['current_price'] ?? $this->price;
    }

    public function bookings()
    {
        return $this->hasMany(BookingCombo::class, 'combo_id', 'combo_id');
    }

    /**
     * Tự động tính toán trạng thái dựa trên ngày tháng
     */
    public function getEffectiveStatusAttribute()
    {
        $now = now()->startOfDay();
        
        if ($this->status === 'inactive') {
            return 'inactive';
        }

        if ($this->start_date && $now->lt($this->start_date->startOfDay())) {
            return 'upcoming';
        }

        if ($this->end_date && $now->gt($this->end_date->endOfDay())) {
            return 'expired';
        }

        return 'active';
    }

    /**
     * Ép định dạng ngày tháng khi trả về JSON để tránh lỗi lệch múi giờ ở Frontend
     */
    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format('Y-m-d');
    }
}

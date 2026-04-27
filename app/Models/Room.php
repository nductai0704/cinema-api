<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToCinema;

class Room extends Model
{
    use HasFactory, BelongsToCinema;

    protected $table = 'rooms';
    protected $primaryKey = 'room_id';
    public $timestamps = true;

    protected $fillable = [
        'room_name',
        'cinema_id',
        'capacity',
        'room_type_id',
        'seat_layout_id',
        'status',
    ];

    protected $appends = [
        'valid_seat_count',
        'total_seat_count',
    ];

    public function seatLayout()
    {
        return $this->belongsTo(SeatLayout::class, 'seat_layout_id', 'layout_id');
    }

    public function roomType()
    {
        return $this->belongsTo(RoomType::class, 'room_type_id', 'room_type_id');
    }

    public function cinema()
    {
        return $this->belongsTo(Cinema::class, 'cinema_id', 'cinema_id');
    }

    public function seats()
    {
        return $this->hasMany(Seat::class, 'room_id', 'room_id');
    }

    public function showtimes()
    {
        return $this->hasMany(Showtime::class, 'room_id', 'room_id');
    }

    /**
     * Accessor: Số lượng ghế hiệu dụng (Ghế đôi = 1)
     */
    public function getValidSeatCountAttribute(): int
    {
        // Nếu chưa có ghế nào trong DB, trả về capacity mặc định
        if (!$this->seats()->exists()) {
            return (int) $this->capacity;
        }

        $activeCoupleBlocks = $this->seats()->whereIn('seat_type', ['couple', 'double'])->where('status', 'active')->count();
        $activeOtherSeats = $this->seats()->whereNotIn('seat_type', ['couple', 'double'])->where('status', 'active')->count();

        return $activeOtherSeats + (int)($activeCoupleBlocks / 2);
    }

    /**
     * Accessor: Tổng số ô có thể đặt ghế (Ma trận - Lối đi)
     */
    public function getTotalSeatCountAttribute(): int
    {
        if ($this->seatLayout && !empty($this->seatLayout->layout_data)) {
            $totalCells = 0;
            $aisleCount = 0;
            
            foreach ($this->seatLayout->layout_data as $row) {
                if (!isset($row['seats']) || !is_array($row['seats'])) continue;
                foreach ($row['seats'] as $seat) {
                    $totalCells++;
                    if (isset($seat['type']) && strtolower($seat['type']) === 'aisle') {
                        $aisleCount++;
                    }
                }
            }
            return $totalCells - $aisleCount;
        }

        return $this->seats()->count() ?: (int) $this->capacity;
    }

    public function hasFutureShowtimes(): bool
    {
        return $this->showtimes()
            ->where('status', 'active')
            ->where(function ($query) {
                $query->where('show_date', '>', now()->toDateString())
                    ->orWhere(function ($query) {
                        $query->where('show_date', now()->toDateString())
                            ->where('end_time', '>', now()->format('H:i:s'));
                    });
            })
            ->exists();
    }
}

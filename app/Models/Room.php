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

    public function getValidSeatCountAttribute(): int
    {
        $activeStatuses = ['active', 'available', 'active_seat'];

        if (!$this->seats()->exists()) {
            return (int) $this->capacity;
        }

        $activeCoupleBlocks = $this->seats()
            ->whereIn('seat_type', ['couple', 'double'])
            ->whereIn('status', $activeStatuses)
            ->count();
        
        $activeOtherSeats = $this->seats()
            ->whereNotIn('seat_type', ['couple', 'double'])
            ->whereIn('status', $activeStatuses)
            ->count();

        return $activeOtherSeats + (int)($activeCoupleBlocks / 2);
    }

    public function getTotalSeatCountAttribute(): int
    {
        $layout = $this->seatLayout;
        if (!$layout) {
            $layout = SeatLayout::find($this->seat_layout_id);
        }

        if ($layout && !empty($layout->layout_data)) {
            $totalCells = 0;
            $aisleCount = 0;
            
            foreach ($layout->layout_data as $row) {
                if (!isset($row['seats']) || !is_array($row['seats'])) continue;
                foreach ($row['seats'] as $seat) {
                    $totalCells++;
                    if (isset($seat['type']) && (strtolower($seat['type']) === 'aisle' || strtolower($seat['type']) === 'empty')) {
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

    public function refreshCapacity()
    {
        $activeCoupleBlocks = $this->seats()->whereIn('seat_type', ['couple', 'double'])->where('status', 'active')->count();
        $activeOtherSeats = $this->seats()->whereNotIn('seat_type', ['couple', 'double'])->where('status', 'active')->count();
        
        $newCapacity = $activeOtherSeats + (int)($activeCoupleBlocks / 2);

        $this->capacity = $newCapacity;
        $this->saveQuietly();
        
        return $newCapacity;
    }

    public function syncSeatsFromLayout()
    {
        $layout = $this->seatLayout;
        if (!$layout || empty($layout->layout_data)) return;

        if ($this->showtimes()->whereHas('tickets')->exists()) {
            return false;
        }

        return \Illuminate\Support\Facades\DB::transaction(function () use ($layout) {
            $this->seats()->delete();

            $seatsToInsert = [];
            $now = now();

            foreach ($layout->layout_data as $y => $row) {
                if (!isset($row['seats']) || !is_array($row['seats'])) continue;

                foreach ($row['seats'] as $x => $seat) {
                    $type = strtolower($seat['type'] ?? '');
                    if (empty($type) || $type === 'aisle' || $type === 'empty') continue;

                    if ($type === 'regular' || $type === 'standard') $type = 'normal';
                    if ($type === 'double') $type = 'couple';

                    $status = strtolower($seat['status'] ?? 'active');

                    $seatsToInsert[] = [
                        'room_id'    => $this->room_id,
                        'row_label'  => strtoupper($row['label'] ?? ''),
                        'seat_number'=> count(array_filter($seatsToInsert, fn($s) => $s['row_label'] === strtoupper($row['label'] ?? ''))) + 1,
                        'seat_type'  => $type,
                        'grid_x'     => $x,
                        'grid_y'     => $y,
                        'pair_uuid'  => $seat['pair_uuid'] ?? ($seat['pair'] ?? null),
                        'status'     => $status,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            if (count($seatsToInsert) > 0) {
                Seat::insert($seatsToInsert);
            }

            $this->refreshCapacity();
            return true;
        });
    }
}

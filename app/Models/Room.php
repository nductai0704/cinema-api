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
        // Các loại ghế KHÔNG được tính vào sức chứa bán vé
        $invalidTypes = ['aisle', 'empty', 'broken', 'trash'];

        // Nếu chưa có ghế nào trong DB, trả về capacity
        if (!$this->seats()->exists()) {
            return (int) $this->capacity;
        }

        // Đếm ghế đôi (couple): 2 ô tính là 1 vị trí
        $activeCoupleBlocks = $this->seats()
            ->whereIn('seat_type', ['couple', 'double'])
            ->whereIn('status', $activeStatuses)
            ->count();
        
        // Đếm ghế đơn (standard, vip, v.v.): 1 ô tính là 1 vị trí
        $activeOtherSeats = $this->seats()
            ->whereNotIn('seat_type', array_merge(['couple', 'double'], $invalidTypes))
            ->whereIn('status', $activeStatuses)
            ->count();

        return $activeOtherSeats + (int)($activeCoupleBlocks / 2);
    }

    /**
     * Accessor: Tổng số ô có thể đặt ghế (Ma trận R x C - Lối đi)
     */
    public function getTotalSeatCountAttribute(): int
    {
        $layout = $this->seatLayout ?: SeatLayout::withoutGlobalScopes()->find($this->seat_layout_id);
        
        if ($layout) {
            $rowCount = (int) $layout->row_count;
            $colCount = (int) $layout->column_count;
            $totalGrid = $rowCount * $colCount;
            
            $aisleCount = 0;
            $data = $layout->layout_data;
            
            if (is_array($data)) {
                foreach ($data as $row) {
                    // Trường hợp nested (mảng các row, mỗi row có seats)
                    if (isset($row['seats']) && is_array($row['seats'])) {
                        foreach ($row['seats'] as $seat) {
                            $type = strtolower($seat['type'] ?? '');
                            if ($type === 'aisle' || $type === 'empty') $aisleCount++;
                        }
                    } 
                    // Trường hợp flat (mảng các ô ghế trực tiếp)
                    else if (is_array($row) && isset($row['type'])) {
                        $type = strtolower($row['type']);
                        if ($type === 'aisle' || $type === 'empty') $aisleCount++;
                    }
                }
            }
            
            // Nếu có row_count/col_count thì dùng công thức R*C - Aisle
            if ($totalGrid > 0) {
                return $totalGrid - $aisleCount;
            }
        }

        // Fallback: Nếu không tính được theo sơ đồ, đếm tất cả records ghế trong DB
        return (int) ($this->seats()->count() ?: $this->capacity);
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

    /**
     * Đồng bộ cột capacity trong DB với số ghế thực tế đang hoạt động
     */
    public function refreshCapacity()
    {
        $this->capacity = $this->valid_seat_count;
        $this->saveQuietly();
        return $this->capacity;
    }

    /**
     * Đồng bộ bảng seats của phòng dựa trên mẫu sơ đồ (seat_layout_id)
     */
    public function syncSeatsFromLayout()
    {
        $layout = $this->seatLayout ?: SeatLayout::find($this->seat_layout_id);
        if (!$layout || empty($layout->layout_data)) return false;

        return \Illuminate\Support\Facades\DB::transaction(function () use ($layout) {
            $now = now();
            $data = $layout->layout_data;

            // Chuyển đổi layout_data thành danh sách phẳng nếu đang là nested
            $flatSeats = [];
            foreach ($data as $y => $item) {
                if (isset($item['seats']) && is_array($item['seats'])) {
                    foreach ($item['seats'] as $x => $s) {
                        $s['grid_x'] = $x;
                        $s['grid_y'] = $y;
                        $s['row_label'] = $item['label'] ?? '';
                        $flatSeats[] = $s;
                    }
                } else {
                    $flatSeats[] = $item;
                }
            }

            $seatsToInsert = [];
            foreach ($flatSeats as $s) {
                $type = strtolower($s['type'] ?? $s['seat_type'] ?? '');
                if (empty($type) || $type === 'aisle' || $type === 'empty') continue;

                if ($type === 'regular' || $type === 'standard') $type = 'normal';
                if ($type === 'double') $type = 'couple';

                // Nếu là ghế hư (broken/trash), cho dù status là gì cũng sẽ không được tính là active
                $status = strtolower($s['status'] ?? 'active');
                if (in_array($type, ['broken', 'trash'])) $status = 'broken';

                $rowLabel = strtoupper($s['row_label'] ?? $s['label'] ?? '');
                $seatNumber = $s['seat_number'] ?? (count(array_filter($seatsToInsert, fn($exist) => $exist['row_label'] === $rowLabel)) + 1);

                $seatsToInsert[] = [
                    'room_id'    => $this->room_id,
                    'row_label'  => $rowLabel,
                    'seat_number'=> $seatNumber,
                    'seat_type'  => $type,
                    'grid_x'     => $s['grid_x'] ?? ($s['col'] ?? 0),
                    'grid_y'     => $s['grid_y'] ?? ($s['row'] ?? 0),
                    'pair_uuid'  => $s['pair_uuid'] ?? ($s['pair'] ?? null),
                    'status'     => $status,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            // Lấy các ghế hiện tại để tránh xóa mất ghế đã có người đặt
            $existingSeats = $this->seats()->get();
            $existingMap = [];
            foreach ($existingSeats as $seat) {
                $existingMap[$seat->row_label . '_' . $seat->seat_number] = $seat;
            }

            $newSeatsBatch = [];

            foreach ($seatsToInsert as $newSeat) {
                $key = $newSeat['row_label'] . '_' . $newSeat['seat_number'];

                if (isset($existingMap[$key])) {
                    // Update ghế cũ
                    $seatModel = $existingMap[$key];
                    $seatModel->update([
                        'seat_type' => $newSeat['seat_type'],
                        'grid_x'    => $newSeat['grid_x'],
                        'grid_y'    => $newSeat['grid_y'],
                        'pair_uuid' => $newSeat['pair_uuid'],
                        'status'    => $newSeat['status'],
                        'updated_at'=> $now,
                    ]);
                    unset($existingMap[$key]);
                } else {
                    // Ghế mới
                    $newSeatsBatch[] = $newSeat;
                }
            }

            if (count($newSeatsBatch) > 0) {
                Seat::insert($newSeatsBatch);
            }

            // Xử lý các ghế bì xóa khỏi sơ đồ (nghĩa là đã biến thành lối đi hoặc không còn)
            foreach ($existingMap as $oldSeat) {
                if ($oldSeat->tickets()->exists()) {
                    // Nếu đã có người mua vé, đánh dấu nó thành broken thay vì xóa hẳn dể khỏi chết DB
                    $oldSeat->update(['status' => 'broken', 'seat_type' => 'broken']);
                } else {
                    $oldSeat->delete();
                }
            }

            $this->refreshCapacity();
            return true;
        });
    }
}

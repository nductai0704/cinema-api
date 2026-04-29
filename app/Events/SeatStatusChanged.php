<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SeatStatusChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $showtimeId;
    public $seatId;
    public $status;
    public $userId;

    /**
     * Create a new event instance.
     */
    public function __construct($showtimeId, $seatId, $status, $userId = null)
    {
        $this->showtimeId = $showtimeId;
        $this->seatId = $seatId;
        $this->status = $status;
        $this->userId = $userId;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('showtime.' . $this->showtimeId),
        ];
    }

    /**
     * Tên sự kiện để lắng nghe trên frontend.
     */
    public function broadcastAs(): string
    {
        return 'SeatStatusChanged';
    }

    /**
     * Dữ liệu trả về (mặc định lấy các public properties nhưng có thể custom).
     */
    public function broadcastWith(): array
    {
        return [
            'seat_id' => $this->seatId,
            'status' => $this->status,
            'user_id' => $this->userId,
        ];
    }
}

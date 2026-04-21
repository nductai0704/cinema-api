<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SeatHold;

class CleanupSeatHolds extends Command
{
    protected $signature = 'cinema:cleanup-holds';

    protected $description = 'Xoá bỏ các lượt giữ ghế đã hết hạn (sau 5 phút không thanh toán)';

    public function handle()
    {
        $deletedCount = SeatHold::where('expired_time', '<', now())->delete();

        if ($deletedCount > 0) {
            $this->info("Đã dọn dẹp thành công {$deletedCount} lượt giữ ghế hết hạn.");
        }

        return 0;
    }
}

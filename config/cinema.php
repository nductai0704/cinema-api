<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cinema Basic Configuration
    |--------------------------------------------------------------------------
    |
    | Các cài đặt thời gian và thuật toán xử lý chung cho hệ thống rạp chiếu.
    |
    */

    // Thời gian dọn dẹp phòng định dạng phút. Hệ thống sẽ tự động cộng thời gian
    // này vào lúc kết thúc phim để làm thời điểm bắt đầu cho suất chiếu tiếp theo.
    'cleaning_time_minutes' => env('CINEMA_CLEANING_TIME_MINUTES', 15),

    // Các loại ghế và phụ thu mặc định (Tham khảo)
    'seat_types' => [
        'normal' => [
            'name' => 'Ghế Thường',
            'surcharge' => 0, 
        ],
        'vip' => [
            'name' => 'Ghế VIP',
            'surcharge' => 10000,
        ],
        'couple' => [
            'name' => 'Ghế Đôi',
            'surcharge' => 20000, // Thường ghế đôi có giá x2 base price + surcharge
        ]
    ]
];

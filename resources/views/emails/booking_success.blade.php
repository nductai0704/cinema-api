<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Vé Điện Tử - {{ $booking->booking_id }}</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f4f4; margin: 0; padding: 20px; }
        .ticket-card { max-width: 500px; margin: 0 auto; background: #fff; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .header { background: #000; color: #fff; padding: 30px; text-align: center; }
        .header h1 { margin: 0; font-size: 24px; letter-spacing: 2px; }
        .content { padding: 30px; }
        .movie-title { font-size: 22px; font-weight: bold; color: #333; margin-bottom: 10px; }
        .info-grid { display: table; width: 100%; margin-top: 20px; border-top: 1px solid #eee; padding-top: 20px; }
        .info-item { display: table-row; }
        .label { display: table-cell; padding: 8px 0; color: #888; font-size: 14px; width: 40%; }
        .value { display: table-cell; padding: 8px 0; color: #333; font-weight: 600; font-size: 15px; }
        .qr-section { text-align: center; padding: 30px; background: #fafafa; border-top: 2px dashed #ddd; }
        .qr-code { background: #fff; padding: 15px; display: inline-block; border-radius: 15px; border: 1px solid #eee; }
        .footer { text-align: center; padding: 20px; font-size: 12px; color: #aaa; }
        .badge { background: #e3f2fd; color: #1976d2; padding: 4px 12px; border-radius: 10px; font-size: 12px; text-transform: uppercase; }
    </style>
</head>
<body>
    <div class="ticket-card">
        <div class="header">
            <span class="badge">VÉ ĐÃ THANH TOÁN</span>
            <h1>CINEMA APP</h1>
        </div>
        <div class="content">
            @php 
                $firstTicket = $booking->tickets->first();
                $movie = $firstTicket->showtime->movie;
                $showtime = $firstTicket->showtime;
                $cinema = $showtime->room->cinema;
            @endphp

            <div class="movie-title">{{ $movie->title }}</div>
            <div style="color: #666; font-size: 14px;">{{ $cinema->cinema_name }} - {{ $showtime->room->room_name }}</div>

            <div class="info-grid">
                <div class="info-item">
                    <div class="label">Suất chiếu:</div>
                    <div class="value">{{ $showtime->start_date_time->format('H:i - d/m/Y') }}</div>
                </div>
                <div class="info-item">
                    <div class="label">Danh sách ghế:</div>
                    <div class="value">
                        @foreach($booking->tickets as $ticket)
                            {{ $ticket->seat->row_label }}{{ $ticket->seat->seat_number }}{{ !$loop->last ? ', ' : '' }}
                        @endforeach
                    </div>
                </div>
                <div class="info-item">
                    <div class="label">Mã đơn hàng:</div>
                    <div class="value">#{{ $booking->booking_id }}</div>
                </div>
                <div class="info-item">
                    <div class="label">Tổng tiền:</div>
                    <div class="value">{{ number_format($booking->total_amount) }} VNĐ</div>
                </div>
            </div>
        </div>

        <div class="qr-section">
            <div style="margin-bottom: 15px; font-weight: bold; color: #555;">MÃ VÉ CỦA BẠN</div>
            <div class="qr-code">
                <img src="data:image/png;base64, {!! $qrCode !!}" alt="QR Code">
            </div>
            <p style="font-size: 12px; color: #999; mt-10">Vui lòng đưa mã này cho nhân viên để soát vé</p>
        </div>

        <div class="footer">
            Cảm ơn bạn đã tin tưởng dịch vụ của Cinema App.<br>
            Chúc bạn có những giây phút xem phim vui vẻ!
        </div>
    </div>
</body>
</html>

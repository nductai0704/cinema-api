<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class BookingSuccessMail extends Mailable
{
    use Queueable, SerializesModels;

    public $booking;
    public $qrCode;

    /**
     * Create a new message instance.
     */
    public function __construct(Booking $booking)
    {
        $this->booking = $booking->load(['tickets.showtime.movie', 'tickets.showtime.room.cinema', 'tickets.seat', 'combos.combo']);
        
        // Tạo mã QR chứa ID đơn hàng hoặc mã số vé
        // Chèn trực tiếp vào Base64 để hiển thị trong Email
        $this->qrCode = base64_encode(QrCode::format('png')
            ->size(200)
            ->margin(1)
            ->generate('BOOKING_' . $booking->booking_id));
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Vé xem phim của bạn - ' . $this->booking->booking_id,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.booking_success',
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}

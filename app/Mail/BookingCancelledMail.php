<?php

namespace App\Mail;

use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class BookingCancelledMail extends BookingMail
{
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Booking cancelled — '.$this->booking->reference,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.booking-cancelled');
    }
}

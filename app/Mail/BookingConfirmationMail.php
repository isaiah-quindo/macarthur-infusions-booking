<?php

namespace App\Mail;

use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class BookingConfirmationMail extends BookingMail
{
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Booking confirmed — '.$this->booking->reference,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.booking-confirmation');
    }
}

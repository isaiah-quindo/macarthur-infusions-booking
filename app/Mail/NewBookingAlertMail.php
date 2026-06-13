<?php

namespace App\Mail;

use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class NewBookingAlertMail extends BookingMail
{
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New booking — '.$this->booking->reference,
            replyTo: [$this->booking->customer_email],
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.new-booking-alert');
    }
}

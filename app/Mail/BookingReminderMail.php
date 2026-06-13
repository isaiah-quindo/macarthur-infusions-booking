<?php

namespace App\Mail;

use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class BookingReminderMail extends BookingMail
{
    public function envelope(): Envelope
    {
        return new Envelope(subject: 'See you tomorrow — '.$this->booking->service->name);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.booking-reminder');
    }
}

<?php

namespace App\Mail;

use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class PaymentNeedsReviewMail extends BookingMail
{
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'NEEDS REVIEW: paid booking lost its slot — '.$this->booking->reference,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.payment-needs-review');
    }
}

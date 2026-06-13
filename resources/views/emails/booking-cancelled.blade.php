@extends('emails.layout')

@section('preheader', "Your {$booking->service->name} appointment has been cancelled.")

@section('body')
    <h1 style="font-family:Georgia,'Times New Roman',serif; font-size:24px; line-height:1.2; color:#0c3848; margin:0 0 12px; font-weight:bold;">
        Your booking has been cancelled
    </h1>
    <p style="font-size:15px; line-height:1.7; color:#0c1f2c; margin:0;">
        Hi {{ explode(' ', $booking->customer_name)[0] }}, the appointment below has been cancelled.
        If a payment was taken, your refund is being arranged and will return to the same card.
    </p>

    @include('emails.partials.booking-details', ['paymentNote' => ''])

    @include('emails.partials.button', ['url' => route('booking.services'), 'label' => 'Book a new time'])

    <p style="font-size:13px; line-height:1.7; color:#5a6b75; margin:22px 0 0;">
        Prefer to talk it through? Call us on {{ config('booking.clinic.phone') }} &mdash; we'd love to look after you.
    </p>
@endsection

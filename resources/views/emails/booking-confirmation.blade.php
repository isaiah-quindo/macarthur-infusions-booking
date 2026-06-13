@extends('emails.layout')

@php($inPerson = $booking->payment_method === \App\Enums\PaymentMethod::InPerson)

@section('preheader', "You're booked for {$booking->service->name} on ".$booking->startsAtClinic()->format('l j F').'.')

@section('body')
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:14px;">
        <tr>
            <td style="background-color:#1f8a4a; border-radius:999px; padding:5px 14px; font-size:12px; font-weight:bold; color:#ffffff; text-transform:uppercase; letter-spacing:0.6px;">
                &#10003;&nbsp; Confirmed
            </td>
        </tr>
    </table>

    <h1 style="font-family:Georgia,'Times New Roman',serif; font-size:26px; line-height:1.2; color:#0c3848; margin:0 0 12px; font-weight:bold;">
        You're booked, {{ explode(' ', $booking->customer_name)[0] }}!
    </h1>

    @if ($inPerson)
        <p style="font-size:15px; line-height:1.7; color:#0c1f2c; margin:0;">
            Your appointment is confirmed. Please bring payment of <strong>{{ $booking->service->priceFormatted() }}</strong>
            to the clinic on the day &mdash; we accept card and cash.
        </p>
    @else
        <p style="font-size:15px; line-height:1.7; color:#0c1f2c; margin:0;">
            Thank you &mdash; your payment has been received and your appointment is confirmed. We look forward to seeing you.
        </p>
    @endif

    @include('emails.partials.booking-details', ['paymentNote' => $inPerson ? ' · due at appointment' : ' · paid'])

    @include('emails.partials.button', ['url' => route('booking.show', $booking), 'label' => 'View your booking'])

    <p style="font-size:13px; line-height:1.7; color:#5a6b75; margin:22px 0 0;">
        {{ config('booking.clinic.cancellation_policy') }}
    </p>
@endsection

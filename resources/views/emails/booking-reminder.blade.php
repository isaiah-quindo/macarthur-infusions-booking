@extends('emails.layout')

@php($inPerson = $booking->payment_method === \App\Enums\PaymentMethod::InPerson)

@section('preheader', 'Your '.$booking->service->name.' is tomorrow at '.$booking->startsAtClinic()->format('g:ia').'.')

@section('body')
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:14px;">
        <tr>
            <td style="background-color:#1f7a8c; border-radius:999px; padding:5px 14px; font-size:12px; font-weight:bold; color:#ffffff; text-transform:uppercase; letter-spacing:0.6px;">
                &#9200;&nbsp; Reminder
            </td>
        </tr>
    </table>

    <h1 style="font-family:Georgia,'Times New Roman',serif; font-size:26px; line-height:1.2; color:#0c3848; margin:0 0 12px; font-weight:bold;">
        See you tomorrow, {{ explode(' ', $booking->customer_name)[0] }}!
    </h1>

    <p style="font-size:15px; line-height:1.7; color:#0c1f2c; margin:0;">
        This is a quick reminder that your appointment is tomorrow,
        <strong>{{ $booking->startsAtClinic()->format('l j F') }}</strong> at
        <strong>{{ $booking->startsAtClinic()->format('g:ia') }}</strong>.
        Please arrive a few minutes early so we can settle you in.
    </p>

    @if ($inPerson)
        <p style="font-size:15px; line-height:1.7; color:#0c1f2c; margin:14px 0 0;">
            Please bring <strong>{{ $booking->service->priceFormatted() }}</strong> with you — we accept card and cash at the clinic.
        </p>
    @endif

    @include('emails.partials.booking-details', ['paymentNote' => $inPerson ? ' · due at appointment' : ' · paid'])

    @include('emails.partials.button', ['url' => route('booking.show', $booking), 'label' => 'View your booking'])

    <p style="font-size:13px; line-height:1.7; color:#5a6b75; margin:22px 0 0;">
        Need to reschedule or cancel? Call us on
        <a href="tel:{{ preg_replace('/\s+/', '', config('booking.clinic.phone')) }}" style="color:#1f7a8c;">{{ config('booking.clinic.phone') }}</a>
        as soon as you can. {{ config('booking.clinic.cancellation_policy') }}
    </p>
@endsection

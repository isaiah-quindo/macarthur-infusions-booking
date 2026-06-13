@extends('emails.layout')

@php($inPerson = $booking->payment_method === \App\Enums\PaymentMethod::InPerson)

@section('preheader', "{$booking->customer_name} · {$booking->service->name} · ".$booking->startsAtClinic()->format('j M, g:ia'))

@section('body')
    <p style="font-size:12px; font-weight:bold; text-transform:uppercase; letter-spacing:1px; color:#ef7a2a; margin:0 0 6px;">New booking</p>
    <h1 style="font-family:Georgia,'Times New Roman',serif; font-size:24px; line-height:1.2; color:#0c3848; margin:0 0 4px; font-weight:bold;">
        {{ $booking->customer_name }}
    </h1>
    <p style="font-size:14px; color:#5a6b75; margin:0;">{{ $booking->startsAtClinic()->format('l j F, g:ia') }}</p>

    @if ($inPerson)
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:18px;">
            <tr>
                <td style="background-color:#fff4ec; border:1px solid #f4c9a8; border-radius:10px; padding:12px 16px; font-size:14px; color:#c75e12; font-weight:600;">
                    Paying at the clinic &mdash; collect {{ $booking->service->priceFormatted() }} at the appointment.
                </td>
            </tr>
        </table>
    @endif

    @include('emails.partials.booking-details', ['paymentNote' => $inPerson ? ' · to collect' : ' · paid online'])

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #e3ecef; border-radius:12px;">
        <tr>
            <td style="padding:16px 20px; font-size:14px; line-height:1.9; color:#0c1f2c;">
                <span style="font-size:12px; text-transform:uppercase; letter-spacing:0.8px; color:#5a6b75;">Contact</span><br>
                <a href="mailto:{{ $booking->customer_email }}" style="color:#1f8a4a; text-decoration:none; font-weight:600;">{{ $booking->customer_email }}</a><br>
                <a href="tel:{{ preg_replace('/\s+/', '', $booking->customer_phone) }}" style="color:#1f8a4a; text-decoration:none; font-weight:600;">{{ $booking->customer_phone }}</a>
                @if ($booking->notes)
                    <br><span style="font-size:12px; text-transform:uppercase; letter-spacing:0.8px; color:#5a6b75;">Notes</span><br>
                    <span style="color:#0c1f2c;">{{ $booking->notes }}</span>
                @endif
            </td>
        </tr>
    </table>

    <div style="margin-top:22px;">
        @include('emails.partials.button', ['url' => route('admin.bookings.show', $booking), 'label' => 'Open in admin', 'color' => '#0c3848'])
    </div>
@endsection

@extends('emails.layout')

@section('preheader', "Action needed: payment captured but slot lost — {$booking->reference}.")

@section('body')
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:16px;">
        <tr>
            <td style="background-color:#fdeaea; border:1px solid #f1b6b6; border-radius:10px; padding:14px 18px; font-size:14px; line-height:1.6; color:#b91c1c;">
                <strong>&#9888; Needs review &mdash; payment captured, slot lost.</strong><br>
                This customer's card was charged, but their hold lapsed during payment and the slot was taken by another booking.
                Call them to re-slot, or refund the payment in the Square Dashboard.
            </td>
        </tr>
    </table>

    @include('emails.partials.booking-details', ['paymentNote' => ' · PAID'])

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #e3ecef; border-radius:12px;">
        <tr>
            <td style="padding:16px 20px; font-size:14px; line-height:1.9; color:#0c1f2c;">
                <span style="font-size:12px; text-transform:uppercase; letter-spacing:0.8px; color:#5a6b75;">Contact</span><br>
                <a href="mailto:{{ $booking->customer_email }}" style="color:#1f8a4a; text-decoration:none; font-weight:600;">{{ $booking->customer_email }}</a><br>
                <a href="tel:{{ preg_replace('/\s+/', '', $booking->customer_phone) }}" style="color:#1f8a4a; text-decoration:none; font-weight:600;">{{ $booking->customer_phone }}</a>
            </td>
        </tr>
    </table>

    <div style="margin-top:22px;">
        @include('emails.partials.button', ['url' => route('admin.bookings.show', $booking), 'label' => 'Open in admin', 'color' => '#b91c1c'])
    </div>
@endsection

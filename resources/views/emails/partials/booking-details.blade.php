@php
    $detailRows = [
        ['When', $booking->startsAtClinic()->format('l j F Y').'<br>'.$booking->startsAtClinic()->format('g:ia').'–'.$booking->startsAtClinic()->addMinutes($booking->service->duration_minutes)->format('g:ia')],
        ['Service', e($booking->service->name)],
        ['Location', e(config('booking.clinic.address'))],
        ['Price', $booking->service->priceFormatted().($paymentNote ?? '')],
    ];
@endphp
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:22px 0; border:1px solid #e3ecef; border-radius:12px; overflow:hidden;">
    <tr>
        <td style="background-color:#eef5f7; padding:12px 20px; border-bottom:1px solid #e3ecef;">
            <span style="font-family:'Courier New',monospace; font-size:15px; font-weight:bold; color:#0c3848; letter-spacing:1px;">{{ $booking->reference }}</span>
        </td>
    </tr>
    @foreach ($detailRows as [$label, $value])
        <tr>
            <td style="padding:12px 20px; {{ ! $loop->last ? 'border-bottom:1px solid #f0f4f6;' : '' }}">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                    <tr>
                        <td style="font-size:12px; text-transform:uppercase; letter-spacing:0.8px; color:#5a6b75; vertical-align:top; padding-right:12px;" width="90">{{ $label }}</td>
                        <td style="font-size:14px; line-height:1.6; color:#0c1f2c; font-weight:600; text-align:right;">{!! $value !!}</td>
                    </tr>
                </table>
            </td>
        </tr>
    @endforeach
</table>

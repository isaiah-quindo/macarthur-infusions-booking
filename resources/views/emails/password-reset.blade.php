@extends('emails.layout')

@section('preheader', 'A request was made to reset your admin password.')

@section('body')
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:14px;">
        <tr>
            <td style="background-color:#1f7a8c; border-radius:999px; padding:5px 14px; font-size:12px; font-weight:bold; color:#ffffff; text-transform:uppercase; letter-spacing:0.6px;">
                Password reset
            </td>
        </tr>
    </table>

    <h1 style="font-family:Georgia,'Times New Roman',serif; font-size:26px; line-height:1.2; color:#0c3848; margin:0 0 12px; font-weight:bold;">
        Hi {{ explode(' ', $user->name)[0] }},
    </h1>

    <p style="font-size:15px; line-height:1.7; color:#0c1f2c; margin:0 0 20px;">
        We received a request to reset the password for your <strong>{{ config('booking.clinic.name') }}</strong>
        admin account. Click the button below to choose a new password.
    </p>

    @include('emails.partials.button', ['url' => $resetUrl, 'label' => 'Reset password'])

    <p style="font-size:13px; line-height:1.7; color:#5a6b75; margin:22px 0 0;">
        This link will expire in 60 minutes. If you didn't ask to reset your password,
        you can safely ignore this email — your account is still secure.
    </p>

    <p style="font-size:12px; line-height:1.6; color:#5a6b75; margin:18px 0 0; word-break:break-all;">
        Button not working? Paste this link into your browser:<br>
        <a href="{{ $resetUrl }}" style="color:#1f7a8c;">{{ $resetUrl }}</a>
    </p>
@endsection

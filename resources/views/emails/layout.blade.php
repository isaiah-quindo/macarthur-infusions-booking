<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="x-apple-disable-message-reformatting">
    <title>{{ config('booking.clinic.name') }}</title>
    <!--[if mso]><style>* { font-family: Helvetica, Arial, sans-serif !important; }</style><![endif]-->
</head>
<body style="margin:0; padding:0; width:100%; background-color:#fbf7f1; -webkit-font-smoothing:antialiased; font-family:'Helvetica Neue',Helvetica,Arial,sans-serif; color:#0c1f2c;">

    {{-- Inbox preview text, hidden in the body --}}
    <div style="display:none; max-height:0; overflow:hidden; mso-hide:all; font-size:1px; line-height:1px; color:#fbf7f1; opacity:0;">
        @yield('preheader', config('booking.clinic.name'))&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;
    </div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#fbf7f1;">
        <tr>
            <td align="center" style="padding:28px 12px;">

                <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px; max-width:600px;">

                    {{-- Header --}}
                    <tr>
                        <td style="background-color:#0c3848; border-radius:16px 16px 0 0; padding:22px 32px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="vertical-align:middle;" width="44">
                                        {{-- Embedded inline (CID) so it renders without depending on a public URL --}}
                                        <img src="{{ $message->embed(public_path('logo.png')) }}" width="40" height="40" alt="Macarthur Infusions"
                                             style="display:block; border:0; outline:none; width:40px; height:40px;">
                                    </td>
                                    <td style="vertical-align:middle; padding-left:12px; font-family:Georgia,'Times New Roman',serif; font-size:20px; font-weight:bold; color:#fbf7f1; letter-spacing:0.3px;">
                                        Macarthur <span style="color:#ef7a2a;">Infusions</span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="background-color:#ffffff; border-left:1px solid #e3ecef; border-right:1px solid #e3ecef; padding:32px;">
                            @yield('body')
                        </td>
                    </tr>

                    {{-- Accent bar (matches the website) --}}
                    <tr>
                        <td style="background-color:#ffffff; border-left:1px solid #e3ecef; border-right:1px solid #e3ecef; padding:0;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr style="height:5px;">
                                    <td style="background-color:#1f8a4a; height:5px; line-height:5px; font-size:0;">&nbsp;</td>
                                    <td style="background-color:#1f7a8c; height:5px; line-height:5px; font-size:0;">&nbsp;</td>
                                    <td style="background-color:#ef7a2a; height:5px; line-height:5px; font-size:0;">&nbsp;</td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="background-color:#0c1f2c; border-radius:0 0 16px 16px; padding:24px 32px; font-size:12px; line-height:1.7; color:#9fb3bc;">
                            <strong style="color:#fbf7f1; font-family:Georgia,serif; font-size:14px;">{{ config('booking.clinic.name') }}</strong><br>
                            {{ config('booking.clinic.address') }}<br>
                            <a href="tel:{{ preg_replace('/\s+/', '', config('booking.clinic.phone')) }}" style="color:#9fb3bc; text-decoration:none;">{{ config('booking.clinic.phone') }}</a>
                            &nbsp;·&nbsp;
                            <a href="{{ config('booking.clinic.website') }}" style="color:#9fb3bc; text-decoration:none;">macarthurinfusions.com.au</a>
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>
</body>
</html>

@php($btnColor = $color ?? '#ef7a2a')
{{-- Bulletproof-ish pill button (VML for Outlook) --}}
<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:4px 0;">
    <tr>
        <td align="center" bgcolor="{{ $btnColor }}" style="border-radius:999px;">
            <a href="{{ $url }}" target="_blank"
               style="display:inline-block; padding:13px 30px; font-family:'Helvetica Neue',Helvetica,Arial,sans-serif; font-size:14px; font-weight:bold; color:#ffffff; text-decoration:none; border-radius:999px;">
                {{ $label }}
            </a>
        </td>
    </tr>
</table>

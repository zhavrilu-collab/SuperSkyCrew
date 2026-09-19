@php
    $palette = $organization->themePalette();
@endphp
<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="utf-8">
    <title>{{ $heading }}</title>
</head>
<body style="margin:0;padding:0;background:{{ $palette['light'] }};font-family:'Segoe UI',Arial,sans-serif;color:{{ $palette['text'] }};font-size:14px;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:{{ $palette['light'] }};padding:24px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="560" cellspacing="0" cellpadding="0" style="max-width:560px;background:#fff;border-radius:16px;overflow:hidden;border:1px solid rgba(0,0,0,.04);">
                <tr>
                    <td style="background:linear-gradient(135deg, {{ $palette['primary'] }} 0%, {{ $palette['dark'] }} 100%);border-bottom:3px solid {{ $palette['gold'] }};padding:18px 24px;color:#fff;">
                        <div style="font-size:16px;font-weight:700;">{{ $organization->name }}</div>
                        <div style="font-size:12px;opacity:.8;margin-top:4px;">{{ $heading }}</div>
                    </td>
                </tr>
                <tr>
                    <td style="padding:24px;">
                        <p style="margin:0 0 12px;">Poštovani/na {{ $greetingName }},</p>
                        <p style="margin:0 0 16px;">{{ $intro }}</p>
                        @if($rows !== [])
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;">
                                @foreach($rows as $row)
                                    <tr>
                                        <td style="padding:8px 6px;border-bottom:1px solid #eef4ee;color:{{ $palette['primary'] }};font-size:12px;width:42%;">{{ $row['label'] }}</td>
                                        <td style="padding:8px 6px;border-bottom:1px solid #eef4ee;">{{ $row['value'] }}</td>
                                    </tr>
                                @endforeach
                            </table>
                        @endif
                        <p style="margin:20px 0 0;">
                            <a href="{{ $actionUrl }}" style="display:inline-block;background:{{ $palette['primary'] }};color:#fff;text-decoration:none;padding:10px 16px;border-radius:9px;font-weight:600;font-size:13px;">{{ $actionLabel }}</a>
                        </p>
                        <p style="margin:16px 0 0;font-size:12px;color:#6a7a6a;">{{ $footer }}</p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>

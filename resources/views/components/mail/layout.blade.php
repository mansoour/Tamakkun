@props(['title' => null])

{{-- Email-safe RTL layout: tables and inline styles only. --}}
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'تمكّن' }}</title>
</head>
<body style="margin:0;padding:0;background:#F7F4FB;font-family:Tahoma,'Segoe UI',Arial,sans-serif;color:#302B3A;direction:rtl;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F7F4FB;padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#FFFFFF;border:1px solid #E9E2F2;border-radius:16px;">
                    <tr>
                        <td style="background:#7458B5;border-radius:16px 16px 0 0;padding:20px 24px;color:#FFFFFF;font-size:22px;font-weight:bold;text-align:right;">تمكّن</td>
                    </tr>
                    <tr>
                        <td style="padding:24px;font-size:15px;line-height:1.8;text-align:right;" dir="rtl">
                            {{ $slot }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 24px;border-top:1px solid #E9E2F2;color:#6B6574;font-size:12px;text-align:right;">
                            خطوتك اليوم… تصنع نتيجتك غدًا<br>
                            هذه رسالة آلية من منصة تمكّن، يرجى عدم الرد عليها.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>

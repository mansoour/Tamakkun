<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: plexarabic; font-size: 10pt; color: #302B3A; direction: rtl; }
        h1 { font-size: 18pt; color: #5F4699; margin: 0 0 4pt; }
        .meta { color: #6B6574; font-size: 9pt; margin-bottom: 10pt; }
        table { width: 100%; border-collapse: collapse; direction: rtl; }
        th { background: #EFE9F8; color: #4B377A; font-weight: bold; text-align: right; padding: 5pt; border: 0.5pt solid #C9B9E8; }
        td { padding: 4pt 5pt; border: 0.5pt solid #E9E2F2; text-align: right; }
        tr:nth-child(even) td { background: #F7F4FB; }
        .footer { color: #6B6574; font-size: 8pt; margin-top: 10pt; }
    </style>
</head>
<body dir="rtl">
    <h1>تمكّن — {{ $report['title'] }}</h1>
    <div class="meta">{{ $report['description'] }} · أُنشئ في {{ $report['generated_at']->format('Y-m-d H:i') }} · {{ count($report['rows']) }} صف</div>

    @if (empty($report['rows']))
        <p>لا توجد بيانات لهذا التقرير.</p>
    @else
        <table dir="rtl">
            <thead>
                <tr>
                    @foreach ($report['columns'] as $column)
                        <th>{{ $column }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($report['rows'] as $row)
                    <tr>
                        @foreach ($row as $cell)
                            {{-- Signed numbers (+6, -3) must stay left-to-right inside RTL text. --}}
                            <td>@if (is_string($cell) && preg_match('/^[+-]\d/', $cell) === 1)<span dir="ltr">{{ $cell }}</span>@else{{ $cell }}@endif</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer">تقرير سري يحتوي بيانات طالبات. لا تشاركيه خارج المدرسة.</div>
</body>
</html>

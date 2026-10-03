<x-mail.layout :title="$title">
    <p style="margin:0 0 12px;font-size:18px;font-weight:bold;color:#4B377A;">{{ $title }}</p>
    <p style="margin:0 0 20px;">{{ $body }}</p>
    @if ($url)
        <p style="margin:0;">
            <a href="{{ $url }}" style="display:inline-block;background:#7458B5;color:#FFFFFF;text-decoration:none;padding:12px 20px;border-radius:12px;font-weight:bold;">{{ $action ?? 'فتح المنصة' }}</a>
        </p>
    @endif
</x-mail.layout>

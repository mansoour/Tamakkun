<meta charset="utf-8">
{{-- Tamakkun (تمكّن). Copyright (c) 2026 Mansoour (https://mansoour.com). All rights reserved. --}}
<!-- Developed by Mansoour · https://mansoour.com · © 2026 All rights reserved -->
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="theme-color" content="#7458B5">
<meta name="author" content="Mansoour (mansoour.com)">
<meta name="copyright" content="© 2026 Mansoour (mansoour.com)">
<link rel="manifest" href="{{ route('manifest') }}">
{{-- Tab icon. The ?v= changes whenever the file changes, so browsers and Cloudflare never keep an old icon. --}}
<link rel="icon" href="{{ asset('favicon.ico') }}?v={{ @filemtime(public_path('favicon.ico')) }}" sizes="32x32">
<link rel="icon" type="image/png" href="{{ asset('icons/icon-192.png') }}?v={{ @filemtime(public_path('icons/icon-192.png')) }}" sizes="192x192">
<link rel="apple-touch-icon" href="{{ asset('icons/apple-touch-icon.png') }}?v={{ @filemtime(public_path('icons/apple-touch-icon.png')) }}">
<meta name="apple-mobile-web-app-title" content="{{ $platformName }}">

<title>{{ isset($title) && $title ? $title.' — ' : '' }}{{ $platformName }}</title>

{{-- Link previews (WhatsApp, X, Telegram). The image is optional; see docs/graphics.md. --}}
<meta name="description" content="{{ $platformName }}: منصة للاستعداد لاختباري القدرات العامة والتحصيلي بدروس مصوّرة وألعاب واختبارات إلكترونية ومتابعة من الموجهة الطلابية.">
<meta property="og:type" content="website">
<meta property="og:locale" content="ar_SA">
<meta property="og:site_name" content="{{ $platformName }}">
<meta property="og:title" content="{{ isset($title) && $title ? $title.' — ' : '' }}{{ $platformName }}">
<meta property="og:description" content="استعداد • تدريب • متابعة • إنجاز — القدرات الكمي واللفظي والتحصيلي في مسار واحد.">
<meta property="og:url" content="{{ url()->current() }}">
@if (is_file(public_path('images/og-image.jpg')))
    <meta property="og:image" content="{{ asset('images/og-image.jpg') }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta name="twitter:card" content="summary_large_image">
@endif

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=alexandria:700,800|ibm-plex-sans-arabic:400,500,700&display=swap" rel="stylesheet">

@vite(['resources/css/app.css', 'resources/js/app.js'])

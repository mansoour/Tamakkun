<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="theme-color" content="#7458B5">
<link rel="manifest" href="{{ route('manifest') }}">
<link rel="apple-touch-icon" href="{{ asset('icons/apple-touch-icon.png') }}">
<meta name="apple-mobile-web-app-title" content="{{ $platformName }}">

<title>{{ isset($title) && $title ? $title.' — ' : '' }}{{ $platformName }}</title>

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=alexandria:700,800|ibm-plex-sans-arabic:400,500,700&display=swap" rel="stylesheet">

@vite(['resources/css/app.css', 'resources/js/app.js'])

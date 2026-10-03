<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="theme-color" content="#7458B5">

<title>{{ isset($title) && $title ? $title.' — ' : '' }}{{ $platformName }}</title>

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=alexandria:500,600,700|ibm-plex-sans-arabic:400,500,600,700&display=swap" rel="stylesheet">

@vite(['resources/css/app.css', 'resources/js/app.js'])

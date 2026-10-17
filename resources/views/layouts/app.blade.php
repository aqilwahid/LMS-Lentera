<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#245D83">
    <title>@yield('title', 'LENTERA')</title>
    <link rel="icon" href="{{ asset('assets/lentera-logo.png') }}">
    <link rel="stylesheet" href="{{ asset('styles-v2.css') }}">
    <link rel="stylesheet" href="{{ asset('waiting-room.css') }}">
    <script src="{{ asset('waiting-room.js') }}" defer></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="{{ auth()->user()?->role === 'participant' ? 'participant' : '' }}">
<a class="skip-link" href="#main-content">Lewati navigasi</a>
<x-navigation />
<main id="main-content" class="container server-main" tabindex="-1">
    @if(config('lentera.demo_enabled'))
        <p class="pill">Lingkungan contoh · data sintetis · bukan produksi</p>
    @endif
    @if(session('status')) <div class="server-status" role="status">{{ session('status') }}</div> @endif
    <x-validation-errors />
    @yield('content')
</main>
<footer class="public-footer container"><span>LENTERA · Literasi dan Edukasi Kebanksentralan</span></footer>
</body>
</html>

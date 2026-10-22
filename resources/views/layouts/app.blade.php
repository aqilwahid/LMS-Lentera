<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#1f5f8b">
    <meta name="description" content="LENTERA, ruang literasi dan edukasi kebanksentralan Bank Indonesia.">
    <title>@yield('title', 'LENTERA')</title>
    <link rel="icon" href="{{ asset('assets/lentera-logo.webp') }}">
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
<footer class="public-footer">
    <div class="pf-inner">
        <div><img src="{{ asset('assets/lentera-logo.webp') }}" alt="LENTERA" width="120" height="40" loading="lazy"><p>Literasi dan Edukasi Kebanksentralan.</p></div>
        <nav aria-label="Tautan footer"><h2>Akses</h2><a href="{{ route('home') }}">Beranda</a><a href="{{ route('login') }}">Masuk pengelola / mitra</a></nav>
        <div><h2>Bantuan</h2><p>Belum menerima QR kelas? Hubungi pengelola kegiatan Anda.</p></div>
    </div>
    <div class="pf-copy">&copy; {{ now()->year }} LENTERA &middot; Literasi dan Edukasi Kebanksentralan</div>
</footer>
</body>
</html>

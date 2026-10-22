@php
    $user = auth()->user();
    $links = [];
    if (request()->route('token')) {
        $links[] = ['Kelas saya', route('class.show', request()->route('token')), request()->routeIs('class.show')];
    }
    if ($user) {
        $links[] = ['Beranda', route('dashboard'), request()->routeIs('dashboard', 'activities.*', 'l1.*')];
        if (in_array($user->role, ['bi_admin', 'partner'])) {
            $links[] = ['Analisis L2', route('analytics.l2'), request()->routeIs('analytics.l2')];
        }
        if ($user->role === 'bi_admin') {
            $kind = request()->route('kind');
            array_push($links,
                ['Kelola kegiatan', route('management.activities.index'), request()->routeIs('management.activities.*', 'management.sessions.*', 'materials.*', 'class.access', 'l2.settings.*')],
                ['Institusi', route('masters.index', 'institusi'), request()->routeIs('masters.*') && $kind === 'institusi'],
                ['Narasumber', route('masters.index', 'narasumber'), request()->routeIs('masters.*') && $kind === 'narasumber'],
                ['Kepesertaan', route('participants.index'), request()->routeIs('participants.*')],
                ['Instrumen L1', route('instruments.index'), request()->routeIs('instruments.*')],
                ['Instrumen L2', route('l2.instruments.index'), request()->routeIs('l2.instruments.*')],
                ['Akun', route('accounts.index'), request()->routeIs('accounts.*')],
                ['Impor historis', route('imports.index'), request()->routeIs('imports.*')],
                ['Riwayat perubahan', route('management.audit'), request()->routeIs('management.audit')],
            );
        }
    }
    $roleLabel = ['participant' => 'Peserta', 'bi_admin' => 'Pengelola BI', 'partner' => 'Mitra · Akses lihat'][$user?->role] ?? 'Akun';
@endphp
<header class="top-header server-header" x-data="{ menu: false, account: false }" @keydown.escape.window="menu = false; account = false">
    <div class="server-header-bar">
        <a class="server-brand" href="{{ $user ? route('dashboard') : route('home') }}" aria-label="LENTERA — beranda"><img class="brand-logo" width="150" height="50" src="{{ asset('assets/lentera-logo.webp') }}" alt="LENTERA"></a>
        <div class="server-header-tools">
            <img class="bi-logo" width="120" height="21" src="{{ asset('assets/bank-indonesia-logo.webp') }}" alt="Bank Indonesia">
            @auth
                <div class="account" @click.outside="account = false">
                    <button class="account-btn" type="button" @click="account = !account" :aria-expanded="account.toString()" aria-controls="account-menu">
                        <span class="avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
                        <span class="account-name">{{ $user->name }}</span>
                        <svg class="account-caret" viewBox="0 0 20 20" width="16" height="16" aria-hidden="true" focusable="false"><path d="M5 7.5l5 5 5-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                    <div id="account-menu" class="account-menu" x-show="account" x-cloak>
                        <p><strong>{{ $user->name }}</strong><span>{{ $roleLabel }}</span></p>
                        <form method="post" action="{{ route('logout') }}">@csrf<button class="btn secondary wide" type="submit">Keluar</button></form>
                    </div>
                </div>
                <noscript><form method="post" action="{{ route('logout') }}">@csrf<button class="btn secondary" type="submit">Keluar</button></form></noscript>
            @endauth
            @if(count($links) > 1)
                <button class="nav-toggle btn secondary" type="button" @click="menu = !menu" :aria-expanded="menu.toString()" aria-controls="server-nav">
                    <svg viewBox="0 0 20 20" width="18" height="18" aria-hidden="true" focusable="false"><path d="M3 5h14M3 10h14M3 15h14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>Menu
                </button>
            @endif
        </div>
    </div>
    @if($links)
        <nav id="server-nav" class="server-nav" :class="{ 'is-open': menu }" aria-label="Navigasi utama">
            @php
            $icons = [
                'Kelas saya' => 'M4 5a2 2 0 012-2h12v16H6a2 2 0 00-2 2V5zM8 7h6',
                'Beranda' => 'M3 11l9-8 9 8M5 10v10h5v-6h4v6h5V10',
                'Analisis L2' => 'M4 20V10M10 20V4M16 20v-7M22 20H2',
                'Kelola kegiatan' => 'M3 5h18v16H3zM3 10h18M8 3v4M16 3v4',
                'Institusi' => 'M3 21h18M5 21V9l7-5 7 5v12M9 21v-6h6v6',
                'Narasumber' => 'M12 12a4 4 0 100-8 4 4 0 000 8zM4 21a8 8 0 0116 0',
                'Kepesertaan' => 'M9 11a3 3 0 100-6 3 3 0 000 6zM2 20a7 7 0 0114 0M17 11a3 3 0 100-6M22 20a6 6 0 00-4-5.6',
                'Instrumen L1' => 'M9 4h6v3H9zM6 5H5v16h14V5h-1M9 12h6M9 16h4',
                'Instrumen L2' => 'M9 4h6v3H9zM6 5H5v16h14V5h-1M9 12l2 2 4-4',
                'Akun' => 'M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6l8-3z',
                'Impor historis' => 'M12 3v12M7 10l5 5 5-5M4 21h16',
                'Riwayat perubahan' => 'M3 12a9 9 0 109-9M3 4v5h5M12 7v5l3 2',
            ];
        @endphp
        @foreach($links as [$label, $href, $active])
                <a href="{{ $href }}" @if($active) aria-current="page" @endif><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="{{ $icons[$label] ?? 'M5 12h14' }}"/></svg>{{ $label }}</a>
            @endforeach
        </nav>
    @endif
</header>
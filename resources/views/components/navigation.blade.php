<header class="top-header server-header" x-data="{ open: false }">
    <a href="{{ auth()->check() ? route('dashboard') : route('login') }}" aria-label="LENTERA — beranda"><img class="brand-logo" src="{{ asset('assets/lentera-logo.png') }}" alt="LENTERA"></a>
    <nav aria-label="Navigasi utama">
        @auth
            <a href="{{ route('dashboard') }}">Beranda</a>
            <button class="btn secondary" type="button" @click="open = !open" :aria-expanded="open.toString()" aria-controls="account-menu">Akun</button>
            <div id="account-menu" x-show="open" x-cloak>
                <span>{{ auth()->user()->name }} · {{ ['participant'=>'Peserta','bi_admin'=>'Pengelola BI','partner'=>'Mitra · Akses lihat'][auth()->user()->role] ?? 'Akun' }}</span>
                <form method="post" action="{{ route('logout') }}">@csrf<button class="btn secondary" type="submit">Keluar</button></form>
            </div>
            <noscript><form method="post" action="{{ route('logout') }}">@csrf<button type="submit">Keluar</button></form></noscript>
        @endauth
    </nav>
    <img class="bi-logo" src="{{ asset('assets/bank-indonesia-logo.png') }}" alt="Bank Indonesia">
</header>

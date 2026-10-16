<header class="top-header server-header" x-data="{ open: false }">
    <a href="{{ auth()->check() ? route('dashboard') : route('home') }}" aria-label="LENTERA — beranda"><img class="brand-logo" src="{{ asset('assets/lentera-logo.png') }}" alt="LENTERA"></a>
    <nav aria-label="Navigasi utama">
        @if(request()->route('token'))
            <a href="{{ route('class.show', request()->route('token')) }}">Kelas saya</a>
        @endif
        @auth
            <a href="{{ route('dashboard') }}">Beranda</a>
            @if(in_array(auth()->user()->role,['bi_admin','partner']))<a href="{{ route('analytics.l2') }}">Analisis L2</a>@endif
            @if(auth()->user()->role === 'bi_admin')
                <a href="{{ route('management.activities.index') }}">Kelola kegiatan</a>
                <a href="{{ route('masters.index','institusi') }}">Institusi</a>
                <a href="{{ route('masters.index','narasumber') }}">Narasumber</a>
                <a href="{{ route('participants.index') }}">Kepesertaan</a>
                <a href="{{ route('instruments.index') }}">Instrumen L1</a>
                <a href="{{ route('l2.instruments.index') }}">Instrumen L2</a>
                <a href="{{ route('management.audit') }}">Riwayat perubahan</a>
            @endif
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

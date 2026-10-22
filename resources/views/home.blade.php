@extends('layouts.app')
@section('title','LENTERA — Ikuti kelas')
@section('content')
<div class="lp">
    <section class="lp-hero" aria-labelledby="hero-title">
        <div class="lp-wrap">
            <div>
                <p class="eyebrow">LITERASI KEBANKSENTRALAN</p>
                <h1 id="hero-title">Ikuti kelas bersama LENTERA.</h1>
                <p class="lead">Buka tautan atau pindai QR kelas yang dibagikan pengelola, lalu isi formulir peserta. Anda tidak perlu membuat akun atau masuk.</p>
                <div class="cta-row">
                    <a class="btn accent large" href="#cara-ikut">Cara mengikuti kelas</a>
                    <a class="btn secondary large" href="{{ route('login') }}">Masuk pengelola / mitra</a>
                </div>
                <div class="lp-chips"><span>Tanpa akun</span><span>Lewat QR / tautan</span><span>Materi &amp; evaluasi online</span></div>
                <div class="lp-trust"><span>Diselenggarakan oleh</span><img src="{{ asset('assets/bank-indonesia-logo.webp') }}" alt="Bank Indonesia" width="120" height="21"></div>
            </div>
            <div class="lp-art">
                <figure><img src="{{ asset('assets/learning.webp') }}" alt="" width="900" height="675" fetchpriority="high"></figure>
                <div class="lp-float a" aria-hidden="true"><i>▶</i>Materi PDF</div>
                <div class="lp-float b" aria-hidden="true"><i>★</i>Evaluasi L1 &amp; L2</div>
            </div>
        </div>
    </section>

    <section class="lp-section" id="cara-ikut" aria-labelledby="steps-title">
        <div class="lp-wrap">
            <h2 id="steps-title">Empat langkah mengikuti kelas</h2>
            <p class="sub">Semua dilakukan dari browser, tanpa akun dan tanpa aplikasi tambahan.</p>
            <ol class="lp-steps">
                <li class="lp-step"><h3>Buka QR atau tautan</h3><p>Pindai QR kelas atau buka tautan dari pengelola kegiatan.</p></li>
                <li class="lp-step"><h3>Isi formulir peserta</h3><p>Lengkapi identitas singkat untuk kepesertaan dan evaluasi.</p></li>
                <li class="lp-step"><h3>Pelajari materi</h3><p>Baca atau unduh materi PDF yang diterbitkan untuk sesi Anda.</p></li>
                <li class="lp-step"><h3>Kirim evaluasi</h3><p>Isi evaluasi L1, pretest, dan post-test sesuai jadwal kelas.</p></li>
            </ol>
        </div>
    </section>

    <section class="lp-section" aria-labelledby="roles-title">
        <div class="lp-wrap">
            <h2 id="roles-title">Siapa Anda?</h2>
            <p class="sub">Pilih jalur yang sesuai agar langsung tahu harus mulai dari mana.</p>
            <div class="lp-roles">
                <article class="lp-role r1"><small>Peserta</small><h3>Saya mengikuti kelas</h3><p>Tidak perlu login. Gunakan QR atau tautan dari pengelola, lalu isi formulir.</p><a class="btn" href="#cara-ikut">Lihat langkahnya</a></article>
                <article class="lp-role r2"><small>Pengelola BI</small><h3>Saya mengelola kegiatan</h3><p>Atur kegiatan, sesi, QR, materi, instrumen, dan lihat ringkasan hasil.</p><a class="btn" href="{{ route('login') }}">Masuk pengelola</a></article>
                <article class="lp-role r3"><small>Mitra</small><h3>Saya melihat hasil institusi</h3><p>Akses lihat agregat tanpa identitas individu, sesuai publikasi dari BI.</p><a class="btn" href="{{ route('login') }}">Masuk mitra</a></article>
            </div>
        </div>
    </section>
</div>
@endsection
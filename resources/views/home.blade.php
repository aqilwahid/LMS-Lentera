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
                    <a class="btn primary large" href="#cara-ikut">Cara mengikuti kelas</a>
                    <a class="btn secondary large" href="{{ route('login') }}">Masuk pengelola / mitra</a>
                </div>
                <div class="lp-trust"><span>Diselenggarakan oleh</span><img src="{{ asset('assets/bank-indonesia-logo.webp') }}" alt="Bank Indonesia" width="130" height="23"></div>
            </div>
            <figure><img src="{{ asset('assets/learning.webp') }}" alt="" width="900" height="675" fetchpriority="high"></figure>
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

    <section class="lp-section">
        <div class="lp-wrap">
            <div class="lp-cta">
                <div><h2>Pengelola BI atau mitra?</h2><p>Masuk untuk mengelola kegiatan, materi, instrumen, dan membaca ringkasan hasil pembelajaran.</p></div>
                <a class="btn accent large" href="{{ route('login') }}">Masuk sekarang</a>
            </div>
        </div>
    </section>
</div>
@endsection

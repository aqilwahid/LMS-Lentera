@extends('layouts.app')
@section('title', 'Masuk — LENTERA')
@section('content')
<div class="login-layout panel">
    <div class="login-form-side">
        <form class="login-form" method="post" action="{{ route('login') }}">
            @csrf
            <p class="eyebrow">RUANG LITERASI KEBANKSENTRALAN</p>
            <h1>Masuk pengelola / mitra.</h1>
            <p>Halaman ini untuk pegawai BI dan mitra perguruan tinggi. Peserta cukup membuka QR atau tautan kelas dan mengisi formulir tanpa login.</p>
            <div class="field"><label for="email">Email</label><input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required maxlength="255" @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
                @error('email')<p id="email-error">{{ $message }}</p>@enderror
            </div>
            <div class="field"><label for="password">Kata sandi</label><input id="password" name="password" type="password" autocomplete="current-password" required @error('password') aria-invalid="true" @enderror></div>
            <button class="btn primary wide" type="submit">Masuk</button>
            <p>Belum memiliki akun atau perlu pemulihan akses? Hubungi pengelola kegiatan.</p>
        </form>
    </div>
    <div class="login-art"><img src="{{ asset('assets/lighthouse.png') }}" alt=""><div class="login-art-caption"><h2>Perluas wawasan bersama LENTERA.</h2><p>Belajar, mengikuti kegiatan, dan menyampaikan evaluasi.</p></div></div>
</div>
@endsection

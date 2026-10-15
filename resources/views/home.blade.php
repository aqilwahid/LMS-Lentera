@extends('layouts.app')
@section('title','LENTERA — Ikuti kelas')
@section('content')
<section class="panel server-form"><p class="eyebrow">LITERASI KEBANKSENTRALAN</p><h1>Ikuti kelas bersama LENTERA.</h1>
<p>Buka tautan atau pindai QR kelas yang dibagikan pengelola, lalu isi formulir peserta. Anda tidak perlu membuat akun atau masuk.</p>
<p>Belum menerima QR kelas? Hubungi pengelola kegiatan Anda.</p>
<a class="btn secondary" href="{{ route('login') }}">Masuk pengelola / mitra</a></section>
@endsection

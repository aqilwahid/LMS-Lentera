@extends('layouts.app')
@section('title','QR kelas — LENTERA')
@section('content')
<div class="server-form"><a href="{{ route('dashboard') }}">Kembali ke ringkasan</a>
<section class="panel"><h1>Bagikan kelas.</h1><h2>{{ $session->title }}</h2><p>{{ $session->speaker_name }}</p>
<img src="{{ route('class.qr',$session) }}" alt="QR untuk membuka formulir peserta kelas {{ $session->title }}" width="320" height="320" style="max-width:100%;height:auto">
<div class="field"><label for="join-url">Tautan peserta</label><input id="join-url" type="url" value="{{ $joinUrl }}" readonly></div>
<p><a href="{{ $joinUrl }}">Buka formulir peserta</a> · <a href="{{ route('class.qr',$session) }}" download="qr-kelas-{{ $session->id }}.svg">Unduh QR SVG</a></p>
<p>Pendaftaran: {{ $session->join_opens_at }} sampai {{ $session->join_closes_at }} ({{ config('app.timezone') }}).</p>
<p>QR berisi tautan kelas, tanpa data pribadi peserta. Peserta mengisi formulir dan tidak perlu login. Gunakan alamat domain yang dapat diakses peserta saat membagikan QR; localhost hanya untuk pemeriksaan pada komputer ini.</p>
</section></div>
@endsection

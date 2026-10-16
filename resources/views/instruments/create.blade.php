@extends('layouts.app')
@section('title','Buat instrumen L1 — LENTERA')
@section('content')
<div class="server-form server-context"><a href="{{ route('instruments.index') }}">Kembali ke instrumen</a>
<div class="page-heading"><div><h1>Buat instrumen L1.</h1><p>Simpan sebagai draf, lalu periksa sebelum menerbitkan. Instrumen ini hanya tersedia bagi KPwBI Anda.</p></div></div>
<p class="server-status">Teks awal adalah salinan kerja atau salinan instrumen yang Anda pilih. Pastikan redaksinya telah disetujui sebelum digunakan untuk kegiatan nyata. Skala tetap 1–6; label verbal resmi belum ditetapkan.</p>
<form class="panel" method="post" action="{{ route('instruments.store') }}">@csrf
<input type="hidden" id="creation_nonce" name="creation_nonce" value="{{ old('creation_nonce',$nonce) }}">
<div class="field"><label for="name">Nama instrumen</label><input id="name" name="name" value="{{ old('name') }}" required maxlength="160"><p>Gunakan nama yang mudah dikenali. Nama seri tetap sama pada seluruh versinya.</p></div>
@include('instruments.questions')
<button class="btn primary" type="submit">Simpan draf</button>
</form></div>
@endsection

@extends('layouts.app')
@section('title','Buat instrumen L2 — LENTERA')
@section('content')
<div class="server-form server-context"><a href="{{ route('l2.instruments.index') }}">Kembali ke instrumen L2</a><div class="page-heading"><div><h1>Buat instrumen L2.</h1><p>Soal dan kunci tidak disediakan otomatis. Gunakan instrumen yang disetujui petugas berwenang.</p></div></div>
<form class="panel" method="get" action="{{ route('l2.instruments.create') }}"><div class="field"><label for="count">Jumlah soal</label><input id="count" type="number" name="count" min="1" max="{{ config('lentera.l2_max_questions') }}" value="{{ count($questions) }}" required></div><p>Tentukan jumlah sebelum mengisi. Menyiapkan ulang formulir mengosongkan isian yang belum disimpan.</p><button class="btn secondary" type="submit">Siapkan formulir</button></form>
<form class="panel" method="post" action="{{ route('l2.instruments.store') }}">@csrf<input type="hidden" id="creation_nonce" name="creation_nonce" value="{{ old('creation_nonce',$nonce) }}">
<div class="field"><label for="name">Nama instrumen L2</label><input id="name" name="name" value="{{ old('name') }}" required maxlength="160"></div>
<div class="field"><label for="instructions">Petunjuk tes (opsional)</label><textarea id="instructions" name="instructions" maxlength="5000">{{ old('instructions') }}</textarea></div>
@include('l2.instruments.questions')
<p>Nama seri dan jumlah soal pada formulir ini dipertahankan saat membuat versi berikutnya. Bobot setara; tidak ada timer atau ambang lulus.</p><button class="btn primary" type="submit">Simpan draf L2</button></form></div>
@endsection

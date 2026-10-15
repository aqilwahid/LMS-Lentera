@extends('layouts.app')
@section('title','Pengaturan kegiatan — LENTERA')
@section('content')
<div class="server-form"><a href="{{ route('management.activities.index') }}">Kembali ke daftar kegiatan</a>
<div class="page-heading"><div><h1>{{ $activity->exists ? 'Pengaturan kegiatan.' : 'Buat kegiatan.' }}</h1></div></div>
<form class="panel" method="post" action="{{ $activity->exists ? route('management.activities.update',$activity) : route('management.activities.store') }}">
@csrf
@if($activity->exists) @method('put')<input type="hidden" id="revision" name="revision" value="{{ old('revision',$activity->revision) }}"> @endif
<div class="field"><label for="title">Nama kegiatan</label><input id="title" name="title" value="{{ old('title',$activity->title) }}" maxlength="255" required></div>
<div class="field"><label for="description">Deskripsi kegiatan (opsional)</label><textarea id="description" name="description" maxlength="5000">{{ old('description',$activity->description) }}</textarea></div>
<button class="btn primary" type="submit">Simpan kegiatan</button>
</form>
@if($activity->exists)
<section class="panel"><h2>Sesi kegiatan</h2><p>Setiap sesi memiliki narasumber, instrumen L1, jadwal dan QR sendiri.</p><a class="btn primary" href="{{ route('management.sessions.create',$activity) }}">Tambah sesi</a>
<div class="server-list">@forelse($activity->sessions as $session)
<article><h3>{{ $session->title }}</h3><p>{{ $session->speaker_name }} · {{ $session->join_enabled ? 'QR aktif' : 'QR nonaktif' }}</p>
<a class="btn secondary" href="{{ route('management.sessions.edit',$session) }}">Atur sesi: {{ $session->title }}</a>
@if($session->join_enabled)<a href="{{ route('class.access',$session) }}">QR dan tautan kelas</a>@endif
</article>
@empty <p>Belum ada sesi. Tambahkan sesi untuk menyiapkan pendaftaran peserta.</p>@endforelse</div>
</section>
@endif
</div>
@endsection

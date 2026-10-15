@extends('layouts.app')
@section('title','Materi sesi — LENTERA')
@section('content')
<div class="server-form server-context"><a href="{{ route('management.sessions.edit',$session) }}">Kembali ke sesi</a><div class="page-heading"><div><h1>Materi sesi.</h1><p>{{ $session->title }}</p></div></div>
<form class="panel" method="post" enctype="multipart/form-data" action="{{ route('materials.store',$session) }}">@csrf
<input id="upload_nonce" name="upload_nonce" type="hidden" value="{{ old('upload_nonce',Illuminate\Support\Str::uuid()) }}">
<h2>Tambah materi PDF</h2>
<div class="field"><label for="title">Judul materi</label><input id="title" name="title" required maxlength="255" value="{{ old('title') }}"></div>
<div class="field"><label for="description">Keterangan (opsional)</label><textarea id="description" name="description" maxlength="3000">{{ old('description') }}</textarea></div>
<div class="field"><label for="pdf">Berkas PDF</label><input id="pdf" name="pdf" type="file" accept=".pdf,application/pdf" required><p>Maksimal {{ number_format($maxKb/1024,2,',','.') }} MB per berkas. Pilih ulang berkas jika validasi gagal.</p>@error('pdf')<p role="alert">{{ $message }}</p>@enderror</div>
<p>Unggahan disimpan privat dan belum terlihat oleh peserta sampai Anda menerbitkan versinya.</p><button class="btn primary">Unggah PDF</button></form>
<section class="panel server-list"><h2>Daftar materi</h2>@forelse($materials as $material)
<article><h3>{{ $material->title }}</h3><p>{{ $material->is_visible ? 'Terbit · versi '.$material->currentVersion?->version_number : 'Tersembunyi / belum terbit' }}</p><a href="{{ route('materials.show',$material) }}">Kelola materi: {{ $material->title }}</a></article>
@empty <p>Belum ada materi pada sesi ini.</p>@endforelse{{ $materials->links() }}</section></div>
@endsection

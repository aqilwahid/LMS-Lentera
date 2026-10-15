@extends('layouts.app')
@section('title','Versi materi — LENTERA')
@section('content')
<div class="server-form server-context"><a href="{{ route('materials.index',$material->session) }}">Kembali ke materi sesi</a><div class="page-heading"><div><h1>{{ $material->title }}</h1><p>{{ $material->description }}</p><p>{{ $material->is_visible ? 'Terbit untuk peserta' : 'Tersembunyi dari peserta' }}</p></div></div>
<form class="panel" method="post" enctype="multipart/form-data" action="{{ route('materials.version',$material) }}">@csrf
<input id="revision" name="revision" type="hidden" value="{{ old('revision',$material->revision) }}"><input id="upload_nonce" name="upload_nonce" type="hidden" value="{{ old('upload_nonce',Illuminate\Support\Str::uuid()) }}">
<h2>Unggah versi baru</h2><div class="field"><label for="pdf">Berkas PDF versi baru</label><input id="pdf" name="pdf" type="file" accept=".pdf,application/pdf" required><p>Maksimal {{ number_format($maxKb/1024,2,',','.') }} MB. Versi terbit saat ini tidak berubah sampai versi baru diterbitkan.</p>@error('pdf')<p role="alert">{{ $message }}</p>@enderror</div><button class="btn primary">Unggah versi baru</button></form>
@if($material->is_visible)<form class="panel" method="post" action="{{ route('materials.publish',$material) }}">@csrf<input name="revision" type="hidden" value="{{ $material->revision }}"><input name="operation" type="hidden" value="hide"><p>Menyembunyikan materi menghentikan akses lewat aplikasi. Versi dan riwayat tetap tersimpan. Berkas yang sudah diunduh tetap berada pada perangkat peserta.</p><button class="btn secondary">Sembunyikan materi</button></form>@endif
<section class="panel server-list"><h2>Riwayat versi</h2>@foreach($versions as $version)
<article><h3>Versi {{ $version->version_number }}{{ $material->is_visible && $material->current_version_id===$version->id ? ' · terbit' : '' }}</h3><p>{{ $version->original_filename }} · {{ number_format($version->size_bytes/1024,1,',','.') }} KB · {{ $version->created_at->format('d/m/Y H:i') }}</p>
<p><a href="{{ route('materials.admin.download',[$material,$version]) }}">Unduh versi {{ $version->version_number }}</a></p>
<form method="post" action="{{ route('materials.publish',$material) }}">@csrf<input name="revision" type="hidden" value="{{ $material->revision }}"><input name="operation" type="hidden" value="publish"><input name="version_id" type="hidden" value="{{ $version->id }}"><button class="btn secondary">Terbitkan versi {{ $version->version_number }}</button></form>
</article>@endforeach{{ $versions->links() }}</section></div>
@endsection

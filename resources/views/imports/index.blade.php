@extends('layouts.app')
@section('title','Impor historis — LENTERA')
@section('content')
<div class="page-heading"><div><p class="eyebrow">PENGELOLA BI</p><h1>Impor respons L1 historis.</h1><p>Unggah CSV (UTF-8, pemisah ; atau ,) dengan kolom: {{ implode(', ', $columns) }}. Nilai 1–6, kolom masukan boleh kosong. Baris ringkasan dikecualikan, dan institusi yang belum dikenal ditahan sampai dipetakan.</p></div></div>
<form class="panel" method="post" enctype="multipart/form-data" action="{{ route('imports.store') }}">@csrf
<div class="field"><label for="learning_session_id">Sesi tujuan</label><select id="learning_session_id" name="learning_session_id" required>@foreach($sessions as $s)<option value="{{ $s->id }}" @selected(old('learning_session_id')==$s->id)>{{ $s->title }} · {{ $s->starts_at->format('d/m/Y') }}</option>@endforeach</select></div>
<div class="field"><label for="file">Berkas CSV (maks. 2 MB, 2.000 baris)</label><input id="file" name="file" type="file" accept=".csv,text/csv,text/plain" required></div>
<button class="btn primary">Baca dan periksa berkas</button></form>
<section class="panel server-list">@forelse($batches as $b)
<article><h2>{{ $b->file_name }}</h2><p>{{ $b->session?->title }} · {{ $b->created_at->format('d/m/Y H:i') }}</p><a href="{{ route('imports.show',$b->id) }}">Buka pratinjau: {{ $b->file_name }}</a></article>
@empty <p>Belum ada impor.</p>@endforelse{{ $batches->links() }}</section>
@endsection

@extends('layouts.app')
@section('title','Kepesertaan — LENTERA')
@section('content')
<div class="page-heading"><div><p class="eyebrow">PENGELOLA BI</p><h1>Kepesertaan.</h1><p>Data peserta pada kegiatan dalam cakupan KPwBI Anda. Gunakan hanya untuk pengelolaan kegiatan.</p></div></div>
<form class="panel" method="get">
<div class="field"><label for="q">Cari nama, nomor identitas atau instansi</label><input id="q" name="q" maxlength="100" value="{{ request('q') }}"></div>
<div class="field"><label for="activity_id">Kegiatan</label><select id="activity_id" name="activity_id"><option value="">Semua kegiatan</option>@foreach($activities as $activity)<option value="{{ $activity->id }}" @selected((string)request('activity_id')===(string)$activity->id)>{{ $activity->title }}</option>@endforeach</select></div>
<div class="field"><label for="mapping">Pemetaan instansi</label><select id="mapping" name="mapping"><option value="all">Semua kepesertaan</option><option value="unmapped" @selected(request('mapping')==='unmapped')>Belum dipetakan</option></select></div>
<button class="btn secondary">Terapkan filter</button> <a href="{{ route('participants.index') }}">Reset filter</a>
</form>
<section class="panel server-list server-context">@forelse($enrollments as $enrollment)
<article><h2>{{ $enrollment->full_name ?? $enrollment->user?->name ?? 'Peserta lama' }}</h2><p>{{ $enrollment->activity->title }} · {{ $enrollment->joinedSession?->title ?? 'Kepesertaan kegiatan lama' }}</p>
<p>Instansi asli: {{ $enrollment->institution_name }}</p><p>{{ $enrollment->status==='active' ? 'Aktif' : 'Nonaktif' }} · {{ $enrollment->institution_id ? 'Dikenali saat pendaftaran' : ($enrollment->mapped_institution_id ? 'Dipetakan oleh pengelola' : 'Belum dipetakan') }}</p><a href="{{ route('participants.show',$enrollment) }}">Lihat kepesertaan #{{ $enrollment->id }}</a></article>
@empty <p>Belum ada kepesertaan yang sesuai filter.</p>@endforelse{{ $enrollments->links() }}</section>
@endsection

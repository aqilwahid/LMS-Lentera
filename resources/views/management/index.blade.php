@extends('layouts.app')
@section('title','Kelola kegiatan — LENTERA')
@section('content')
<div class="page-heading"><div><p class="eyebrow">PENGELOLA BI</p><h1>Kelola kegiatan.</h1><p>Kegiatan dalam cakupan KPwBI akun Anda.</p></div><a class="btn primary" href="{{ route('management.activities.create') }}">Buat kegiatan</a></div>
<section class="panel server-list">
@forelse($activities as $activity)
<article><h2>{{ $activity->title }}</h2><p>{{ $activity->is_demo ? 'Kegiatan contoh · ' : '' }}{{ $activity->sessions_count }} sesi</p><a class="btn secondary" href="{{ route('management.activities.edit',$activity) }}">Kelola kegiatan: {{ $activity->title }}</a></article>
@empty <h2>Belum ada kegiatan.</h2><p>Pilih Buat kegiatan untuk memulai.</p>
@endforelse
{{ $activities->links() }}
</section>
@endsection

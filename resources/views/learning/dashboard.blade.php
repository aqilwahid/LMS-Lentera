@extends('layouts.app')
@section('title', 'Kegiatan saya — LENTERA')
@section('content')
<div class="page-heading"><div><p class="eyebrow">RUANG BELAJAR</p><h1>Kegiatan saya.</h1><p>Selamat datang, {{ auth()->user()->name }}.</p></div></div>
<section class="panel">
    <div class="server-list">
    @forelse($enrollments as $enrollment)
        <article><p class="pill">{{ $enrollment->activity->is_demo ? 'Kegiatan contoh' : 'Kegiatan' }}</p><h2>{{ $enrollment->activity->title }}</h2>
        <p>{{ $enrollment->institution_name }} · {{ $enrollment->activity->sessions->count() }} sesi</p>
        <a class="btn primary" href="{{ route('activities.show', $enrollment->activity) }}">Lihat kegiatan</a></article>
    @empty
        <h2>Belum ada kegiatan yang diikuti.</h2><p>Hubungi pengelola untuk memeriksa undangan dan kepesertaan Anda.</p>
    @endforelse
    </div>
    {{ $enrollments->links() }}
</section>
@endsection

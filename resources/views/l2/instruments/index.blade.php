@extends('layouts.app')
@section('title','Instrumen L2 — LENTERA')
@section('content')
<div class="page-heading"><div><p class="eyebrow">PENGELOLA BI · EVALUASI</p><h1>Instrumen L2.</h1><p>Soal pilihan ganda untuk pretest dan post-test. Setiap soal memiliki empat opsi dan bobot setara.</p></div><a class="btn primary" href="{{ route('l2.instruments.create') }}">Buat instrumen L2</a></div>
<section class="panel server-list server-context">
@forelse($instruments as $instrument)
<article><h2>{{ $instrument->name }}</h2><p>Versi {{ $instrument->version }} · {{ count($instrument->questions) }} soal · {{ $instrument->published_at ? 'Terbit · terkunci' : 'Draf' }}</p><a class="btn secondary" href="{{ route('l2.instruments.show',$instrument) }}" aria-label="Buka L2: {{ $instrument->name }} versi {{ $instrument->version }}">Lihat soal dan kunci</a></article>
@empty<p>Belum ada instrumen L2. Siapkan redaksi soal dan kunci yang sudah diperiksa sebelum digunakan pada kegiatan nyata.</p>@endforelse
{{ $instruments->links() }}
</section>
@endsection

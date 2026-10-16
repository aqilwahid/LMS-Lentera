@extends('layouts.app')
@section('title','Instrumen L1 — LENTERA')
@section('content')
<div class="page-heading"><div><p class="eyebrow">PENGELOLA BI · EVALUASI</p><h1>Instrumen L1.</h1><p>Kelola lima butir penilaian dengan skala 1–6. Draf belum dapat dipilih pada sesi.</p></div><a class="btn primary" href="{{ route('instruments.create') }}">Buat instrumen L1</a></div>
<section class="panel server-list server-context">
@forelse($instruments as $instrument)
<article><h2>{{ $instrument->set?->name ?? $instrument->code }}</h2><p>Versi {{ $instrument->version }} · {{ $instrument->published_at ? 'Terbit · terkunci' : 'Draf' }}</p>
@unless($instrument->set)<p>Referensi bersama dari instalasi sebelumnya · hanya baca. Instrumen L1-DEMO merupakan contoh, bukan redaksi resmi.</p>@endunless
<a class="btn secondary" href="{{ route('instruments.show',$instrument) }}" aria-label="Buka instrumen: {{ $instrument->set?->name ?? $instrument->code }} versi {{ $instrument->version }}">Lihat instrumen</a></article>
@empty<p>Belum ada instrumen. Buat draf pertama, periksa kelima butir, lalu terbitkan agar dapat digunakan pada sesi.</p>@endforelse
{{ $instruments->links() }}
</section>
@endsection

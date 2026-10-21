@extends('layouts.app')
@section('title', 'Kegiatan — LENTERA')
@section('content')
<a class="back-link" href="{{ route('dashboard') }}">&larr; Kembali ke beranda</a>
<div class="page-heading"><div><p class="eyebrow">KEGIATAN</p><h1>{{ $activity->title }}</h1><p>{{ $activity->description }}</p></div></div>
<section class="panel" aria-labelledby="sessions-title"><h2 id="sessions-title">Sesi pembelajaran</h2>
<div class="session-list">
@forelse($activity->sessions as $session)
    <article class="session-item">
        <div>
            <h3>{{ $session->title }}</h3>
            <p>{{ $session->speaker_name }} · {{ $session->starts_at->format('d/m/Y H:i') }} ({{ config('app.timezone') }})</p>
            <p>Evaluasi L1: {{ $session->l1_opens_at->format('d/m/Y H:i') }} sampai {{ $session->l1_closes_at->format('d/m/Y H:i') }}.</p>
        </div>
        @if(auth()->user()->role === 'participant')<a class="btn primary" href="{{ route('l1.edit', $session) }}">Buka evaluasi L1</a>@endif
    </article>
@empty <p>Belum ada sesi.</p>
@endforelse
</div></section>
@endsection
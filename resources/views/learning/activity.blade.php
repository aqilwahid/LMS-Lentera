@extends('layouts.app')
@section('title', 'Kegiatan — LENTERA')
@section('content')
<a href="{{ route('dashboard') }}">Kembali ke beranda</a>
<div class="page-heading"><div><h1>{{ $activity->title }}</h1><p>{{ $activity->description }}</p></div></div>
<section class="panel"><h2>Sesi pembelajaran</h2><div class="server-list">
@forelse($activity->sessions as $session)
<article><h3>{{ $session->title }}</h3><p>{{ $session->speaker_name }} · {{ $session->starts_at->format('d/m/Y H:i') }} ({{ config('app.timezone') }})</p>
<p>Evaluasi L1: {{ $session->l1_opens_at->format('d/m/Y H:i') }} sampai {{ $session->l1_closes_at->format('d/m/Y H:i') }}.</p>
@if(auth()->user()->role === 'participant')<a class="btn primary" href="{{ route('l1.edit', $session) }}">Buka evaluasi L1</a>@endif
</article>
@empty <p>Belum ada sesi.</p>
@endforelse
</div></section>
@endsection

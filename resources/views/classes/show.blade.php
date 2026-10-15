@extends('layouts.app')
@section('title','Kelas saya — LENTERA')
@section('content')
<div class="server-form"><div class="page-heading"><div><p class="eyebrow">KELAS SAYA</p><h1>{{ $session->title }}</h1></div></div>
<section class="panel"><h2>{{ $session->activity->title }}</h2><p>Selamat datang, {{ $enrollment->full_name }}.</p><p>{{ $enrollment->institution_name }}</p>
<p>Narasumber: {{ $session->speaker_name }}</p><p>Jadwal: {{ $session->starts_at->format('d/m/Y H:i') }} ({{ config('app.timezone') }}).</p>
<a class="btn primary" href="{{ route('class.l1.edit',$session->join_token) }}">Buka evaluasi L1</a>
</section><section class="panel"><h2>Materi dan evaluasi L2</h2><p>Materi kelas, pretest, dan post-test belum dihubungkan pada tahap backend ini.</p></section></div>
@endsection

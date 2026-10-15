@extends('layouts.app')
@section('title','Kelas saya — LENTERA')
@section('content')
<div class="server-form"><div class="page-heading"><div><p class="eyebrow">KELAS SAYA</p><h1>{{ $session->title }}</h1></div></div>
<section class="panel"><h2>{{ $session->activity->title }}</h2><p>Selamat datang, {{ $enrollment->full_name }}.</p><p>{{ $enrollment->institution_name }}</p>
<p>Narasumber: {{ $session->speaker_name }}</p><p>Jadwal: {{ $session->starts_at->format('d/m/Y H:i') }} ({{ config('app.timezone') }}).</p>
<a class="btn primary" href="{{ route('class.l1.edit',$session->join_token) }}">Buka evaluasi L1</a>
</section>
<section class="panel server-list server-context"><h2>Materi kelas</h2>
@forelse($materials as $material)
<article><h3>{{ $material->title }}</h3><p>{{ $material->description }}</p><p>PDF · versi {{ $material->currentVersion?->version_number }}</p><a class="btn primary" href="{{ route('class.material.preview',[$session->join_token,$material]) }}">Baca materi: {{ $material->title }}</a> <a href="{{ route('class.material.download',[$session->join_token,$material,'version'=>$material->current_version_id]) }}">Unduh PDF: {{ $material->title }}</a></article>
@empty <p>Belum ada materi yang diterbitkan untuk sesi ini.</p>@endforelse{{ $materials->links() }}
</section><section class="panel"><h2>Evaluasi L2</h2><p>Pretest dan post-test belum dihubungkan pada tahap backend ini.</p></section></div>
@endsection

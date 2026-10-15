@extends('layouts.app')
@section('title', 'Ringkasan — LENTERA')
@section('content')
<div class="page-heading"><div><p class="eyebrow">{{ auth()->user()->role === 'partner' ? 'MITRA · AKSES LIHAT' : 'PENGELOLA BI' }}</p><h1>Gambaran pembelajaran.</h1>
<p>{{ auth()->user()->role === 'partner' ? auth()->user()->institution?->name : 'Cakupan KPwBI akun Anda' }} · seluruh periode yang tersedia.</p></div></div>
<section class="panel"><h2>Hasil L1 tersimpan</h2><p>Setiap kelompok memisahkan sesi, versi instrumen, dan institusi saat kepesertaan.</p>
<div class="server-list">
@forelse($aggregates as $row)
<article><h3>{{ $row->title }}</h3><p>{{ $row->institution_name }} · sesi #{{ $row->learning_session_id }} · instrumen #{{ $row->l1_instrument_id }}</p>
<p><strong>{{ number_format($row->mean_score, 2, ',', '.') }} / 6</strong> · {{ $row->responses }} respons L1</p>
<p>Periode respons: {{ $row->period_start }} sampai {{ $row->period_end }} ({{ config('app.timezone') }}).</p></article>
@empty <h3>Belum ada respons L1 pada cakupan Anda.</h3><p>Hasil muncul setelah peserta mengirim evaluasi.</p>
@endforelse
</div>{{ $aggregates->links() }}
</section>
<section class="panel"><h2>Evaluasi L2</h2><p>Pretest dan post-test belum diimplementasikan pada tahap backend ini.</p></section>
@endsection

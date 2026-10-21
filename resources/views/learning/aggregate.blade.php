@extends('layouts.app')
@section('title', 'Ringkasan — LENTERA')
@section('content')
<div class="page-heading"><div><p class="eyebrow">{{ auth()->user()->role === 'partner' ? 'MITRA · AKSES LIHAT' : 'PENGELOLA BI' }}</p><h1>Gambaran pembelajaran.</h1>
<p>{{ auth()->user()->role === 'partner' ? auth()->user()->institution?->name : 'Cakupan KPwBI akun Anda' }} · {{ (request('from') || request('to')) ? 'periode '.(request('from') ?: 'awal').' sampai '.(request('to') ?: 'sekarang') : 'seluruh periode yang tersedia' }}.</p></div></div>
<form class="panel" method="get"><div class="field"><label for="from">Tanggal awal</label><input id="from" name="from" type="date" value="{{ request('from') }}"></div><div class="field"><label for="to">Tanggal akhir</label><input id="to" name="to" type="date" value="{{ request('to') }}"></div><button class="btn secondary">Terapkan periode</button> <a href="{{ route('dashboard') }}">Seluruh periode</a></form>
<section class="panel"><h2>Hasil L1 tersimpan</h2><p>Setiap kelompok memisahkan sesi, versi instrumen, dan institusi saat kepesertaan.</p><p><a class="btn secondary" href="{{ route('dashboard', request()->only('from', 'to') + ['format' => 'csv']) }}">Unduh CSV (Excel)</a></p>
<div class="server-list">
@forelse($aggregates as $row)
<article><h3>{{ $row->title }}</h3><p>{{ $row->institution_name }} · sesi #{{ $row->learning_session_id }} · instrumen #{{ $row->l1_instrument_id }}</p>
<p><strong>{{ number_format($row->mean_score, 2, ',', '.') }} / 6</strong> · {{ $row->responses }} respons L1</p>
<p>Periode respons: {{ $row->period_start }} sampai {{ $row->period_end }} ({{ config('app.timezone') }}).</p></article>
@empty <h3>Belum ada respons L1 pada cakupan Anda.</h3><p>Hasil muncul setelah peserta mengirim evaluasi.</p>
@endforelse
</div>{{ $aggregates->links() }}
</section>
@if(auth()->user()->role === 'bi_admin')
<section class="panel"><h2>Bagikan QR kelas</h2><div class="server-list">
@forelse($activities as $activity)
<article><h3>{{ $activity->title }}</h3>
@foreach($activity->sessions as $session)
@if($session->join_enabled && $session->join_token)<p><a href="{{ route('class.access',$session) }}">QR dan tautan: {{ $session->title }}</a></p>@endif
@endforeach
</article>
@empty <p>Belum ada kegiatan pada cakupan KPwBI Anda.</p>
@endforelse
</div>{{ $activities->links() }}</section>
@endif
<section class="panel"><h2>Evaluasi L2</h2><p>Lihat hasil pretest/post-test dan pasangan sah menurut periode, sesi, versi serta institusi dalam cakupan akun Anda.</p><a class="btn primary" href="{{ route('analytics.l2') }}">Buka analisis L2</a></section>
@endsection

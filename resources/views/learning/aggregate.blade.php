@extends('layouts.app')
@section('title', 'Ringkasan — LENTERA')
@section('content')
@php
    $isPartner = auth()->user()->role === 'partner';
    $filtered = request('from') || request('to');
    $csvUrl = route('dashboard', request()->only('from', 'to') + ['format' => 'csv']);
@endphp
<div class="page-heading"><div>
    <p class="eyebrow">{{ $isPartner ? 'MITRA · AKSES LIHAT' : 'PENGELOLA BI' }}</p>
    <h1>Gambaran pembelajaran.</h1>
    <p>{{ $isPartner ? auth()->user()->institution?->name : 'Cakupan KPwBI akun Anda' }} · {{ $filtered ? 'periode '.(request('from') ?: 'awal').' sampai '.(request('to') ?: 'sekarang') : 'seluruh periode yang tersedia' }}.</p>
</div></div>

<form class="panel dash-filter" method="get" action="{{ route('dashboard') }}" aria-label="Filter periode">
    <div class="field"><label for="from">Tanggal awal</label><input id="from" name="from" type="date" value="{{ request('from') }}"></div>
    <div class="field"><label for="to">Tanggal akhir</label><input id="to" name="to" type="date" value="{{ request('to') }}"></div>
    <button class="btn secondary">Terapkan periode</button>
    @if($filtered)<a class="reset" href="{{ route('dashboard') }}">Seluruh periode</a>@endif
</form>

<dl class="kpi-grid" aria-label="Ringkasan hasil L1">
    <div class="kpi primary"><dt>Rata-rata L1</dt><dd>{{ $totals->responses ? number_format($totals->mean_score, 2, ',', '.') : '—' }} <small>/ 6</small></dd></div>
    <div class="kpi"><dt>Respons L1</dt><dd>{{ number_format($totals->responses, 0, ',', '.') }}</dd></div>
    <div class="kpi"><dt>Sesi dengan respons</dt><dd>{{ number_format($totals->sessions, 0, ',', '.') }}</dd></div>
    <div class="kpi"><dt>Isian institusi</dt><dd>{{ number_format($totals->institutions, 0, ',', '.') }}</dd></div>
</dl>

<section class="panel" aria-labelledby="l1-title">
    <div class="panel-head">
        <div><h2 id="l1-title">Hasil L1 tersimpan</h2><p>Setiap kelompok memisahkan sesi, versi instrumen, dan institusi saat kepesertaan.</p></div>
        <a class="btn secondary" href="{{ $csvUrl }}">Unduh CSV (Excel)</a>
    </div>
    @if($aggregates->count())
    <div class="table-wrap" tabindex="0" role="region" aria-label="Tabel hasil L1 (dapat digulir)">
        <table class="data-table">
            <caption class="sr-only">Hasil L1 per kegiatan, sesi, instrumen, dan institusi</caption>
            <thead><tr><th scope="col">Kegiatan</th><th scope="col">Institusi</th><th scope="col">Rata-rata</th><th scope="col" class="num">Respons</th><th scope="col">Periode respons</th></tr></thead>
            <tbody>
            @foreach($aggregates as $row)
                <tr>
                    <th scope="row"><strong>{{ $row->title }}</strong><small>sesi #{{ $row->learning_session_id }} · instrumen #{{ $row->l1_instrument_id }}</small></th>
                    <td>{{ $row->institution_name }}</td>
                    <td><div class="score"><strong>{{ number_format($row->mean_score, 2, ',', '.') }} / 6</strong><div class="score-bar" aria-hidden="true"><span style="width: {{ min(100, max(0, round($row->mean_score / 6 * 100))) }}%"></span></div></div></td>
                    <td class="num">{{ $row->responses }} respons L1</td>
                    <td>{{ $row->period_start }} sampai {{ $row->period_end }}<small>{{ config('app.timezone') }}</small></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @else
    <div class="empty-box"><h3>Belum ada respons L1 pada cakupan Anda.</h3><p>Hasil muncul setelah peserta mengirim evaluasi.</p></div>
    @endif
    {{ $aggregates->links() }}
</section>

@if(! $isPartner)
<section class="panel" aria-labelledby="qr-title">
    <h2 id="qr-title">Bagikan QR kelas</h2>
    <div class="session-list">
    @forelse($activities as $activity)
        <div class="session-item">
            <div><h3>{{ $activity->title }}</h3><p>{{ $activity->sessions->count() }} sesi</p></div>
            <div class="qr-list">
            @foreach($activity->sessions as $session)
                @if($session->join_enabled && $session->join_token)<a href="{{ route('class.access',$session) }}">QR dan tautan: {{ $session->title }}</a>@endif
            @endforeach
            </div>
        </div>
    @empty <p>Belum ada kegiatan pada cakupan KPwBI Anda.</p>
    @endforelse
    </div>
    {{ $activities->links() }}
</section>
@endif

<section class="panel" aria-labelledby="l2-title">
    <div class="panel-head">
        <div><h2 id="l2-title">Evaluasi L2</h2><p>Lihat hasil pretest/post-test dan pasangan sah menurut periode, sesi, versi serta institusi dalam cakupan akun Anda.</p></div>
        <a class="btn primary" href="{{ route('analytics.l2') }}">Buka analisis L2</a>
    </div>
</section>
@endsection

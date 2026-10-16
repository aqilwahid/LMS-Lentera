@extends('layouts.app')
@section('title','Analisis L2 — LENTERA')
@section('content')
<div class="server-form server-context"><div class="page-heading"><div><p class="eyebrow">{{ auth()->user()->role === 'partner' ? 'MITRA · AGREGAT INSTITUSI SENDIRI' : 'PENGELOLA BI · CAKUPAN KPwBI' }}</p><h1>Analisis L2.</h1><p>{{ auth()->user()->role === 'partner' ? auth()->user()->institution->name : 'Hasil dalam cakupan KPwBI akun Anda' }} · {{ $filters['from'] }} sampai {{ $filters['to'] }} ({{ config('app.timezone') }}).</p></div></div>
@if(auth()->user()->role === 'partner')<p class="server-status">Hanya agregat institusi Anda dari sesi yang skornya sudah dibuka oleh pengelola. Tidak ada identitas, jawaban individu atau kunci soal.</p>@endif
<form class="panel" method="get" action="{{ route('analytics.l2') }}"><h2>Pilih periode dan cakupan</h2>
<div class="field"><label for="from">Tanggal awal</label><input id="from" name="from" type="date" value="{{ $filters['from'] }}" required></div>
<div class="field"><label for="to">Tanggal akhir</label><input id="to" name="to" type="date" value="{{ $filters['to'] }}" required></div>
@foreach(['activity_id'=>['Kegiatan',$activities],'session_id'=>['Sesi',$sessions],'instrument_id'=>['Versi instrumen L2',$instruments]] as $field=>$option)
<div class="field"><label for="{{ $field }}">{{ $option[0] }}</label><select id="{{ $field }}" name="{{ $field }}"><option value="">Semua yang tersedia</option>
@foreach($option[1] as $item)<option value="{{ $item->id }}" @selected((string)($filters[$field] ?? '') === (string)$item->id)>{{ $item->title ?? $item->name }}{{ isset($item->version) ? ' · versi '.$item->version : '' }}</option>@endforeach
</select></div>
@endforeach
@if(auth()->user()->role === 'bi_admin')
<div class="field"><label for="institution_id">Institusi</label><select id="institution_id" name="institution_id"><option value="">Semua institusi dalam cakupan</option><option value="0" @selected(isset($filters['institution_id']) && (string)$filters['institution_id'] === '0')>Belum dikenali atau dipetakan</option>
@foreach($institutions as $institution)<option value="{{ $institution->id }}" @selected((string)($filters['institution_id'] ?? '') === (string)$institution->id)>{{ $institution->name }}{{ $institution->is_active ? '' : ' (nonaktif)' }}</option>@endforeach
</select></div>
@endif
<p>Pilihan kegiatan/sesi/instrumen berasal dari respons final yang tersedia pada cakupan akun, sebelum filter tanggal. Periode berlaku pada tanggal pengiriman tiap tahap. Pasangan mengikuti tanggal pengiriman post-test; pretest pasangannya dapat berasal dari sebelum periode.</p><button class="btn primary" type="submit">Terapkan filter L2</button> <a class="btn secondary" href="{{ route('analytics.l2') }}">Kembali ke tahun berjalan</a>
</form>
<section class="panel"><h2>Hasil pada periode terpilih</h2><p>Pretest final pada periode: <strong>{{ $summary->pre_count }}</strong> · Post-test final pada periode: <strong>{{ $summary->post_count }}</strong>.</p><p>Pasangan sah dengan post-test pada periode: <strong>{{ $summary->pair_count }}</strong>.</p>
@if($summary->pair_count > 0)
<p>Rata-rata pasangan: pretest <strong>{{ number_format($summary->pre_mean,2,',','.') }} / 100</strong> · post-test <strong>{{ number_format($summary->post_mean,2,',','.') }} / 100</strong>.</p><p>Selisih rata-rata post minus pre: <strong>{{ number_format($summary->delta,2,',','.') }} poin persentase</strong>.</p><p>Post-test pasangan: {{ $summary->pair_period_start }} sampai {{ $summary->pair_period_end }} ({{ config('app.timezone') }}).</p>
@else<p>Rata-rata dan selisih belum tersedia karena tidak ada pasangan sah. Jawaban tanpa pasangan tidak dianggap nol.</p>@endif
<p>Rata-rata keseluruhan dihitung dari tiap pasangan, sehingga kelompok yang lebih besar mendapat bobot sesuai jumlah pasangannya. Hasil lintas versi hanya merupakan ringkasan deskriptif; kesetaraan instrumen belum dibuktikan.</p></section>
<section class="panel server-list"><h2>Rincian menurut sesi, versi dan institusi</h2>
@forelse($rows as $row)
<article><h3>{{ $row->activity_title }} · {{ $row->session_title }}</h3><p>Isian institusi saat bergabung: <strong>{{ $row->institution_source_name }}</strong>.</p><p>Master: {{ $row->institution_master_name ?? 'Belum dikenali atau dipetakan' }} · {{ $row->instrument_name }} · versi {{ $row->version }}.</p><p>Pretest final: {{ $row->pre_count }} · Post-test final: {{ $row->post_count }} · Pasangan sah: {{ $row->pair_count }}.</p>
@if($row->pair_count > 0)<p>Rata-rata pasangan: pretest {{ number_format($row->pre_mean,2,',','.') }} / 100 · post-test {{ number_format($row->post_mean,2,',','.') }} / 100.</p><p>Selisih post minus pre: {{ number_format($row->delta,2,',','.') }} poin persentase.</p>@else<p>Rata-rata pasangan belum tersedia.</p>@endif
</article>
@empty<p>Belum ada hasil final L2 yang sesuai dengan periode dan cakupan ini.</p>@endforelse
{{ $rows->links() }}
</section><section class="panel"><h2>Cara membaca hasil</h2><p>Pasangan sah berasal dari kepesertaan, sesi, versi dan jumlah soal yang sama. Pretest harus sudah dikirim final sebelum post-test dimulai. Tes draf, pasangan tidak lengkap atau urutan terbalik tidak masuk rata-rata pasangan.</p><p>Riwayat final tetap dihitung bila kepesertaan kemudian dinonaktifkan. Isian institusi asli dipertahankan; pemetaan master yang diaudit menentukan cakupan mitra. Selisih bukan bukti dampak kausal. L1 tidak menjadi bagian skor L2.</p></section></div>
@endsection

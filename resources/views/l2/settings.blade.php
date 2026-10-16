@extends('layouts.app')
@section('title','Pengaturan L2 — LENTERA')
@section('content')
<div class="server-form server-context"><a href="{{ route('management.sessions.edit',$session) }}">Kembali ke pengaturan sesi</a><div class="page-heading"><div><h1>Pengaturan L2.</h1><p>{{ $session->title }} · {{ config('app.timezone') }}</p></div></div>
@if($locked)<p class="server-status">Peserta sudah memulai tes. Versi soal dan prasyarat pretest terkunci. Perubahan jadwal, status aktif dan publikasi skor tetap dapat disimpan.</p>@endif
<form class="panel" method="post" action="{{ route('l2.settings.update',$session) }}">@csrf @method('put')<input type="hidden" id="revision" name="revision" value="{{ old('revision',$assessment->revision ?? 0) }}">
<div class="field"><label for="l2_instrument_id">Versi instrumen L2</label>
@if($locked)
<input type="hidden" name="l2_instrument_id" value="{{ $assessment->l2_instrument_id }}"><input id="l2_instrument_id" value="{{ $assessment->instrument->name }} · versi {{ $assessment->instrument->version }}" readonly>
@else
<select id="l2_instrument_id" name="l2_instrument_id" required><option value="">Pilih versi terbit</option>
@foreach($instruments as $instrument)<option value="{{ $instrument->id }}" @selected((string)old('l2_instrument_id',$assessment->l2_instrument_id) === (string)$instrument->id)>{{ $instrument->name }} · versi {{ $instrument->version }}</option>@endforeach
</select>
@endif
<p>Pretest dan post-test memakai versi yang sama pada sesi ini. <a href="{{ route('l2.instruments.index') }}">Kelola instrumen L2</a>.</p></div>
@foreach(['pre_opens_at'=>'Pretest dibuka','pre_closes_at'=>'Pretest ditutup','post_opens_at'=>'Post-test dibuka','post_closes_at'=>'Post-test ditutup'] as $field=>$label)
<div class="field"><label for="{{ $field }}">{{ $label }}</label><input type="datetime-local" id="{{ $field }}" name="{{ $field }}" value="{{ old($field,$assessment->$field?->format('Y-m-d\TH:i')) }}" required></div>
@endforeach
@foreach(['enabled'=>'Status akses L2','require_pretest'=>'Wajib pretest final sebelum post-test','results_visible'=>'Buka skor peserta dan agregat mitra'] as $field=>$label)
<div class="field"><label for="{{ $field }}">{{ $label }}</label>
@if($locked && $field === 'require_pretest')
<input type="hidden" name="{{ $field }}" value="{{ (int)$assessment->$field }}"><input id="{{ $field }}" value="{{ $assessment->$field ? 'Ya' : 'Tidak' }}" readonly>
@else
<select id="{{ $field }}" name="{{ $field }}"><option value="0" @selected(!old($field,$assessment->$field))>{{ $field === 'enabled' ? 'Nonaktif' : 'Tidak' }}</option><option value="1" @selected(old($field,$assessment->$field))>{{ $field === 'enabled' ? 'Aktif' : 'Ya' }}</option></select>
@endif
@if($field === 'results_visible')<p>Pilihan Ya membuka skor untuk pemilik jawaban dan agregat L2 untuk mitra institusinya. Pilihan Tidak menutup keduanya. Identitas peserta, jawaban individu dan kunci tidak dibagikan kepada mitra.</p>@endif
</div>
@endforeach
<p>Skor awalnya tidak ditampilkan. Aktifkan akses setelah jadwal dan instrumen benar. Menonaktifkan L2 menghentikan mulai/simpan/kirim, tetapi mempertahankan draf dan hasil. Publikasi skor tidak membuka kunci jawaban.</p>
<button class="btn primary" type="submit" @disabled($instruments->isEmpty() && !$locked)>Simpan pengaturan L2</button></form>
<section class="panel"><h2>Ringkasan L2 sesi ini</h2><p>Pretest final: {{ $counts['pre'] ?? 0 }} respons · Post-test final: {{ $counts['post'] ?? 0 }} respons.</p><p>Pasangan lengkap: {{ $pairs->total }} kepesertaan dengan versi dan jumlah soal yang sama, serta pretest final sebelum post-test dimulai.</p><p><a href="{{ route('analytics.l2') }}">Analisis L2 dengan filter periode dan institusi</a></p>
@if($pairs->total > 0)<p>Rata-rata pasangan: pretest {{ number_format($pairs->pre_mean,2,',','.') }} / 100 · post-test {{ number_format($pairs->post_mean,2,',','.') }} / 100.</p><p>Selisih post minus pre: {{ number_format($pairs->delta,2,',','.') }} poin persentase.</p>@else<p>Rata-rata berpasangan belum tersedia. Respons tanpa pasangan tidak dihitung sebagai nol.</p>@endif
<p>Ringkasan memakai hasil final yang tersimpan, termasuk kepesertaan yang kemudian dinonaktifkan. Selisih bukan bukti dampak kausal; peserta menjawab versi soal yang sama. L1 tidak menjadi komponen skor.</p></section></div>
@endsection

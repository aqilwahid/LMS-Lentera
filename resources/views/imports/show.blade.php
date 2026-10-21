@extends('layouts.app')
@section('title','Pratinjau impor — LENTERA')
@section('content')
@php($labels=['ok'=>'Siap diimpor','imported'=>'Sudah diimpor','unmapped'=>'Institusi belum dipetakan','duplicate'=>'Duplikat','error'=>'Tidak valid','summary'=>'Baris ringkasan/kosong'])
<div class="server-form"><a href="{{ route('imports.index') }}">Kembali ke impor</a>
<div class="page-heading"><div><h1>{{ $batch->file_name }}</h1><p>Sesi: {{ $batch->session->title }} ({{ $batch->session->activity->title }})</p></div></div>
<section class="panel"><h2>Ringkasan</h2>@foreach($labels as $k=>$label)<p>{{ $label }}: <strong>{{ $counts[$k] ?? 0 }}</strong></p>@endforeach
@if(($counts['ok'] ?? 0) > 0)<form method="post" action="{{ route('imports.apply',$batch->id) }}">@csrf<button class="btn primary">Terapkan {{ $counts['ok'] }} baris siap</button></form>@endif</section>
@if($unmapped->isNotEmpty())<section class="panel"><h2>Petakan institusi</h2><p>Nama tidak dicocokkan otomatis. Pilih institusi master untuk setiap isian.</p>
@foreach($unmapped as $key=>$u)<form method="post" action="{{ route('imports.alias',$batch->id) }}">@csrf<input type="hidden" name="institution_key" value="{{ $key }}">
<div class="field"><label for="inst-{{ $loop->index }}">“{{ $u['name'] }}” ({{ $u['n'] }} baris)</label><select id="inst-{{ $loop->index }}" name="institution_id" required>@foreach($institutions as $i)<option value="{{ $i->id }}">{{ $i->name }}</option>@endforeach</select></div><button class="btn secondary">Simpan pemetaan</button></form>@endforeach</section>@endif
<section class="panel server-list"><h2>Baris ditahan</h2>@forelse($held as $r)<article><p>Baris {{ $r->line }} · {{ $labels[$r->status] ?? $r->status }} · {{ $r->error }}</p></article>@empty<p>Tidak ada.</p>@endforelse{{ $held->links() }}</section></div>
@endsection

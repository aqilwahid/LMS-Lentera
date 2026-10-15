@extends('layouts.app')
@section('title','Master — LENTERA')
@section('content')
<div class="page-heading"><div><p class="eyebrow">PENGELOLA BI</p><h1>Master {{ $kind }}.</h1><p>Data dalam cakupan KPwBI akun Anda.</p></div><a class="btn primary" href="{{ route('masters.create',$kind) }}">Tambah {{ $kind }}</a></div>
<form class="panel" method="get"><div class="field"><label for="q">Cari nama</label><input id="q" name="q" value="{{ request('q') }}" maxlength="100"></div><button class="btn secondary">Cari</button></form>
<section class="panel server-list server-context">@forelse($records as $record)
<article><h2>{{ $record->name }}</h2><p>{{ $record->is_active ? 'Aktif' : 'Nonaktif' }}@if($kind==='narasumber') · {{ $record->type==='internal' ? 'Internal' : 'Eksternal' }} · {{ $record->organization }}@endif</p><a href="{{ route('masters.edit',[$kind,$record->id]) }}">Ubah: {{ $record->name }}</a></article>
@empty <p>Belum ada master yang sesuai.</p>@endforelse{{ $records->links() }}</section>
@endsection

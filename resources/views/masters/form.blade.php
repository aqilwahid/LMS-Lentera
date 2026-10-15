@extends('layouts.app')
@section('title','Pengaturan master — LENTERA')
@section('content')
<div class="server-form"><a href="{{ route('masters.index',$kind) }}">Kembali ke master {{ $kind }}</a><div class="page-heading"><div><h1>{{ $record->exists ? 'Ubah' : 'Tambah' }} {{ $kind }}.</h1></div></div>
<form class="panel" method="post" action="{{ $record->exists ? route('masters.update',[$kind,$record->id]) : route('masters.store',$kind) }}">@csrf
@if($record->exists) @method('put')<input id="revision" name="revision" type="hidden" value="{{ old('revision',$record->revision) }}">@endif
<div class="field"><label for="name">Nama {{ $kind }}</label><input id="name" name="name" value="{{ old('name',$record->name) }}" maxlength="255" required>@error('name')<p role="alert">{{ $message }}</p>@enderror</div>
@if($kind==='narasumber')
<div class="field"><label for="organization">Asal instansi (opsional)</label><input id="organization" name="organization" value="{{ old('organization',$record->organization) }}" maxlength="255"></div>
<div class="field"><label for="type">Jenis narasumber</label><select id="type" name="type" required>@foreach(['internal'=>'Internal','external'=>'Eksternal'] as $value=>$label)<option value="{{ $value }}" @selected(old('type',$record->type ?? 'external')===$value)>{{ $label }}</option>@endforeach</select></div>
@endif
<div class="field"><label for="is_active">Status master</label><select id="is_active" name="is_active"><option value="1" @selected((string)old('is_active',$record->exists ? (int)$record->is_active : 1)==='1')>Aktif</option><option value="0" @selected((string)old('is_active',$record->exists ? (int)$record->is_active : 1)==='0')>Nonaktif</option></select></div>
<p>Master nonaktif tidak ditawarkan untuk pendaftaran atau pemilihan baru. Penggantian nama tidak menimpa isian institusi peserta atau nama narasumber pada sesi lama.</p><button class="btn primary">Simpan master</button></form></div>
@endsection

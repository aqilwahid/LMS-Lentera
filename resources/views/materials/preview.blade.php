@extends('layouts.app')
@section('title','Baca materi — LENTERA')
@section('content')
<div class="server-context"><a href="{{ route('class.show',$token) }}">Kembali ke kelas</a><div class="page-heading"><div><h1>{{ $material->title }}</h1><p>{{ $material->description }}</p><p>PDF · versi {{ $version->version_number }} · {{ number_format($version->size_bytes/1024,1,',','.') }} KB</p></div></div>
<section class="panel"><p><a class="btn primary" href="{{ route('class.material.download',[$token,$material,'version'=>$version->id]) }}">Unduh PDF</a> <a href="{{ route('class.material.file',[$token,$material,'version'=>$version->id]) }}" target="_blank" rel="noopener">Buka PDF di tab baru</a></p>
<p>Jika pratinjau tidak tampil, gunakan Buka PDF di tab baru atau Unduh PDF.</p>
<iframe src="{{ route('class.material.file',[$token,$material,'version'=>$version->id]) }}" title="Pratinjau PDF: {{ $material->title }}" style="width:100%;height:70vh;min-height:360px;border:1px solid #E2E8F0;border-radius:12px" loading="lazy"></iframe>
</section></div>
@endsection

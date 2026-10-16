@extends('layouts.app')
@section('title','Versi instrumen L1 — LENTERA')
@section('content')
<div class="server-form server-context"><a href="{{ route('instruments.index') }}">Kembali ke instrumen</a>
<div class="page-heading"><div><p class="eyebrow">INSTRUMEN L1</p><h1>{{ $instrument->set?->name ?? $instrument->code }}</h1><p>Versi {{ $instrument->version }} · {{ $instrument->published_at ? 'Terbit · terkunci' : 'Draf' }}</p><p>Kode: {{ $instrument->code }} · Skala 1–6</p></div></div>
@if($instrument->published_at)
<section class="panel"><h2>Lima butir penilaian</h2><p>Diterbitkan {{ $instrument->published_at->format('d/m/Y H:i') }} ({{ config('app.timezone') }}). Isi versi ini terkunci; sesi lama dan respons peserta tetap memakai versi yang sudah ditetapkan.</p>
@foreach($instrument->questions as $question)<h3>Butir {{ $loop->iteration }}</h3><p>{{ $question }}</p>@endforeach
</section>
@if($instrument->set)
<section class="panel"><h2>Perlu perubahan?</h2><p>Buat draf versi berikutnya dengan menyalin kelima butir versi ini. Perubahan versi baru tidak otomatis mengganti instrumen pada sesi.</p>
<form method="post" action="{{ route('instruments.version',$instrument) }}">@csrf<input type="hidden" id="creation_nonce" name="creation_nonce" value="{{ $nonce }}"><button class="btn primary" type="submit">Buat draf versi baru</button></form></section>
@else
<section class="panel"><h2>Referensi bersama · hanya baca</h2><p>Versi dari instalasi sebelumnya tidak dapat diubah melalui menu ini. L1-DEMO adalah data contoh. Untuk menyesuaikan butir, buat instrumen milik KPwBI Anda dari salinan ini.</p><a class="btn primary" href="{{ route('instruments.create',['template'=>$instrument->id]) }}">Salin sebagai instrumen baru</a></section>
@endif
@else
<form class="panel" method="post" action="{{ route('instruments.update',$instrument) }}">@csrf @method('put')
<input type="hidden" id="revision" name="revision" value="{{ old('revision',$instrument->revision) }}">
<h2>Ubah draf</h2><p>Simpan perubahan sebelum menerbitkan. Tombol terbit di bawah hanya menerbitkan isi yang sudah tersimpan.</p>
@include('instruments.questions',['questions'=>$instrument->questions])
<button class="btn primary" type="submit">Simpan perubahan draf</button></form>
<section class="panel"><h2>Terbitkan versi ini</h2><p>Periksa pratinjau isi tersimpan berikut. Setelah diterbitkan, versi ini tidak dapat diubah atau dihapus. Penerbitan tidak menyatakan redaksi telah disetujui Bank Indonesia.</p>
<details open><summary>Lihat lima butir yang akan diterbitkan</summary>@foreach($instrument->questions as $question)<h3>Butir {{ $loop->iteration }}</h3><p>{{ $question }}</p>@endforeach</details>
<form method="post" action="{{ route('instruments.publish',$instrument) }}">@csrf<input type="hidden" name="revision" value="{{ $instrument->revision }}"><button class="btn secondary" type="submit">Terbitkan versi {{ $instrument->version }}</button></form></section>
@endif
<section class="panel"><h2>Riwayat versi</h2><ul>
@foreach($versions as $version)
<li><a href="{{ route('instruments.show',$version) }}">Versi {{ $version->version }} · {{ $version->published_at ? 'Terbit' : 'Draf' }}</a>
@if($version->id === $instrument->id) · sedang dibuka @endif
</li>
@endforeach
</ul></section>
</div>
@endsection

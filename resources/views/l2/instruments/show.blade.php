@extends('layouts.app')
@section('title','Versi instrumen L2 — LENTERA')
@section('content')
<div class="server-form server-context"><a href="{{ route('l2.instruments.index') }}">Kembali ke instrumen L2</a><div class="page-heading"><div><h1>{{ $instrument->name }}</h1><p>Versi {{ $instrument->version }} · {{ $instrument->published_at ? 'Terbit · terkunci' : 'Draf' }}</p><p>Kode {{ $instrument->code }} · {{ count($instrument->questions) }} soal · Bobot setara</p></div></div>
@unless($instrument->published_at)
<form class="panel" method="post" action="{{ route('l2.instruments.update',$instrument) }}">@csrf @method('put')<input type="hidden" id="revision" name="revision" value="{{ old('revision',$instrument->revision) }}"><h2>Ubah draf soal dan kunci</h2><div class="field"><label for="instructions">Petunjuk tes (opsional)</label><textarea id="instructions" name="instructions" maxlength="5000">{{ old('instructions',$instrument->instructions) }}</textarea></div>
@include('l2.instruments.questions',['questions'=>$instrument->questions])
<button class="btn primary" type="submit">Simpan perubahan L2</button></form>
@endunless
<section class="panel"><h2>Pratinjau soal tersimpan</h2><p>{{ $instrument->instructions }}</p><p>Hanya pengelola KPwBI Anda dapat melihat kunci. Perubahan yang belum disimpan tidak masuk pratinjau ini.</p>
@foreach($instrument->questions as $question)
<h3>Soal {{ $loop->iteration }}</h3><p>{{ $question['text'] }}</p><ol type="A">@foreach($question['options'] as $option)<li>{{ $option }}</li>@endforeach</ol><p><strong>Kunci: {{ chr(65 + $question['correct']) }}</strong></p>
@endforeach
@if($instrument->published_at)
<form method="post" action="{{ route('l2.instruments.version',$instrument) }}">@csrf<input type="hidden" name="creation_nonce" value="{{ $nonce }}"><p>Versi baru tidak mengganti penetapan pada sesi lama.</p><button class="btn primary" type="submit">Buat draf versi L2 baru</button></form>
@else
<form method="post" action="{{ route('l2.instruments.publish',$instrument) }}">@csrf<input type="hidden" name="revision" value="{{ $instrument->revision }}"><p>Setelah terbit, soal dan kunci terkunci. Periksa redaksi dan kunci sebelum menekan tombol.</p><button class="btn secondary" type="submit">Terbitkan L2 versi {{ $instrument->version }}</button></form>
@endif
</section><section class="panel"><h2>Riwayat versi L2</h2><ul>
@foreach($versions as $version)
<li><a href="{{ route('l2.instruments.show',$version) }}">Versi {{ $version->version }} · {{ $version->published_at ? 'Terbit' : 'Draf' }}</a></li>
@endforeach
</ul></section></div>
@endsection

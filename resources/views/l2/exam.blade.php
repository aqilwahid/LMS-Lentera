@extends('layouts.app')
@section('title',($stage === 'pre' ? 'Pretest' : 'Post-test').' — LENTERA')
@section('content')
<div class="server-form server-context"><a href="{{ route('class.show',$session->join_token) }}">Kembali ke kelas</a><div class="page-heading"><div><p class="eyebrow">EVALUASI L2</p><h1>{{ $stage === 'pre' ? 'Pretest.' : 'Post-test.' }}</h1><p>{{ $session->title }}</p></div></div>
<section class="panel"><p>Jadwal: {{ $assessment->{$stage.'_opens_at'}->format('d/m/Y H:i') }} sampai {{ $assessment->{$stage.'_closes_at'}->format('d/m/Y H:i') }} ({{ config('app.timezone') }}).</p><p>Satu pengiriman final per tahap. Tidak ada timer atau ambang lulus. Jawaban final tidak dapat diubah. Draf tersimpan dapat dibuka kembali dari browser yang sama.</p></section>
@if($state && $state['submitted_at'])
<section class="panel"><h2>{{ $stage === 'pre' ? 'Pretest sudah dikirim.' : 'Post-test sudah dikirim.' }}</h2><p>Tersimpan pada {{ $state['submitted_at']->format('d/m/Y H:i:s') }} ({{ config('app.timezone') }}).</p>
<p>Instrumen {{ $state['code'] }} · versi {{ $state['version'] }}.</p>
@if($result)
<p>Skor Anda: {{ number_format($result['score_percent'],2,',','.') }} / 100.</p><p>Jawaban benar: {{ $result['correct_count'] }} dari {{ $result['question_count'] }} soal.</p>
@if($delta !== null)<p>Selisih post-test minus pretest Anda: {{ number_format($delta,2,',','.') }} poin persentase.</p>@else<p>Selisih berpasangan tersedia setelah kedua tes final, dengan versi dan jumlah soal sama serta pretest final sebelum post-test dimulai.</p>@endif
@else<p>Skor belum dibuka oleh pengelola. Jawaban final sudah tersimpan.</p>@endif
</section>
@elseif(!$state)
<section class="panel"><h2>Sebelum mulai</h2><p>Soal dibuka setelah Anda menekan Mulai. Selama mengerjakan, tekan Simpan draf sebelum meninggalkan halaman. Isian yang belum disimpan tidak otomatis tersimpan.</p>
@if($canStart)
<form method="post" action="{{ route('class.l2.mutate',[$session->join_token,$stage,'start']) }}">@csrf<button class="btn primary" type="submit">{{ $stage === 'pre' ? 'Mulai pretest' : 'Mulai post-test' }}</button></form>
@else<p>Tes belum dibuka, sudah ditutup, dinonaktifkan, atau prasyarat pretest belum terpenuhi. Periksa jadwal atau hubungi panitia.</p>@endif
</section>
@else
<section class="panel"><p>Instrumen {{ $state['code'] }} · versi {{ $state['version'] }}.</p><p>{{ $state['instructions'] }}</p><p>Jawaban pada draf tersimpan: {{ count($state['answers']) }} / {{ count($questions) }}. Draf bukan jawaban final.</p></section>
@unless($assessment->isOpen($stage))<p class="server-status">Tes sedang ditutup atau dinonaktifkan. Draf di bawah tetap tersimpan; perubahan tidak dapat dikirim saat ini.</p>@endunless
<form class="panel" method="post" action="{{ route('class.l2.mutate',[$session->join_token,$stage,'save']) }}">@csrf<input type="hidden" id="revision" name="revision" value="{{ old('revision',$state['revision']) }}"><div id="answers">
@foreach($questions as $index=>$question)
<fieldset class="rating-question" id="answers-{{ $index }}"><legend>{{ $index + 1 }}. {{ $question['text'] }}</legend><div class="server-list">
@foreach($question['options'] as $option=>$text)
<label class="rating-option" for="l2-{{ $index }}-{{ $option }}"><input id="l2-{{ $index }}-{{ $option }}" type="radio" name="answers[{{ $index }}]" value="{{ $option }}" @checked((string)old('answers.'.$index,$state['answers'][$index] ?? '') === (string)$option) @disabled(!$assessment->isOpen($stage))><span>{{ chr(65+$option) }}. {{ $text }}</span></label>
@endforeach
</div></fieldset>
@endforeach
</div><p>Tekan Simpan draf untuk menyimpan sementara. Setelah seluruh soal terjawab dan diperiksa, tekan Kirim final. Tunggu konfirmasi server sebelum menutup halaman.</p><button class="btn secondary" type="submit" @disabled(!$assessment->isOpen($stage))>Simpan draf</button> <button class="btn primary" type="submit" formaction="{{ route('class.l2.mutate',[$session->join_token,$stage,'submit']) }}" @disabled(!$assessment->isOpen($stage))>Kirim final</button></form>
@endif
</div>
@endsection

@extends('layouts.app')
@section('title', 'Evaluasi L1 — LENTERA')
@section('content')
<div class="server-form">
<a href="{{ $returnUrl ?? route('activities.show', $session->activity) }}">Kembali ke kegiatan</a>
<div class="page-heading"><div><p class="eyebrow">UMPAN BALIK PEMBELAJARAN</p><h1>Evaluasi L1.</h1></div></div>
<section class="panel server-context">
    <h2>{{ $session->activity->title }}</h2><p>Sesi: {{ $session->title }}</p>
    <p>Narasumber: {{ $response?->speaker_name ?? $session->speaker_name }}</p>
    <p>Institusi saat mengikuti kegiatan: {{ $enrollment->institution_name }}</p>
    <p>Instrumen: {{ $response ? $response->instrument_snapshot['code'] : $session->instrument->code }} · versi {{ $response ? $response->instrument_snapshot['version'] : $session->instrument->version }}</p>
</section>
@if($response)
    <section class="panel"><h2>Evaluasi sudah dikirim.</h2>
    <p>Tersimpan pada {{ $response->submitted_at->format('d/m/Y H:i:s') }} ({{ config('app.timezone') }}).</p>
    <p>Rata-rata penilaian Anda: {{ number_format($response->score_sum / 5, 2, ',', '.') }} / 6.</p>
    <ol>@foreach($response->instrument_snapshot['questions'] as $i => $question)<li>{{ $question }} <strong>{{ $response->answers[$i] }} / 6</strong></li>@endforeach</ol>
    @if($response->feedback)<p>Masukan: {{ $response->feedback }}</p>@endif
    </section>
@elseif(!$session->instrument->published_at)
    <section class="panel"><h2>Instrumen belum diterbitkan.</h2><p>Hubungi pengelola kegiatan.</p></section>
@elseif(!now()->betweenIncluded($session->l1_opens_at, $session->l1_closes_at))
    <section class="panel"><h2>Evaluasi belum dibuka atau sudah ditutup.</h2><p>Jadwal: {{ $session->l1_opens_at }} sampai {{ $session->l1_closes_at }}.</p></section>
@else
<form class="panel" method="post" action="{{ $submitUrl ?? route('l1.store', $session) }}" x-data="{ sending: false, answers: {{ Illuminate\Support\Js::from(array_values(old('answers', []))) }} }" @submit="sending = true">
    @csrf
    <input type="hidden" id="instrument_id" name="instrument_id" value="{{ $session->l1_instrument_id }}">
    <h2>Bagaimana pengalaman belajar Anda?</h2>
    <p>Jawab seluruh lima pertanyaan dengan skala angka 1–6. Evaluasi ini merupakan umpan balik, bukan nilai tes L2.</p>
    <p x-cloak>Pertanyaan terisi: <span x-text="answers.filter(v => v !== null && v !== '').length">0</span> / 5</p>
    <div id="answers">
    @foreach($session->instrument->questions as $i => $question)
    <fieldset class="rating-question" id="answers-{{ $i }}" @error('answers.'.$i) aria-describedby="answer-error-{{ $i }}" @enderror>
        <legend>{{ $i + 1 }}. {{ $question }}</legend>
        <div class="rating-options">
        @foreach(range(1, 6) as $value)
            <label class="rating-option" for="q{{ $i }}-{{ $value }}"><input id="q{{ $i }}-{{ $value }}" type="radio" name="answers[{{ $i }}]" value="{{ $value }}" x-model="answers[{{ $i }}]" @checked((string)old('answers.'.$i) === (string)$value) required><span>{{ $value }}</span></label>
        @endforeach
        </div>
        @error('answers.'.$i)<p id="answer-error-{{ $i }}" role="alert">{{ $message }}</p>@enderror
    </fieldset>
    @endforeach
    </div>
    <div class="field"><label for="feedback">Masukan (opsional, maksimal 2.000 karakter)</label><textarea id="feedback" name="feedback" maxlength="2000">{{ old('feedback') }}</textarea></div>
    <p>Periksa jawaban sebelum mengirim. Jawaban final tidak dapat diubah pada tahap ini.</p>
    <button class="btn primary" type="submit" :disabled="sending" x-text="sending ? 'Menunggu konfirmasi server…' : 'Kirim evaluasi'">Kirim evaluasi</button>
</form>
@endif
</div>
@endsection

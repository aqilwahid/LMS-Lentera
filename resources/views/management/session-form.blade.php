@extends('layouts.app')
@section('title','Pengaturan sesi — LENTERA')
@section('content')
<div class="server-form"><a href="{{ route('management.activities.edit',$activity) }}">Kembali ke kegiatan</a>
<div class="page-heading"><div><h1>{{ $session->exists ? 'Pengaturan sesi.' : 'Tambah sesi.' }}</h1><p>{{ $activity->title }}</p><p>Seluruh waktu menggunakan {{ config('app.timezone') }}.</p></div></div>
@if($contextLocked)<div class="server-status">Peserta sudah bergabung atau evaluasi sudah terkirim. Nama sesi, narasumber dan versi instrumen dikunci untuk menjaga riwayat. Jadwal tetap dapat diubah.</div>@endif
<form class="panel" method="post" action="{{ $session->exists ? route('management.sessions.update',$session) : route('management.sessions.store',$activity) }}">
@csrf
@if($session->exists) @method('put')<input type="hidden" id="revision" name="revision" value="{{ old('revision',$session->revision) }}"> @endif
@foreach(['title'=>'Nama sesi','speaker_name'=>'Nama narasumber'] as $field=>$label)
<div class="field"><label for="{{ $field }}">{{ $label }}</label><input id="{{ $field }}" name="{{ $field }}" value="{{ old($field,$session->$field) }}" @required($field==='title') maxlength="255" @readonly($contextLocked)></div>
@endforeach
@if($contextLocked)
<input type="hidden" name="speaker_id" value="{{ $session->speaker_id }}">
@else
<div class="field"><label for="speaker_id">Pilih dari master narasumber (opsional)</label><select id="speaker_id" name="speaker_id"><option value="">Isi nama narasumber langsung</option>
@foreach($speakers as $speaker)<option value="{{ $speaker->id }}" @selected((string)old('speaker_id',$session->speaker_id)===(string)$speaker->id)>{{ $speaker->name }}{{ $speaker->is_active ? '' : ' (nonaktif)' }}</option>@endforeach
</select><p>Jika memilih master, nama ditetapkan server dari master. Jika tidak memilih master, isi Nama narasumber di atas. Nama pada sesi lama tidak ikut berubah saat master diubah.</p></div>
@endif
<div class="field"><label for="l1_instrument_id">Versi instrumen L1</label>
@if($contextLocked)
<input type="hidden" name="l1_instrument_id" value="{{ $session->l1_instrument_id }}">
<input id="l1_instrument_id" value="{{ $session->instrument->code }} · versi {{ $session->instrument->version }}" readonly>
@else
<select id="l1_instrument_id" name="l1_instrument_id" required><option value="">Pilih instrumen terbit</option>
@foreach($instruments as $instrument)<option value="{{ $instrument->id }}" @selected((string)old('l1_instrument_id',$session->l1_instrument_id)===(string)$instrument->id)>{{ $instrument->code }} · versi {{ $instrument->version }}</option>@endforeach
</select>
@endif
<p>Hanya instrumen terbit dengan lima pertanyaan yang dapat digunakan. Instrumen contoh digunakan hanya untuk pemeriksaan lokal.</p></div>
@foreach(['starts_at'=>'Waktu sesi','join_opens_at'=>'Pembukaan pendaftaran','join_closes_at'=>'Penutupan pendaftaran','l1_opens_at'=>'Pembukaan evaluasi L1','l1_closes_at'=>'Penutupan evaluasi L1'] as $field=>$label)
<div class="field"><label for="{{ $field }}">{{ $label }}</label><input type="datetime-local" id="{{ $field }}" name="{{ $field }}" value="{{ old($field,$session->$field?->format('Y-m-d\TH:i')) }}" required></div>
@endforeach
<p>Jadwal pendaftaran dan evaluasi diatur terpisah. Waktu penutupan harus sesudah pembukaan masing-masing.</p>
<button class="btn primary" type="submit" @disabled(!$contextLocked && $instruments->isEmpty())>Simpan sesi</button>
@if(!$contextLocked && $instruments->isEmpty())<p>Belum ada instrumen L1 yang siap. Hubungi petugas instrumen sebelum membuat sesi.</p>@endif
</form>
@if($session->exists)
<section class="panel"><h2>Akses QR kelas</h2><p>Status: <strong>{{ $session->join_enabled ? 'Aktif' : 'Nonaktif' }}</strong>. Pendaftaran tetap mengikuti jadwal.</p>
@if($session->join_enabled)<p><a class="btn primary" href="{{ route('class.access',$session) }}">Lihat QR dan tautan</a></p>@endif
<form method="post" action="{{ route('management.sessions.access',$session) }}">@csrf
<input type="hidden" name="revision" value="{{ $session->revision }}"><input type="hidden" name="action" value="{{ $session->join_enabled ? 'disable' : 'enable' }}">
<p>{{ $session->join_enabled ? 'Menonaktifkan akses menghentikan penggunaan tautan oleh peserta, termasuk peserta yang sudah bergabung. Riwayat jawaban tetap tersimpan.' : 'Aktifkan setelah memeriksa kegiatan, narasumber, instrumen dan jadwal. Tautan yang sama dapat digunakan kembali.' }}</p>
<button class="btn secondary" type="submit">{{ $session->join_enabled ? 'Nonaktifkan akses kelas' : 'Aktifkan akses kelas' }}</button>
</form>
<hr>
<form method="post" action="{{ route('management.sessions.access',$session) }}">@csrf<input type="hidden" name="revision" value="{{ $session->revision }}"><input type="hidden" name="action" value="rotate">
<p>Mengganti QR membuat tautan lama tidak berlaku, termasuk untuk peserta yang sudah bergabung. Setelah diganti, bagikan tautan baru. Data peserta dan evaluasi tetap tersimpan.</p>
<button class="btn secondary" type="submit">Ganti QR dan tautan</button>
</form>
</section>
@endif
</div>
@endsection

@extends('layouts.app')
@section('title','Detail kepesertaan — LENTERA')
@section('content')
<div class="server-form server-context"><a href="{{ route('participants.index') }}">Kembali ke kepesertaan</a><div class="page-heading"><div><h1>Detail kepesertaan.</h1><p>{{ $enrollment->activity->title }}</p></div></div>
<section class="panel"><h2>Identitas saat pendaftaran</h2>
@foreach(['full_name'=>'Nama lengkap','institution_name'=>'Instansi asli','identity_number'=>'NIP/NIM/NPM','department'=>'Jurusan/Program Studi','faculty'=>'Fakultas','phone'=>'No. HP','participant_category'=>'Kategori','email'=>'Email'] as $field=>$label)<p><strong>{{ $label }}</strong><br>{{ $enrollment->$field ?? 'Belum tersedia pada data lama' }}</p>@endforeach
<p><strong>Sesi</strong><br>{{ $enrollment->joinedSession?->title ?? 'Kepesertaan kegiatan lama' }}</p>
<p>Instansi asli dan kategori tidak dapat diubah. Salah ketik pada data di bawah dapat dikoreksi dengan alasan; setiap koreksi dicatat pada riwayat.</p></section>
<form class="panel" method="post" action="{{ route('participants.correct',$enrollment) }}">@csrf @method('put')<h2>Koreksi identitas</h2>
<input name="revision" type="hidden" value="{{ old('revision',$enrollment->revision) }}">
@foreach(['full_name'=>'Nama lengkap','identity_number'=>'NIP/NIM/NPM','email'=>'Email','phone'=>'No. HP','department'=>'Jurusan/Program Studi','faculty'=>'Fakultas'] as $field=>$label)<div class="field"><label for="c-{{ $field }}">{{ $label }}</label><input id="c-{{ $field }}" name="{{ $field }}" value="{{ old($field,$enrollment->$field) }}" maxlength="255" required></div>@endforeach
<div class="field"><label for="c-reason">Alasan koreksi</label><input id="c-reason" name="reason" value="{{ old('reason') }}" minlength="5" maxlength="500" required></div>
<button class="btn secondary">Simpan koreksi</button></form>
<form class="panel" method="post" action="{{ route('participants.update',$enrollment) }}">@csrf @method('put')
<input id="revision" name="revision" type="hidden" value="{{ old('revision',$enrollment->revision) }}">
<div class="field"><label for="status">Status kepesertaan</label><select id="status" name="status"><option value="active" @selected(old('status',$enrollment->status)==='active')>Aktif</option><option value="revoked" @selected(old('status',$enrollment->status)==='revoked')>Nonaktif</option></select></div>
<p>Menonaktifkan kepesertaan menghentikan akses kelas dan pengiriman evaluasi peserta tersebut. Riwayat jawaban tetap tersimpan. Pengaktifan kembali tetap mengikuti status QR dan jadwal kelas.</p>
@if(!$enrollment->institution_id)
<div class="field"><label for="mapped_institution_id">Pemetaan ke master institusi</label><select id="mapped_institution_id" name="mapped_institution_id"><option value="">Belum dipetakan</option>
@foreach($institutions as $institution)<option value="{{ $institution->id }}" @selected((string)old('mapped_institution_id',$enrollment->mapped_institution_id)===(string)$institution->id)>{{ $institution->name }}{{ $institution->is_active ? '' : ' (nonaktif, pemetaan lama)' }}</option>@endforeach
</select></div><p>Pilih hanya setelah memastikan instansi asli sesuai master. Pemetaan mengubah cakupan agregat mitra; isian asli tetap disimpan. Kosongkan untuk membatalkan pemetaan.</p>
@else <p>Institusi sudah dikenali saat pendaftaran dan konteks historisnya tetap dipertahankan.</p>@endif
<button class="btn primary">Simpan kepesertaan</button>
</form></div>
@endsection

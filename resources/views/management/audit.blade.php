@extends('layouts.app')
@section('title','Riwayat perubahan — LENTERA')
@section('content')
<div class="page-heading"><div><p class="eyebrow">PENGELOLA BI</p><h1>Riwayat perubahan.</h1><p>Perubahan kegiatan, sesi, QR, master dan kepesertaan dalam cakupan KPwBI Anda.</p></div></div>
<section class="panel server-list">
@forelse($entries as $entry)
<article><h2>{{ ['activity.created'=>'Kegiatan dibuat','activity.updated'=>'Kegiatan diubah','session.created'=>'Sesi dibuat','session.updated'=>'Sesi diubah','access.enable'=>'Akses diaktifkan','access.disable'=>'Akses dinonaktifkan','access.rotate'=>'QR diganti','master.created'=>'Master dibuat','master.updated'=>'Master diubah','enrollment.updated'=>'Kepesertaan diubah','material.uploaded'=>'PDF diunggah','material.publish'=>'Versi materi diterbitkan','material.hide'=>'Materi disembunyikan'][$entry->action] ?? $entry->action }}</h2>
<p>{{ $entry->created_at->format('d/m/Y H:i:s') }} · {{ $entry->actor?->name ?? 'Akun tidak tersedia' }} · {{ ['activity'=>'Kegiatan','session'=>'Sesi','institution'=>'Institusi','speaker'=>'Narasumber','enrollment'=>'Kepesertaan','material'=>'Materi'][$entry->entity_type] ?? 'Catatan' }} #{{ $entry->entity_id }}</p>
<details><summary>Lihat perubahan</summary>
@foreach(($entry->changes['after'] ?? []) as $field=>$value)
<p><strong>{{ ['title'=>'Nama','name'=>'Nama master','organization'=>'Asal instansi','type'=>'Jenis narasumber','is_active'=>'Status master','status'=>'Status kepesertaan','visible'=>'Terbit untuk peserta','version_id'=>'Versi materi (nomor catatan)','version_number'=>'Nomor versi','size_bytes'=>'Ukuran berkas (byte)','sha256'=>'Sidik berkas SHA-256','mapped_institution_id'=>'Pemetaan institusi (nomor master)','speaker_id'=>'Narasumber (nomor master)','description'=>'Deskripsi','speaker_name'=>'Narasumber','l1_instrument_id'=>'ID versi instrumen L1','starts_at'=>'Waktu sesi','join_opens_at'=>'Pendaftaran dibuka','join_closes_at'=>'Pendaftaran ditutup','l1_opens_at'=>'L1 dibuka','l1_closes_at'=>'L1 ditutup','enabled'=>'Status akses'][$field] ?? $field }}</strong><br>
Sebelum: {{ is_bool($entry->changes['before'][$field] ?? null) ? ($entry->changes['before'][$field] ? 'Aktif' : 'Nonaktif') : ($entry->changes['before'][$field] ?? '—') }}<br>
Sesudah: {{ is_bool($value) ? ($value ? 'Aktif' : 'Nonaktif') : ($value ?? '—') }}</p>
@endforeach
@if($entry->changes['token_rotated'] ?? false)<p>QR dan tautan diganti; tautan lama tidak berlaku.</p>@endif
</details>
</article>
@empty <p>Belum ada catatan perubahan dari fitur pengelolaan.</p>
@endforelse
{{ $entries->links() }}
</section>
@endsection

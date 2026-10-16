@extends('layouts.app')
@section('title','Riwayat perubahan — LENTERA')
@section('content')
<div class="page-heading"><div><p class="eyebrow">PENGELOLA BI</p><h1>Riwayat perubahan.</h1><p>Perubahan kegiatan, sesi, QR, master, kepesertaan, materi, dan instrumen L1/L2 dalam cakupan KPwBI Anda.</p></div></div>
<section class="panel server-list">
@forelse($entries as $entry)
<article><h2>{{ ['activity.created'=>'Kegiatan dibuat','activity.updated'=>'Kegiatan diubah','session.created'=>'Sesi dibuat','session.updated'=>'Sesi diubah','access.enable'=>'Akses diaktifkan','access.disable'=>'Akses dinonaktifkan','access.rotate'=>'QR diganti','master.created'=>'Master dibuat','master.updated'=>'Master diubah','enrollment.updated'=>'Kepesertaan diubah','material.uploaded'=>'PDF diunggah','material.publish'=>'Versi materi diterbitkan','material.hide'=>'Materi disembunyikan','instrument.created'=>'Draf instrumen dibuat','instrument.updated'=>'Draf instrumen diubah','instrument.published'=>'Instrumen diterbitkan','instrument.versioned'=>'Versi instrumen dibuat','l2.instrument.created'=>'Draf soal L2 dibuat','l2.instrument.updated'=>'Draf soal L2 diubah','l2.instrument.published'=>'Soal L2 diterbitkan','l2.instrument.versioned'=>'Versi soal L2 dibuat','l2.settings.updated'=>'Pengaturan L2 disimpan'][$entry->action] ?? $entry->action }}</h2>
<p>{{ $entry->created_at->format('d/m/Y H:i:s') }} · {{ $entry->actor?->name ?? 'Akun tidak tersedia' }} · {{ ['activity'=>'Kegiatan','session'=>'Sesi','institution'=>'Institusi','speaker'=>'Narasumber','enrollment'=>'Kepesertaan','material'=>'Materi','l1_instrument'=>'Instrumen L1','l2_instrument'=>'Instrumen L2','l2_assessment'=>'Pengaturan L2'][$entry->entity_type] ?? 'Catatan' }} #{{ $entry->entity_id }}</p>
<details><summary>Lihat perubahan</summary>
@foreach(($entry->changes['after'] ?? []) as $field=>$value)
<p><strong>{{ ['l2_instrument_id'=>'ID versi instrumen L2','question_count'=>'Jumlah soal','content_hash'=>'Penanda isi versi','pre_opens_at'=>'Pretest dibuka','pre_closes_at'=>'Pretest ditutup','post_opens_at'=>'Post-test dibuka','post_closes_at'=>'Post-test ditutup','require_pretest'=>'Prasyarat pretest final','results_visible'=>'Skor ditampilkan ke peserta','code'=>'Kode instrumen','version'=>'Versi instrumen','questions_text'=>'Butir penilaian','published_at'=>'Waktu terbit','title'=>'Nama','name'=>'Nama master','organization'=>'Asal instansi','type'=>'Jenis narasumber','is_active'=>'Status master','status'=>'Status kepesertaan','visible'=>'Terbit untuk peserta','version_id'=>'Versi materi (nomor catatan)','version_number'=>'Nomor versi','size_bytes'=>'Ukuran berkas (byte)','sha256'=>'Sidik berkas SHA-256','mapped_institution_id'=>'Pemetaan institusi (nomor master)','speaker_id'=>'Narasumber (nomor master)','description'=>'Deskripsi','speaker_name'=>'Narasumber','l1_instrument_id'=>'ID versi instrumen L1','starts_at'=>'Waktu sesi','join_opens_at'=>'Pendaftaran dibuka','join_closes_at'=>'Pendaftaran ditutup','l1_opens_at'=>'L1 dibuka','l1_closes_at'=>'L1 ditutup','enabled'=>'Status akses'][$field] ?? $field }}</strong><br>
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

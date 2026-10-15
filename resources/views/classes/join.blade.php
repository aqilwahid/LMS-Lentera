@extends('layouts.app')
@section('title','Gabung kelas — LENTERA')
@section('content')
<div class="server-form">
<div class="page-heading"><div><p class="eyebrow">PENDAFTARAN PESERTA</p><h1>Gabung kelas.</h1><p>Isi data berikut untuk mengikuti kegiatan. Tidak perlu akun atau kata sandi.</p></div></div>
<section class="panel"><h2>{{ $session->activity->title }}</h2><p>{{ $session->title }}</p></section>
@if(!$joinOpen)
<section class="panel"><h2>Pendaftaran belum dibuka atau sudah ditutup.</h2><p>Hubungi pengelola untuk memeriksa jadwal kelas.</p></section>
@else
<form class="panel" method="post" action="{{ route('class.register',$session->join_token) }}">
@csrf
@foreach(['full_name'=>'Nama Lengkap','institution_name'=>'Instansi/Perguruan Tinggi','identity_number'=>'NIP/NIM/NPM','department'=>'Jurusan/Program Studi','faculty'=>'Fakultas','phone'=>'No. HP','participant_category'=>'Kategori','email'=>'Email'] as $field=>$label)
<div class="field"><label for="{{ $field }}">{{ $label }}</label>
<input id="{{ $field }}" name="{{ $field }}" type="{{ $field === 'email' ? 'email' : ($field === 'phone' ? 'tel' : 'text') }}" value="{{ old($field) }}" required maxlength="{{ $field==='phone' ? 30 : (in_array($field,['identity_number','participant_category']) ? 100 : 255) }}"
@if($field==='institution_name') list="institution-options" @endif
@if($field==='participant_category') list="category-options" @endif
@if($field==='full_name') autocomplete="name" @elseif($field==='email') autocomplete="email" @elseif($field==='phone') autocomplete="tel" @endif
@error($field) aria-invalid="true" aria-describedby="{{ $field }}-error" @enderror>
@error($field)<p id="{{ $field }}-error" role="alert">{{ $message }}</p>@enderror
</div>
@endforeach
<datalist id="institution-options">@foreach($institutions as $institution)<option value="{{ $institution->name }}">@endforeach</datalist>
<datalist id="category-options">@foreach(config('lentera.participant_categories') as $category)<option value="{{ $category }}">@endforeach</datalist>
<div class="field"><label for="speaker_name">Nama Narasumber</label><textarea id="speaker_name" name="speaker_name" rows="2" readonly aria-describedby="speaker-help">{{ $session->speaker_name }}</textarea><p id="speaker-help">Sesuai narasumber pada kelas yang dibagikan pengelola.</p></div>
<p>Untuk peserta di luar perguruan tinggi, gunakan nama unit kerja pada Jurusan/Program Studi dan Fakultas. Kategori dapat diketik sesuai status Anda.</p>
<p>Data digunakan untuk kepesertaan dan evaluasi kegiatan. Akses kelas pada browser ini menggunakan sesi; jangan memakai browser bersama untuk beberapa peserta.</p>
<button class="btn primary" type="submit">Gabung kelas</button>
</form>
@endif
</div>
@endsection

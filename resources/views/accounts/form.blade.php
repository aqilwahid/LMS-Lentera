@extends('layouts.app')
@section('title','Pengaturan akun — LENTERA')
@section('content')
<div class="server-form"><a href="{{ route('accounts.index') }}">Kembali ke akun</a><div class="page-heading"><div><h1>{{ $account->exists ? 'Ubah' : 'Tambah' }} akun.</h1></div></div>
@if($account->exists)
<form class="panel" method="post" action="{{ route('accounts.update',$account->id) }}">@csrf @method('put')
<p><strong>Email</strong><br>{{ $account->email }}</p><p><strong>Peran</strong><br>{{ $account->role==='bi_admin' ? 'Pengelola BI' : 'Mitra · '.$account->institution?->name }}</p>
<div class="field"><label for="name">Nama</label><input id="name" name="name" value="{{ old('name',$account->name) }}" maxlength="255" required></div>
<div class="field"><label for="is_active">Status akun</label><select id="is_active" name="is_active"><option value="1" @selected((string)old('is_active',(int)$account->is_active)==='1')>Aktif</option><option value="0" @selected((string)old('is_active',(int)$account->is_active)==='0')>Nonaktif</option></select></div>
<p>Akun nonaktif langsung keluar dari semua sesi dan tidak dapat masuk.</p><button class="btn primary">Simpan akun</button></form>
@if($account->is_active)<form class="panel" method="post" action="{{ route('accounts.reset',$account->id) }}">@csrf<p>Kirim tautan atur kata sandi ke email akun (berlaku 60 menit).</p><button class="btn secondary">Kirim tautan atur kata sandi</button></form>@endif
@else
<form class="panel" method="post" action="{{ route('accounts.store') }}">@csrf
<div class="field"><label for="name">Nama</label><input id="name" name="name" value="{{ old('name') }}" maxlength="255" required></div>
<div class="field"><label for="email">Email</label><input id="email" name="email" type="email" value="{{ old('email') }}" maxlength="255" required></div>
<div class="field"><label for="role">Peran</label><select id="role" name="role" required><option value="bi_admin" @selected(old('role')==='bi_admin')>Pengelola BI</option><option value="partner" @selected(old('role','partner')==='partner')>Mitra · akses lihat</option></select></div>
<div class="field"><label for="institution_id">Institusi (khusus mitra)</label><select id="institution_id" name="institution_id"><option value="">—</option>@foreach($institutions as $i)<option value="{{ $i->id }}" @selected((string)old('institution_id')===(string)$i->id)>{{ $i->name }}</option>@endforeach</select></div>
<p>Kata sandi tidak dibuat oleh pengelola. Pemilik akun menerima tautan email untuk menetapkannya.</p><button class="btn primary">Buat akun</button></form>
@endif
</div>
@endsection

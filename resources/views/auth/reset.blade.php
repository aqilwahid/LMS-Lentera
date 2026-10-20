@extends('layouts.app')
@section('title','Atur kata sandi — LENTERA')
@section('content')
<div class="server-form"><div class="page-heading"><div><h1>Atur kata sandi baru.</h1><p>Minimal 12 karakter, memuat huruf dan angka.</p></div></div>
<form class="panel" method="post" action="{{ route('password.update') }}">@csrf
<input type="hidden" name="token" value="{{ $token }}">
<div class="field"><label for="email">Email akun</label><input id="email" name="email" type="email" value="{{ old('email',$email) }}" autocomplete="username" required maxlength="255"></div>
<div class="field"><label for="password">Kata sandi baru</label><input id="password" name="password" type="password" autocomplete="new-password" minlength="12" required></div>
<div class="field"><label for="password_confirmation">Ulangi kata sandi</label><input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="12" required></div>
<button class="btn primary">Simpan kata sandi</button></form></div>
@endsection

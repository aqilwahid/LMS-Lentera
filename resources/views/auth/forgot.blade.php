@extends('layouts.app')
@section('title','Pulihkan kata sandi — LENTERA')
@section('content')
<div class="server-form"><a href="{{ route('login') }}">Kembali ke halaman masuk</a><div class="page-heading"><div><h1>Pulihkan kata sandi.</h1><p>Untuk akun pengelola BI dan mitra. Peserta tidak memerlukan akun.</p></div></div>
<form class="panel" method="post" action="{{ route('password.email') }}">@csrf
<div class="field"><label for="email">Email akun</label><input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required maxlength="255"></div>
<button class="btn primary">Kirim tautan pemulihan</button></form></div>
@endsection

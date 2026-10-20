@extends('layouts.app')
@section('title','Akun — LENTERA')
@section('content')
<div class="page-heading"><div><p class="eyebrow">PENGELOLA BI</p><h1>Akun pengelola dan mitra.</h1><p>Akun BI pada KPwBI Anda dan akun mitra pada institusi KPwBI Anda.</p></div><a class="btn primary" href="{{ route('accounts.create') }}">Tambah akun</a></div>
<form class="panel" method="get"><div class="field"><label for="q">Cari nama atau email</label><input id="q" name="q" value="{{ request('q') }}" maxlength="100"></div><button class="btn secondary">Cari</button></form>
<section class="panel server-list server-context">@forelse($accounts as $account)
<article><h2>{{ $account->name }}</h2><p>{{ $account->email }} · {{ $account->role==='bi_admin' ? 'Pengelola BI' : 'Mitra · '.$account->institution?->name }} · {{ $account->is_active ? 'Aktif' : 'Nonaktif' }}</p><a href="{{ route('accounts.edit',$account->id) }}">Ubah: {{ $account->name }}</a></article>
@empty <p>Belum ada akun yang sesuai.</p>@endforelse{{ $accounts->links() }}</section>
@endsection

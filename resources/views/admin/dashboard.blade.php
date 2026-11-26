@extends('layouts.app')
@section('title', 'Dashboard Admin')
@section('content')
<div class="topbar">
    <h1>Dashboard Admin</h1>
    <a class="btn" href="{{ route('admin.presensi') }}">Buka Presensi Wajah</a>
</div>
<div class="card">Halo, {{ session('auth_nama') }}.</div>
@include('rekap._ringkasan')
@endsection

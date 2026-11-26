@extends('layouts.app')
@section('title', 'Dashboard Guru')
@section('content')
<div class="topbar"><h1>Dashboard Guru</h1></div>
<div class="card">Halo, {{ session('auth_nama') }}.</div>
@include('rekap._ringkasan')
@endsection

@extends('layouts.app')
@section('title', 'Dashboard Admin')
@section('content')
<div class="topbar"><h1>Dashboard Admin</h1></div>
<div class="card">Halo, {{ session('auth_nama') }}.</div>
@endsection
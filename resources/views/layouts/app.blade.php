<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Dashboard') - Presensi Wajah SMK Ar Rahmah</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
@php($role = session('auth_role'))
<div class="shell">
    <aside class="sidebar">
        <div class="brand">Presensi Wajah<br><small>SMK Ar Rahmah Bantul</small></div>

        @if ($role === 'admin')
            <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">Dashboard</a>
            <a href="{{ route('admin.kelas.index') }}" class="{{ request()->routeIs('admin.kelas.*') ? 'active' : '' }}">Data Kelas</a>
            <a href="{{ route('admin.siswa.index') }}" class="{{ request()->routeIs('admin.siswa.*') ? 'active' : '' }}">Data Siswa</a>
            <a href="{{ route('admin.guru.index') }}" class="{{ request()->routeIs('admin.guru.*') ? 'active' : '' }}">Data Guru</a>
            <a href="{{ route('admin.dataset.index') }}" class="{{ request()->routeIs('admin.dataset.*') ? 'active' : '' }}">Dataset Wajah</a>
        @else
            <a href="{{ route('guru.dashboard') }}" class="{{ request()->routeIs('guru.dashboard') ? 'active' : '' }}">Dashboard</a>
        @endif

        <a href="{{ route('jadwal.index') }}" class="{{ request()->routeIs('jadwal.*') ? 'active' : '' }}">Jadwal Presensi</a>

        <div class="user">
            {{ session('auth_nama') }}<br>
            <span style="opacity:.7">{{ $role === 'admin' ? 'Admin / Staf IT' : 'Guru' }}</span>
            <form method="POST" action="{{ route('logout') }}" style="margin-top:.6rem">
                @csrf
                <button class="btn sec sm" type="submit">Keluar</button>
            </form>
        </div>
    </aside>

    <main class="main">
        @if (session('success')) <div class="alert success">{{ session('success') }}</div> @endif
        @if (session('error')) <div class="alert error">{{ session('error') }}</div> @endif
        @yield('content')
    </main>
</div>
@stack('scripts')
</body>
</html>
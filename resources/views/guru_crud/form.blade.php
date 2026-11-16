@extends('layouts.app')
@section('title', $guru->exists ? 'Ubah Guru' : 'Tambah Guru')
@section('content')
<div class="topbar"><h1>{{ $guru->exists ? 'Ubah' : 'Tambah' }} Guru</h1></div>
<div class="card" style="max-width:640px">
<form method="POST" action="{{ $guru->exists ? route('admin.guru.update', $guru) : route('admin.guru.store') }}" autocomplete="off">
    @csrf
    @if ($guru->exists) @method('PUT') @endif
    <div class="grid2">
        <div class="field">
            <label>NIP</label>
            <input name="nip" value="{{ old('nip', $guru->nip) }}">
            @error('nip') <div class="err">{{ $message }}</div> @enderror
        </div>
        <div class="field">
            <label>Nama Lengkap</label>
            <input name="nama" value="{{ old('nama', $guru->nama) }}" required>
            @error('nama') <div class="err">{{ $message }}</div> @enderror
        </div>
        <div class="field">
            <label>Username</label>
            <input name="username" value="{{ old('username', $guru->username) }}" required>
            @error('username') <div class="err">{{ $message }}</div> @enderror
        </div>
        <div class="field">
            <label>Password {{ $guru->exists ? '(kosongkan jika tidak diubah)' : '' }}</label>
            <input type="password" name="password" autocomplete="new-password" {{ $guru->exists ? '' : 'required' }}>
            @error('password') <div class="err">{{ $message }}</div> @enderror
        </div>
        <div class="field">
            <label>Email</label>
            <input type="email" name="email" value="{{ old('email', $guru->email) }}">
            @error('email') <div class="err">{{ $message }}</div> @enderror
        </div>
        <div class="field">
            <label>No. HP</label>
            <input name="no_hp" value="{{ old('no_hp', $guru->no_hp) }}">
            @error('no_hp') <div class="err">{{ $message }}</div> @enderror
        </div>
    </div>
    <button class="btn" type="submit">Simpan</button>
    <a class="btn sec" href="{{ route('admin.guru.index') }}">Batal</a>
</form>
</div>
@endsection
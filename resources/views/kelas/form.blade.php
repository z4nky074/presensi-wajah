@extends('layouts.app')
@section('title', $kelas->exists ? 'Ubah Kelas' : 'Tambah Kelas')
@section('content')
<div class="topbar"><h1>{{ $kelas->exists ? 'Ubah' : 'Tambah' }} Kelas</h1></div>
<div class="card" style="max-width:640px">
<form method="POST" action="{{ $kelas->exists ? route('admin.kelas.update', $kelas) : route('admin.kelas.store') }}">
    @csrf
    @if ($kelas->exists) @method('PUT') @endif
    <div class="grid2">
        <div class="field">
            <label>Nama Kelas</label>
            <input name="nama_kelas" value="{{ old('nama_kelas', $kelas->nama_kelas) }}" placeholder="contoh: X TKJ 1" required>
            @error('nama_kelas') <div class="err">{{ $message }}</div> @enderror
        </div>
        <div class="field">
            <label>Tingkat</label>
            <select name="tingkat" required>
                @foreach (['X', 'XI', 'XII'] as $t)
                    <option value="{{ $t }}" @selected(old('tingkat', $kelas->tingkat) === $t)>{{ $t }}</option>
                @endforeach
            </select>
            @error('tingkat') <div class="err">{{ $message }}</div> @enderror
        </div>
        <div class="field">
            <label>Jurusan</label>
            <input name="jurusan" value="{{ old('jurusan', $kelas->jurusan) }}" required>
            @error('jurusan') <div class="err">{{ $message }}</div> @enderror
        </div>
        <div class="field">
            <label>Wali Kelas</label>
            <input name="wali_kelas" value="{{ old('wali_kelas', $kelas->wali_kelas) }}">
            @error('wali_kelas') <div class="err">{{ $message }}</div> @enderror
        </div>
    </div>
    <button class="btn" type="submit">Simpan</button>
    <a class="btn sec" href="{{ route('admin.kelas.index') }}">Batal</a>
</form>
</div>
@endsection
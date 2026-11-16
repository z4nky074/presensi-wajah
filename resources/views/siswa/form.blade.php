@extends('layouts.app')
@section('title', $siswa->exists ? 'Ubah Siswa' : 'Tambah Siswa')
@section('content')
<div class="topbar"><h1>{{ $siswa->exists ? 'Ubah' : 'Tambah' }} Siswa</h1></div>
<div class="card" style="max-width:640px">
<form method="POST" action="{{ $siswa->exists ? route('admin.siswa.update', $siswa) : route('admin.siswa.store') }}">
    @csrf
    @if ($siswa->exists) @method('PUT') @endif
    <div class="grid2">
        <div class="field">
            <label>NIS</label>
            <input name="nis" value="{{ old('nis', $siswa->nis) }}" required>
            @error('nis') <div class="err">{{ $message }}</div> @enderror
        </div>
        <div class="field">
            <label>Nama Lengkap</label>
            <input name="nama" value="{{ old('nama', $siswa->nama) }}" required>
            @error('nama') <div class="err">{{ $message }}</div> @enderror
        </div>
        <div class="field">
            <label>Jenis Kelamin</label>
            <select name="jenis_kelamin" required>
                @foreach (['L' => 'Laki-laki', 'P' => 'Perempuan'] as $v => $t)
                    <option value="{{ $v }}" @selected(old('jenis_kelamin', $siswa->jenis_kelamin) === $v)>{{ $t }}</option>
                @endforeach
            </select>
            @error('jenis_kelamin') <div class="err">{{ $message }}</div> @enderror
        </div>
        <div class="field">
            <label>Kelas</label>
            <select name="id_kelas" required>
                <option value="">- pilih kelas -</option>
                @foreach ($kelas as $k)
                    <option value="{{ $k->id_kelas }}" @selected(old('id_kelas', $siswa->id_kelas) == $k->id_kelas)>{{ $k->nama_kelas }}</option>
                @endforeach
            </select>
            @error('id_kelas') <div class="err">{{ $message }}</div> @enderror
        </div>
    </div>
    <button class="btn" type="submit">Simpan</button>
    <a class="btn sec" href="{{ route('admin.siswa.index') }}">Batal</a>
</form>
</div>
@endsection
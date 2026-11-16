@extends('layouts.app')
@section('title', $jadwal->exists ? 'Ubah Jadwal' : 'Tambah Jadwal')
@section('content')
<div class="topbar"><h1>{{ $jadwal->exists ? 'Ubah' : 'Tambah' }} Jadwal Presensi</h1></div>
<div class="card" style="max-width:640px">
<form method="POST" action="{{ $jadwal->exists ? route('jadwal.update', $jadwal) : route('jadwal.store') }}">
    @csrf
    @if ($jadwal->exists) @method('PUT') @endif
    <div class="grid2">
        <div class="field">
            <label>Peran</label>
            <select name="peran" required>
                @foreach (['siswa' => 'Siswa', 'guru' => 'Guru'] as $v => $t)
                    <option value="{{ $v }}" @selected(old('peran', $jadwal->peran) === $v)>{{ $t }}</option>
                @endforeach
            </select>
            @error('peran') <div class="err">{{ $message }}</div> @enderror
        </div>
        <div class="field">
            <label>Hari</label>
            <select name="hari" required>
                @foreach ($hari as $h)
                    <option @selected(old('hari', $jadwal->hari) === $h)>{{ $h }}</option>
                @endforeach
            </select>
            @error('hari') <div class="err">{{ $message }}</div> @enderror
        </div>
        <div class="field">
            <label>Jam Masuk (batas tepat waktu)</label>
            <input type="time" name="jam_masuk" value="{{ old('jam_masuk', $jadwal->jam_masuk) }}" required>
            @error('jam_masuk') <div class="err">{{ $message }}</div> @enderror
        </div>
        <div class="field">
            <label>Jam Pulang</label>
            <input type="time" name="jam_pulang" value="{{ old('jam_pulang', $jadwal->jam_pulang) }}" required>
            @error('jam_pulang') <div class="err">{{ $message }}</div> @enderror
        </div>
        <div class="field">
            <label>Status</label>
            <select name="status">
                @foreach (['aktif' => 'Aktif', 'nonaktif' => 'Nonaktif'] as $v => $t)
                    <option value="{{ $v }}" @selected(old('status', $jadwal->status) === $v)>{{ $t }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <button class="btn" type="submit">Simpan</button>
    <a class="btn sec" href="{{ route('jadwal.index') }}">Batal</a>
</form>
</div>
@endsection
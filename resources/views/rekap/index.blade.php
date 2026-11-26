@extends('layouts.app')
@section('title', 'Rekap Presensi')
@section('content')
<div class="topbar">
    <div>
        <h1>Rekap Presensi</h1>
        <div class="muted">Presensi siswa dan guru yang sudah tercatat lewat kamera. Halaman ini hanya untuk melihat.</div>
    </div>
</div>

<div class="card">
    <form method="GET" action="{{ route('rekap.index') }}">
        <div class="grid2">
            <div class="field">
                <label for="dari">Dari tanggal</label>
                <input type="date" id="dari" name="dari" value="{{ $dari }}">
                @error('dari') <div class="err">{{ $message }}</div> @enderror
            </div>
            <div class="field">
                <label for="sampai">Sampai tanggal</label>
                <input type="date" id="sampai" name="sampai" value="{{ $sampai }}">
                @error('sampai') <div class="err">{{ $message }}</div> @enderror
            </div>
            <div class="field">
                <label for="peran">Peran</label>
                <select id="peran" name="peran">
                    <option value="">Semua</option>
                    <option value="siswa" @selected(($filter['peran'] ?? '') === 'siswa')>Siswa</option>
                    <option value="guru" @selected(($filter['peran'] ?? '') === 'guru')>Guru</option>
                </select>
            </div>
            <div class="field">
                <label for="id_kelas">Kelas (siswa)</label>
                <select id="id_kelas" name="id_kelas">
                    <option value="">Semua</option>
                    @foreach ($kelas as $k)
                        <option value="{{ $k->id_kelas }}" @selected((int) ($filter['id_kelas'] ?? 0) === $k->id_kelas)>{{ $k->nama_kelas }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="">Semua</option>
                    <option value="Hadir" @selected(($filter['status'] ?? '') === 'Hadir')>Hadir</option>
                    <option value="Terlambat" @selected(($filter['status'] ?? '') === 'Terlambat')>Terlambat</option>
                </select>
            </div>
        </div>
        <button class="btn" type="submit">Terapkan</button>
        <a class="btn sec" href="{{ route('rekap.index') }}">Hari ini</a>
    </form>
</div>

<div class="card">
    <table>
        <thead>
            <tr><th>Tanggal</th><th>Nama</th><th>Peran</th><th>Kelas</th><th>Masuk</th><th>Pulang</th><th>Status</th></tr>
        </thead>
        <tbody>
        @forelse ($daftar as $p)
            <tr>
                <td>{{ $p->tanggal->format('d-m-Y') }}</td>
                <td>{{ $p->nama }}</td>
                <td>{{ $p->peran }}</td>
                <td>{{ $p->nama_kelas }}</td>
                <td>{{ $p->jam_masuk ? substr($p->jam_masuk, 0, 5) : '-' }}</td>
                <td>{{ $p->jam_pulang ? substr($p->jam_pulang, 0, 5) : '-' }}</td>
                <td>{{ $p->status }}</td>
            </tr>
        @empty
            <tr><td colspan="7" class="muted">Tidak ada data presensi pada filter ini.</td></tr>
        @endforelse
        </tbody>
    </table>
    <div class="pager">{{ $daftar->links() }}</div>
</div>
@endsection

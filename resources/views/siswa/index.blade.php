@extends('layouts.app')
@section('title', 'Data Siswa')
@section('content')
<div class="topbar">
    <h1>Data Siswa</h1>
    <a class="btn" href="{{ route('admin.siswa.create') }}">+ Tambah Siswa</a>
</div>
<div class="card">
    <form method="GET" style="display:flex;gap:.75rem;align-items:flex-end;margin-bottom:1rem;flex-wrap:wrap">
        <div class="field" style="margin:0;flex:1;min-width:160px">
            <label>Cari (NIS / nama)</label>
            <input name="q" value="{{ request('q') }}">
        </div>
        <div class="field" style="margin:0;flex:1;min-width:160px">
            <label>Kelas</label>
            <select name="id_kelas">
                <option value="">Semua kelas</option>
                @foreach ($kelas as $k)
                    <option value="{{ $k->id_kelas }}" @selected(request('id_kelas') == $k->id_kelas)>{{ $k->nama_kelas }}</option>
                @endforeach
            </select>
        </div>
        <button class="btn" type="submit">Filter</button>
    </form>

    <table>
        <thead><tr><th>NIS</th><th>Nama</th><th>L/P</th><th>Kelas</th><th></th></tr></thead>
        <tbody>
        @forelse ($daftar as $s)
            <tr>
                <td>{{ $s->nis }}</td>
                <td>{{ $s->nama }}</td>
                <td>{{ $s->jenis_kelamin }}</td>
                <td>{{ $s->kelas->nama_kelas }}</td>
                <td>
                    <a class="btn sec sm" href="{{ route('admin.siswa.edit', $s) }}">Ubah</a>
                    <form method="POST" action="{{ route('admin.siswa.destroy', $s) }}" style="display:inline"
                          onsubmit="return confirm('Hapus siswa {{ $s->nama }}?')">
                        @csrf @method('DELETE')
                        <button class="btn danger sm">Hapus</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="muted">Tidak ada data siswa.</td></tr>
        @endforelse
        </tbody>
    </table>
    <div class="pager">{{ $daftar->links() }}</div>
</div>
@endsection
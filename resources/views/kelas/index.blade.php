@extends('layouts.app')
@section('title', 'Data Kelas')
@section('content')
<div class="topbar">
    <h1>Data Kelas</h1>
    <a class="btn" href="{{ route('admin.kelas.create') }}">+ Tambah Kelas</a>
</div>
<div class="card">
<table>
    <thead><tr><th>Kelas</th><th>Tingkat</th><th>Jurusan</th><th>Wali Kelas</th><th>Siswa</th><th></th></tr></thead>
    <tbody>
    @forelse ($daftar as $k)
        <tr>
            <td>{{ $k->nama_kelas }}</td>
            <td>{{ $k->tingkat }}</td>
            <td>{{ $k->jurusan }}</td>
            <td>{{ $k->wali_kelas ?? '-' }}</td>
            <td>{{ $k->siswa_count }}</td>
            <td>
                <a class="btn sec sm" href="{{ route('admin.kelas.edit', $k) }}">Ubah</a>
                <form method="POST" action="{{ route('admin.kelas.destroy', $k) }}" style="display:inline"
                      onsubmit="return confirm('Hapus kelas {{ $k->nama_kelas }}?')">
                    @csrf @method('DELETE')
                    <button class="btn danger sm">Hapus</button>
                </form>
            </td>
        </tr>
    @empty
        <tr><td colspan="6" class="muted">Belum ada data kelas.</td></tr>
    @endforelse
    </tbody>
</table>
</div>
@endsection
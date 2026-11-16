@extends('layouts.app')
@section('title', 'Jadwal Presensi')
@section('content')
<div class="topbar">
    <div>
        <h1>Jadwal Presensi</h1>
        <div class="muted">Presensi hanya diterima pada hari dengan jadwal berstatus aktif.</div>
    </div>
    <a class="btn" href="{{ route('jadwal.create') }}">+ Tambah Jadwal</a>
</div>
<div class="card">
<table>
    <thead><tr><th>Peran</th><th>Hari</th><th>Jam Masuk</th><th>Jam Pulang</th><th>Status</th><th></th></tr></thead>
    <tbody>
    @forelse ($daftar as $j)
        <tr>
            <td>{{ ucfirst($j->peran) }}</td>
            <td>{{ $j->hari }}</td>
            <td>{{ substr($j->jam_masuk, 0, 5) }}</td>
            <td>{{ substr($j->jam_pulang, 0, 5) }}</td>
            <td>{{ ucfirst($j->status) }}</td>
            <td>
                <a class="btn sec sm" href="{{ route('jadwal.edit', $j) }}">Ubah</a>
                <form method="POST" action="{{ route('jadwal.destroy', $j) }}" style="display:inline"
                      onsubmit="return confirm('Hapus jadwal ini?')">
                    @csrf @method('DELETE')
                    <button class="btn danger sm">Hapus</button>
                </form>
            </td>
        </tr>
    @empty
        <tr><td colspan="6" class="muted">Belum ada jadwal. Tanpa jadwal aktif, presensi akan ditolak.</td></tr>
    @endforelse
    </tbody>
</table>
</div>
@endsection
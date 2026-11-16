@extends('layouts.app')
@section('title', 'Data Guru')
@section('content')
<div class="topbar">
    <h1>Data Guru</h1>
    <a class="btn" href="{{ route('admin.guru.create') }}">+ Tambah Guru</a>
</div>
<div class="card">
    <form method="GET" style="display:flex;gap:.75rem;align-items:flex-end;margin-bottom:1rem">
        <div class="field" style="margin:0;flex:1">
            <label>Cari (NIP / nama)</label>
            <input name="q" value="{{ request('q') }}">
        </div>
        <button class="btn" type="submit">Filter</button>
    </form>

    <table>
        <thead><tr><th>NIP</th><th>Nama</th><th>Username</th><th>No. HP</th><th></th></tr></thead>
        <tbody>
        @forelse ($daftar as $g)
            <tr>
                <td>{{ $g->nip ?? '-' }}</td>
                <td>{{ $g->nama }}</td>
                <td>{{ $g->username }}</td>
                <td>{{ $g->no_hp ?? '-' }}</td>
                <td>
                    <a class="btn sec sm" href="{{ route('admin.guru.edit', $g) }}">Ubah</a>
                    <form method="POST" action="{{ route('admin.guru.destroy', $g) }}" style="display:inline"
                          onsubmit="return confirm('Hapus guru {{ $g->nama }}?')">
                        @csrf @method('DELETE')
                        <button class="btn danger sm">Hapus</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="muted">Tidak ada data guru.</td></tr>
        @endforelse
        </tbody>
    </table>
    <div class="pager">{{ $daftar->links() }}</div>
</div>
@endsection
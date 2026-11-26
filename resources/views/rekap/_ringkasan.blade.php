<div class="muted" style="margin-bottom:.6rem">Presensi hari ini ({{ \Carbon\Carbon::parse($tanggal)->format('d-m-Y') }})</div>
<div class="grid2">
    <div class="card">
        <div class="muted">Siswa</div>
        <div style="font-size:1.6rem;font-weight:700">{{ $ringkasan['siswa_hadir'] + $ringkasan['siswa_terlambat'] }}</div>
        <div class="muted">Hadir {{ $ringkasan['siswa_hadir'] }} &middot; Terlambat {{ $ringkasan['siswa_terlambat'] }}</div>
    </div>
    <div class="card">
        <div class="muted">Guru</div>
        <div style="font-size:1.6rem;font-weight:700">{{ $ringkasan['guru_hadir'] + $ringkasan['guru_terlambat'] }}</div>
        <div class="muted">Hadir {{ $ringkasan['guru_hadir'] }} &middot; Terlambat {{ $ringkasan['guru_terlambat'] }}</div>
    </div>
</div>

<div class="card">
    <div class="topbar" style="margin-bottom:.6rem">
        <strong>Presensi terbaru hari ini</strong>
        <a class="btn sec sm" href="{{ route('rekap.index') }}">Lihat semua</a>
    </div>
    <table>
        <thead><tr><th>Nama</th><th>Peran</th><th>Kelas</th><th>Masuk</th><th>Pulang</th><th>Status</th></tr></thead>
        <tbody>
        @forelse ($terbaru as $p)
            <tr>
                <td>{{ $p->nama }}</td>
                <td>{{ $p->peran }}</td>
                <td>{{ $p->nama_kelas }}</td>
                <td>{{ $p->jam_masuk ? substr($p->jam_masuk, 0, 5) : '-' }}</td>
                <td>{{ $p->jam_pulang ? substr($p->jam_pulang, 0, 5) : '-' }}</td>
                <td>{{ $p->status }}</td>
            </tr>
        @empty
            <tr><td colspan="6" class="muted">Belum ada presensi hari ini.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

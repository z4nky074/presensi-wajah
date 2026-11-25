@extends('layouts.app')
@section('title', 'Dataset Wajah')
@section('content')
<div class="topbar">
    <div>
        <h1>Dataset Wajah</h1>
        <div class="muted">Daftarkan {{ $min }} sampai 5 foto per orang (cahaya dan sudut sedikit berbeda) agar pengenalan lebih akurat.</div>
    </div>
</div>

<div class="card" style="max-width:760px">
    <h2 style="margin-top:0">Tambah Sampel Wajah</h2>
    <form method="POST" action="{{ route('admin.dataset.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="grid2">
            <div class="field">
                <label>Jenis Pemilik</label>
                <select name="peran" id="peran">
                    <option value="siswa" @selected(old('peran') !== 'guru')>Siswa</option>
                    <option value="guru" @selected(old('peran') === 'guru')>Guru</option>
                </select>
                @error('peran') <div class="err">{{ $message }}</div> @enderror
            </div>
            <div class="field" id="box-siswa">
                <label>Siswa</label>
                <select name="id_siswa">
                    <option value="">- pilih siswa -</option>
                    @foreach ($siswa as $s)
                        <option value="{{ $s->id_siswa }}" @selected(old('id_siswa') == $s->id_siswa)>
                            {{ $s->nama }} ({{ $s->kelas->nama_kelas }}) - {{ $jumlahSiswa[$s->id_siswa] ?? 0 }} foto
                        </option>
                    @endforeach
                </select>
                @error('id_siswa') <div class="err">{{ $message }}</div> @enderror
            </div>
            <div class="field" id="box-guru" hidden>
                <label>Guru</label>
                <select name="id_guru">
                    <option value="">- pilih guru -</option>
                    @foreach ($guru as $g)
                        <option value="{{ $g->id_guru }}" @selected(old('id_guru') == $g->id_guru)>
                            {{ $g->nama }} - {{ $jumlahGuru[$g->id_guru] ?? 0 }} foto
                        </option>
                    @endforeach
                </select>
                @error('id_guru') <div class="err">{{ $message }}</div> @enderror
            </div>
        </div>
        <div class="field">
            <label>Foto wajah (JPG atau PNG, maks 4 MB per foto, maks 10 foto sekaligus)</label>
            <input type="file" name="foto[]" accept="image/jpeg,image/png" multiple required>
            <div class="muted">Tahan <b>Ctrl</b> atau <b>Shift</b> untuk memilih beberapa foto sekaligus.</div>
            @error('foto') <div class="err">{{ $message }}</div> @enderror
            @error('foto.*') <div class="err">{{ $message }}</div> @enderror
        </div>
        <button class="btn" type="submit">Proses &amp; Simpan</button>
    </form>
</div>

<div class="card" style="max-width:760px">
    <h2 style="margin-top:0">Kelengkapan Dataset</h2>
    <p>
        <b>{{ $lengkap }}</b> dari <b>{{ $total }}</b> orang sudah punya minimal {{ $min }} foto.
        @if ($kurang->count() > 0)
            <span class="muted">({{ $kurang->count() }} orang masih kurang)</span>
        @endif
    </p>

    @if ($kurang->count() > 0)
        <div class="table-wrap">
        <table>
            <thead><tr><th>Nama</th><th>Peran</th><th>Kelas</th><th>Jumlah foto</th></tr></thead>
            <tbody>
            @foreach ($kurang as $k)
                <tr>
                    <td>{{ $k['nama'] }}</td>
                    <td>{{ $k['peran'] }}</td>
                    <td>{{ $k['kelas'] }}</td>
                    <td><b style="color:{{ $k['jumlah'] === 0 ? '#b91c1c' : '#b45309' }}">{{ $k['jumlah'] }}</b></td>
                </tr>
            @endforeach
            </tbody>
        </table>
        </div>
    @else
        <p class="muted">Semua orang sudah memiliki dataset yang lengkap.</p>
    @endif
</div>

<div class="card">
    <form method="GET" style="margin-bottom:1rem">
        <label>Tampilkan</label>
        <select name="peran" onchange="this.form.submit()" style="max-width:200px">
            <option value="">Semua</option>
            <option value="siswa" @selected(request('peran') === 'siswa')>Siswa</option>
            <option value="guru" @selected(request('peran') === 'guru')>Guru</option>
        </select>
    </form>

    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:1rem">
    @forelse ($daftar as $d)
        <figure style="margin:0;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;background:#fff">
            <img src="{{ route('admin.dataset.foto', $d) }}" alt="Foto {{ $d->pemilik }}" loading="lazy"
                 style="width:100%;aspect-ratio:1;object-fit:cover;display:block">
            <figcaption style="padding:.5rem .6rem;font-size:.8rem">
                <b>{{ $d->pemilik }}</b><br>
                <span class="muted">{{ ucfirst($d->peran) }}{{ $d->siswa ? ' • '.$d->siswa->kelas->nama_kelas : '' }} • {{ $d->tanggal_upload->format('d/m/Y') }}</span>
                <form method="POST" action="{{ route('admin.dataset.destroy', $d) }}" style="margin-top:.4rem"
                      onsubmit="return confirm('Hapus sampel wajah ini?')">
                    @csrf @method('DELETE')
                    <button class="btn danger sm">Hapus</button>
                </form>
            </figcaption>
        </figure>
    @empty
        <p class="muted">Belum ada dataset wajah.</p>
    @endforelse
    </div>
    <div class="pager">{{ $daftar->links() }}</div>
</div>
@endsection

@push('scripts')
<script>
    const peran = document.getElementById('peran');
    const sinkron = () => {
        document.getElementById('box-siswa').hidden = peran.value !== 'siswa';
        document.getElementById('box-guru').hidden = peran.value !== 'guru';
    };
    peran.addEventListener('change', sinkron);
    sinkron();
</script>
@endpush
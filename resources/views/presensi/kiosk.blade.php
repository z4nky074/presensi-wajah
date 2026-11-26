@extends('layouts.app')
@section('title', 'Presensi Wajah')
@section('content')
<style>
    .kiosk { background: #0b1f1e; color: #e6fffb; border-radius: 14px; display: flex; flex-direction: column; align-items: center; padding: 1.5rem 1rem; }
    .kiosk .jam { font-size: 2.6rem; font-weight: 700; letter-spacing: .03em; font-variant-numeric: tabular-nums; color: #fff; }
    .kiosk .tgl { opacity: .75; margin-bottom: 1rem; }
    .kiosk .modebar { display: flex; flex-wrap: wrap; justify-content: center; gap: .75rem; margin: .4rem 0 .8rem; }
    .kiosk .modebar button { padding: .65rem 1.6rem; border-radius: 999px; border: 2px solid #14b8a6; background: transparent; color: #99f6e4; font-size: 1.05rem; cursor: pointer; font-family: inherit; }
    .kiosk .modebar button.on { background: #14b8a6; color: #04302c; font-weight: 700; }
    .kiosk .camera { position: relative; width: min(640px, 100%); aspect-ratio: 4/3; background: #000; border-radius: 14px; overflow: hidden; border: 4px solid #14b8a6; transition: border-color .2s; margin-top: .4rem; }
    .kiosk .camera.success { border-color: #22c55e; }
    .kiosk .camera.fail { border-color: #ef4444; }
    .kiosk .camera.warn { border-color: #f59e0b; }
    .kiosk .camera video { width: 100%; height: 100%; object-fit: cover; transform: scaleX(-1); }
    .kiosk .overlay { position: absolute; left: 0; right: 0; bottom: 0; padding: 1rem; background: linear-gradient(transparent, rgba(0,0,0,.8)); text-align: center; }
    .kiosk .overlay b { display: block; font-size: 1.4rem; }
    .kiosk .statusline { margin-top: 1rem; font-size: 1.1rem; min-height: 1.7rem; text-align: center; }
</style>

<div class="topbar">
    <div>
        <h1>Presensi Wajah</h1>
        <div class="muted">Pilih siswa atau guru, pilih datang atau pulang, lalu hadapkan wajah ke kamera.</div>
    </div>
</div>

<div class="kiosk">
    <div class="jam" id="jam">--:--:--</div>
    <div class="tgl" id="tgl"></div>

    <div class="modebar">
        <button type="button" id="btn-siswa" class="on">Presensi Siswa</button>
        <button type="button" id="btn-guru">Presensi Guru</button>
    </div>
    <div class="modebar">
        <button type="button" id="btn-datang" class="on">Datang</button>
        <button type="button" id="btn-pulang">Pulang</button>
    </div>

    <div class="camera" id="camera">
        <video id="video" autoplay playsinline muted></video>
        <div class="overlay" id="overlay" hidden><b id="ov-nama"></b><span id="ov-info"></span></div>
    </div>
    <canvas id="canvas" hidden></canvas>
    <div class="statusline" id="status">Menyalakan kamera&hellip;</div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const URL_PROSES = @json(route('admin.presensi.proses'));
    const CSRF = @json(csrf_token());
    const INTERVAL = 1500;   // jeda antar pengambilan gambar (ms)
    const TITIK = '•';  // pemisah info hasil

    const $ = id => document.getElementById(id);
    const video = $('video'), canvas = $('canvas'), cam = $('camera'), status = $('status'), overlay = $('overlay');
    let jenis = 'datang', peran = 'siswa', sibuk = false, jeda = 0, berhenti = false;

    // --- jam ---
    const hari = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
    const bulan = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    const pad = n => String(n).padStart(2, '0');
    setInterval(() => {
        const d = new Date();
        $('jam').textContent = `${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;
        $('tgl').textContent = `${hari[d.getDay()]}, ${d.getDate()} ${bulan[d.getMonth()]} ${d.getFullYear()}`;
    }, 500);

    // --- pilihan peran (siswa/guru) dan jenis (datang/pulang) ---
    function segarkan() {
        $('btn-siswa').classList.toggle('on', peran === 'siswa');
        $('btn-guru').classList.toggle('on', peran === 'guru');
        $('btn-datang').classList.toggle('on', jenis === 'datang');
        $('btn-pulang').classList.toggle('on', jenis === 'pulang');
        tampil('', 'Arahkan wajah ke kamera untuk presensi ' + jenis + ' ' + peran + '.');
    }
    $('btn-siswa').onclick = () => { peran = 'siswa'; segarkan(); };
    $('btn-guru').onclick = () => { peran = 'guru'; segarkan(); };
    $('btn-datang').onclick = () => { jenis = 'datang'; segarkan(); };
    $('btn-pulang').onclick = () => { jenis = 'pulang'; segarkan(); };

    function tampil(kelas, teks, hasil) {
        cam.className = 'camera' + (kelas ? ' ' + kelas : '');
        status.textContent = teks || '';
        if (hasil) {
            $('ov-nama').textContent = hasil.nama || '';
            $('ov-info').textContent = hasil.info || '';
            overlay.hidden = false;
        } else {
            overlay.hidden = true;
        }
    }

    // --- kamera ---
    async function mulaiKamera() {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            tampil('fail', 'Kamera tidak dapat diakses. Buka halaman lewat HTTPS atau localhost.');
            return false;
        }
        try {
            const stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: 'user', width: { ideal: 640 }, height: { ideal: 480 } },
                audio: false,
            });
            video.srcObject = stream;
            await video.play();
            return true;
        } catch (e) {
            tampil('fail', 'Izin kamera ditolak atau kamera tidak ditemukan.');
            return false;
        }
    }

    // matikan kamera saat halaman ditinggalkan
    window.addEventListener('pagehide', () => {
        const s = video.srcObject;
        if (s) s.getTracks().forEach(t => t.stop());
    });

    // --- siklus: ambil gambar -> kirim -> tampilkan hasil ---
    async function siklus() {
        if (berhenti || sibuk || Date.now() < jeda || video.readyState < 2) return;
        sibuk = true;
        try {
            const lebar = 480, tinggi = Math.round(480 * video.videoHeight / video.videoWidth);
            canvas.width = lebar;
            canvas.height = tinggi;
            canvas.getContext('2d').drawImage(video, 0, 0, lebar, tinggi);
            const image = canvas.toDataURL('image/jpeg', 0.85);

            const res = await fetch(URL_PROSES, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: JSON.stringify({ peran, jenis, image }),
            });

            // sesi login habis (dialihkan ke halaman login) atau token kedaluwarsa
            if (res.redirected || res.status === 401 || res.status === 419) {
                berhenti = true;
                tampil('fail', 'Sesi berakhir. Muat ulang halaman lalu login kembali.');
                return;
            }
            if (res.status === 429) {
                tampil('fail', 'Terlalu banyak permintaan. Tunggu sebentar.');
                jeda = Date.now() + 5000;
                return;
            }

            const d = await res.json().catch(() => ({ status: 'error', message: 'Respons server tidak valid.' }));
            tanggapi(d);
        } catch (e) {
            tampil('fail', 'Tidak dapat terhubung ke server presensi.');
            jeda = Date.now() + 3000;
        } finally {
            sibuk = false;
        }
    }

    function tanggapi(d) {
        const tahan = ms => { jeda = Date.now() + ms; setTimeout(segarkan, ms); };
        switch (d.status) {
            case 'success':
                tampil('success', d.message, { nama: d.nama, info: [d.peran, d.kelas, d.waktu, d.status_presensi].filter(Boolean).join(' ' + TITIK + ' ') });
                tahan(4500);
                break;
            case 'duplicate': case 'no_arrival': case 'no_schedule':
                tampil('warn', d.message, d.nama ? { nama: d.nama, info: d.message } : null);
                tahan(4000);
                break;
            case 'unknown':
                tampil('fail', d.message);
                tahan(2500);
                break;
            case 'no_face': case 'multiple_faces':
                tampil('', d.message);
                break;
            default:
                tampil('fail', d.message || 'Terjadi kesalahan.');
                tahan(3000);
        }
    }

    mulaiKamera().then(ok => {
        if (!ok) return;
        segarkan();
        setInterval(siklus, INTERVAL);
    });
})();
</script>
@endpush

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Presensi Wajah - SMK Ar Rahmah Bantul</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; }
        .kiosk { min-height: 100vh; background: #0b1f1e; color: #e6fffb; display: flex; flex-direction: column; align-items: center; padding: 1.5rem 1rem; }
        .kiosk h1 { color: #fff; text-align: center; margin: 0 0 .5rem; font-size: 1.5rem; }
        .jam { font-size: 2.6rem; font-weight: 700; letter-spacing: .03em; font-variant-numeric: tabular-nums; }
        .tgl { opacity: .75; margin-bottom: 1rem; }
        .modebar { display: flex; gap: .75rem; margin: .5rem 0 1rem; }
        .modebar button { padding: .7rem 1.8rem; border-radius: 999px; border: 2px solid #14b8a6; background: transparent; color: #99f6e4; font-size: 1.05rem; cursor: pointer; font-family: inherit; }
        .modebar button.on { background: #14b8a6; color: #04302c; font-weight: 700; }
        .camera { position: relative; width: min(640px, 100%); aspect-ratio: 4/3; background: #000; border-radius: 14px; overflow: hidden; border: 4px solid #14b8a6; transition: border-color .2s; }
        .camera.success { border-color: #22c55e; }
        .camera.fail { border-color: #ef4444; }
        .camera.warn { border-color: #f59e0b; }
        .camera video { width: 100%; height: 100%; object-fit: cover; transform: scaleX(-1); }
        .overlay { position: absolute; left: 0; right: 0; bottom: 0; padding: 1rem; background: linear-gradient(transparent, rgba(0,0,0,.8)); text-align: center; }
        .overlay b { display: block; font-size: 1.4rem; }
        .statusline { margin-top: 1rem; font-size: 1.1rem; min-height: 1.7rem; text-align: center; }
        .links { margin-top: auto; padding-top: 1.5rem; font-size: .85rem; opacity: .7; }
        .links a { color: #99f6e4; }
    </style>
</head>
<body>
<div class="kiosk">
    <h1>Presensi Wajah<br><span style="font-weight:400;font-size:1rem;opacity:.8">SMK Ar Rahmah Bantul</span></h1>
    <div class="jam" id="jam">--:--:--</div>
    <div class="tgl" id="tgl"></div>

    <div class="modebar">
        <button type="button" id="btn-datang" class="on">Presensi Datang</button>
        <button type="button" id="btn-pulang">Presensi Pulang</button>
    </div>

    <div class="camera" id="camera">
        <video id="video" autoplay playsinline muted></video>
        <div class="overlay" id="overlay" hidden><b id="ov-nama"></b><span id="ov-info"></span></div>
    </div>
    <canvas id="canvas" hidden></canvas>
    <div class="statusline" id="status">Menyalakan kamera…</div>

    <div class="links">
        Guru/Admin: <a href="{{ route('login') }}">Masuk dashboard</a>
    </div>
</div>

<script>
(() => {
    const URL_PROSES = @json(route('kiosk.proses'));
    const CSRF = document.querySelector('meta[name=csrf-token]').content;
    const INTERVAL = 1500;   // jeda antar pengambilan gambar (ms)

    const $ = id => document.getElementById(id);
    const video = $('video'), canvas = $('canvas'), cam = $('camera'), status = $('status'), overlay = $('overlay');
    let jenis = 'datang', sibuk = false, jeda = 0;

    // --- jam ---
    const hari = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
    const bulan = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    const pad = n => String(n).padStart(2, '0');
    setInterval(() => {
        const d = new Date();
        $('jam').textContent = `${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;
        $('tgl').textContent = `${hari[d.getDay()]}, ${d.getDate()} ${bulan[d.getMonth()]} ${d.getFullYear()}`;
    }, 500);

    // --- pilih datang / pulang ---
    function pilih(j) {
        jenis = j;
        $('btn-datang').classList.toggle('on', j === 'datang');
        $('btn-pulang').classList.toggle('on', j === 'pulang');
        tampil('', 'Arahkan wajah ke kamera untuk presensi ' + j + '.');
    }
    $('btn-datang').onclick = () => pilih('datang');
    $('btn-pulang').onclick = () => pilih('pulang');

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

    // --- siklus: ambil gambar -> kirim -> tampilkan hasil ---
    async function siklus() {
        if (sibuk || Date.now() < jeda || video.readyState < 2) return;
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
                body: JSON.stringify({ jenis, image }),
            });
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
        const tahan = ms => { jeda = Date.now() + ms; setTimeout(() => pilih(jenis), ms); };
        switch (d.status) {
            case 'success':
                tampil('success', d.message, { nama: d.nama, info: `${d.peran}${d.kelas ? ' • ' + d.kelas : ''} • ${d.waktu} • ${d.status_presensi}` });
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
        pilih('datang');
        setInterval(siklus, INTERVAL);
    });
})();
</script>
</body>
</html>
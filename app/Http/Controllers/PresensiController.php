<?php

namespace App\Http\Controllers;

use App\Models\JadwalPresensi;
use App\Models\Presensi;
use App\Models\Siswa;
use App\Services\FaceService;
use App\Services\PencocokWajah;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/** Presensi wajah datang/pulang lewat layar kiosk (tanpa login). */
class PresensiController extends Controller
{
    private const HARI = [0 => 'Minggu', 1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu'];

    public function __construct(private FaceService $face, private PencocokWajah $pencocok)
    {
    }

    public function kiosk()
    {
        return view('presensi.kiosk');
    }

    /** Menerima satu gambar dari kamera, mengenali wajah, lalu mencatat presensi. */
    public function proses(Request $request): JsonResponse
    {
        $data = $request->validate([
            'jenis' => 'required|in:datang,pulang',
            'image' => 'required|string',
        ]);

        $binary = base64_decode(preg_replace('#^data:image/\w+;base64,#', '', $data['image']), true);
        if ($binary === false || $binary === '' || strlen($binary) > 4 * 1024 * 1024) {
            return $this->balas('error', 'Citra tidak valid.', 422);
        }

        // 1. Deteksi wajah dan ekstraksi encoding (Flask)
        $hasil = $this->face->encode($binary);
        if (! $hasil['ok']) {
            return match ($hasil['code']) {
                'no_face'        => $this->balas('no_face', 'Arahkan wajah ke kamera.'),
                'multiple_faces' => $this->balas('multiple_faces', 'Hanya satu orang di depan kamera.'),
                'service_down', 'service_error' => $this->balas('error', $hasil['message'], 503),
                default          => $this->balas('error', $hasil['message'], 422),
            };
        }

        // 2. Pencocokan dengan dataset
        $cocok = $this->pencocok->cocokkan($hasil['encoding']);
        if ($cocok === null) {
            return $this->balas('unknown', 'Wajah tidak dikenali. Presensi tidak dicatat.');
        }

        // 3. Cek jadwal hari ini
        $sekarang = Carbon::now();
        $jadwal = JadwalPresensi::where('peran', $cocok['peran'])
            ->where('hari', self::HARI[$sekarang->dayOfWeek])
            ->where('status', 'aktif')
            ->first();

        if (! $jadwal) {
            return $this->balas('no_schedule', 'Tidak ada jadwal presensi aktif hari ini.', 200, $cocok);
        }

        // 4. Catat presensi
        try {
            return DB::transaction(fn () => $this->catat($cocok, $data['jenis'], $sekarang, $jadwal));
        } catch (\Throwable $e) {
            Log::error('Gagal mencatat presensi', ['pesan' => $e->getMessage()]);

            return $this->balas('error', 'Presensi gagal disimpan. Coba lagi.', 500);
        }
    }

    private function catat(array $cocok, string $jenis, Carbon $sekarang, JadwalPresensi $jadwal): JsonResponse
    {
        $kolom   = $cocok['peran'] === 'siswa' ? 'id_siswa' : 'id_guru';
        $tanggal = $sekarang->toDateString();
        $jam     = $sekarang->format('H:i:s');

        $presensi = Presensi::where($kolom, $cocok['id'])->where('tanggal', $tanggal)->lockForUpdate()->first();

        if ($jenis === 'datang') {
            if ($presensi && $presensi->jam_masuk) {
                return $this->balas('duplicate', 'Sudah presensi datang pukul '.substr($presensi->jam_masuk, 0, 5).'.', 200, $cocok);
            }

            $batas  = Carbon::parse($tanggal.' '.$jadwal->jam_masuk)->addMinutes(config('face.toleransi_menit'));
            $status = $sekarang->greaterThan($batas) ? 'Terlambat' : 'Hadir';

            Presensi::create([
                $kolom              => $cocok['id'],
                'tanggal'           => $tanggal,
                'jam_masuk'         => $jam,
                'jenis_presensi'    => 'datang',
                'status'            => $status,
                'confidence'        => $cocok['confidence'],
                'metode_verifikasi' => 'face_recognition',
            ]);
        } else {
            if (! $presensi || ! $presensi->jam_masuk) {
                return $this->balas('no_arrival', 'Belum presensi datang hari ini.', 200, $cocok);
            }
            if ($presensi->jam_pulang) {
                return $this->balas('duplicate', 'Sudah presensi pulang pukul '.substr($presensi->jam_pulang, 0, 5).'.', 200, $cocok);
            }

            $presensi->update([
                'jam_pulang'     => $jam,
                'jenis_presensi' => 'pulang',
                'confidence'     => $cocok['confidence'],
            ]);
            $status = $presensi->status;
        }

        $kelas = null;
        if ($cocok['peran'] === 'siswa') {
            $kelas = Siswa::with('kelas')->find($cocok['id'])?->kelas?->nama_kelas;
        }

        return response()->json([
            'status'          => 'success',
            'message'         => $jenis === 'datang' ? 'Presensi datang berhasil.' : 'Presensi pulang berhasil.',
            'nama'            => $cocok['nama'],
            'peran'           => ucfirst($cocok['peran']),
            'kelas'           => $kelas,
            'jenis'           => $jenis,
            'waktu'           => $sekarang->format('H:i:s'),
            'status_presensi' => $status,
        ]);
    }

    private function balas(string $status, string $pesan, int $kode = 200, ?array $cocok = null): JsonResponse
    {
        return response()->json([
            'status'  => $status,
            'message' => $pesan,
            'nama'    => $cocok['nama'] ?? null,
        ], $kode);
    }
}
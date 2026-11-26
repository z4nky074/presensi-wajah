<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\Presensi;
use Carbon\Carbon;
use Illuminate\Http\Request;

/** Rekap presensi siswa dan guru untuk dashboard admin dan guru (hanya melihat). */
class RekapController extends Controller
{
    /** Dashboard admin/guru: ringkasan presensi hari ini. */
    public function dashboard(Request $request)
    {
        $role    = $request->session()->get('auth_role');
        $tanggal = Carbon::today()->toDateString();

        $hariIni = Presensi::with(['siswa.kelas', 'guru'])->where('tanggal', $tanggal);

        $ringkasan = [
            'siswa_hadir'     => (clone $hariIni)->whereNotNull('id_siswa')->where('status', 'Hadir')->count(),
            'siswa_terlambat' => (clone $hariIni)->whereNotNull('id_siswa')->where('status', 'Terlambat')->count(),
            'guru_hadir'      => (clone $hariIni)->whereNotNull('id_guru')->where('status', 'Hadir')->count(),
            'guru_terlambat'  => (clone $hariIni)->whereNotNull('id_guru')->where('status', 'Terlambat')->count(),
        ];

        $terbaru = (clone $hariIni)->orderByDesc('updated_at')->orderByDesc('id_presensi')->limit(8)->get();

        return view($role === 'admin' ? 'admin.dashboard' : 'guru.dashboard', [
            'ringkasan' => $ringkasan,
            'terbaru'   => $terbaru,
            'tanggal'   => $tanggal,
        ]);
    }

    /** Daftar presensi dengan filter tanggal, peran, kelas, dan status. */
    public function index(Request $request)
    {
        $filter = $request->validate([
            'dari'     => 'nullable|date_format:Y-m-d',
            'sampai'   => 'nullable|date_format:Y-m-d',
            'peran'    => 'nullable|in:siswa,guru',
            'id_kelas' => 'nullable|integer',
            'status'   => 'nullable|in:Hadir,Terlambat',
        ]);

        $hariIni = Carbon::today()->toDateString();
        $dari    = $filter['dari'] ?? $hariIni;
        $sampai  = $filter['sampai'] ?? $dari;
        if ($sampai < $dari) {
            [$dari, $sampai] = [$sampai, $dari];
        }

        $query = Presensi::with(['siswa.kelas', 'guru'])
            ->whereBetween('tanggal', [$dari, $sampai])
            ->orderByDesc('tanggal')
            ->orderBy('jam_masuk');

        if (($filter['peran'] ?? null) === 'siswa') {
            $query->whereNotNull('id_siswa');
        } elseif (($filter['peran'] ?? null) === 'guru') {
            $query->whereNotNull('id_guru');
        }

        if (! empty($filter['id_kelas'])) {
            $query->whereHas('siswa', fn ($q) => $q->where('id_kelas', $filter['id_kelas']));
        }

        if (! empty($filter['status'])) {
            $query->where('status', $filter['status']);
        }

        return view('rekap.index', [
            'daftar' => $query->paginate(25)->withQueryString(),
            'kelas'  => Kelas::orderBy('nama_kelas')->get(),
            'dari'   => $dari,
            'sampai' => $sampai,
            'filter' => $filter,
        ]);
    }
}

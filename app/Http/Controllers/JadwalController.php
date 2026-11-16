<?php

namespace App\Http\Controllers;

use App\Models\JadwalPresensi;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class JadwalController extends Controller
{
    private const HARI = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];

    public function index()
    {
        $urutan = array_flip(self::HARI);
        $daftar = JadwalPresensi::all()->sortBy(fn ($j) => $j->peran.$urutan[$j->hari]);

        return view('jadwal.index', ['daftar' => $daftar]);
    }

    public function create()
    {
        $jadwal = new JadwalPresensi(['status' => 'aktif', 'jam_masuk' => '07:00', 'jam_pulang' => '15:00']);

        return view('jadwal.form', ['jadwal' => $jadwal, 'hari' => self::HARI]);
    }

    public function store(Request $request)
    {
        JadwalPresensi::create($this->validasi($request));

        return redirect()->route('jadwal.index')->with('success', 'Jadwal presensi ditambahkan.');
    }

    public function edit(JadwalPresensi $jadwal)
    {
        // tampilkan HH:MM saja (database menyimpan HH:MM:SS)
        $jadwal->jam_masuk = substr($jadwal->jam_masuk, 0, 5);
        $jadwal->jam_pulang = substr($jadwal->jam_pulang, 0, 5);

        return view('jadwal.form', ['jadwal' => $jadwal, 'hari' => self::HARI]);
    }

    public function update(Request $request, JadwalPresensi $jadwal)
    {
        $jadwal->update($this->validasi($request, $jadwal));

        return redirect()->route('jadwal.index')->with('success', 'Jadwal presensi diperbarui.');
    }

    public function destroy(JadwalPresensi $jadwal)
    {
        $jadwal->delete();

        return back()->with('success', 'Jadwal presensi dihapus.');
    }

    private function validasi(Request $request, ?JadwalPresensi $jadwal = null): array
    {
        return $request->validate([
            'peran' => 'required|in:siswa,guru',
            // satu peran hanya boleh punya satu jadwal per hari (cek konflik)
            'hari' => ['required', Rule::in(self::HARI),
                Rule::unique('jadwal_presensi', 'hari')
                    ->where('peran', $request->input('peran'))
                    ->ignore($jadwal?->id_jadwal, 'id_jadwal')],
            'jam_masuk'  => 'required|date_format:H:i',
            'jam_pulang' => 'required|date_format:H:i|after:jam_masuk',
            'status'     => 'required|in:aktif,nonaktif',
        ], [
            'required'    => ':attribute wajib diisi.',
            'in'          => ':attribute tidak valid.',
            'date_format' => 'Format :attribute tidak valid.',
            'after'       => 'Jam pulang harus setelah jam masuk.',
            'hari.unique' => 'Jadwal untuk peran dan hari tersebut sudah ada.',
        ]);
    }
}
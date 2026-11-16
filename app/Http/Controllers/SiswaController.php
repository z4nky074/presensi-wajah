<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\Siswa;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SiswaController extends Controller
{
    public function index(Request $request)
    {
        $query = Siswa::with('kelas')->orderBy('nama');

        if ($request->filled('id_kelas')) {
            $query->where('id_kelas', $request->id_kelas);
        }

        if ($request->filled('q')) {
            $kata = '%'.$request->q.'%';
            $query->where(fn ($w) => $w->where('nama', 'like', $kata)->orWhere('nis', 'like', $kata));
        }

        return view('siswa.index', [
            'daftar' => $query->paginate(20)->withQueryString(),
            'kelas'  => Kelas::orderBy('nama_kelas')->get(),
        ]);
    }

    public function create()
    {
        return view('siswa.form', [
            'siswa' => new Siswa(),
            'kelas' => Kelas::orderBy('nama_kelas')->get(),
        ]);
    }

    public function store(Request $request)
    {
        Siswa::create($this->validasi($request));

        return redirect()->route('admin.siswa.index')->with('success', 'Data siswa ditambahkan.');
    }

    public function edit(Siswa $siswa)
    {
        return view('siswa.form', [
            'siswa' => $siswa,
            'kelas' => Kelas::orderBy('nama_kelas')->get(),
        ]);
    }

    public function update(Request $request, Siswa $siswa)
    {
        $siswa->update($this->validasi($request, $siswa));

        return redirect()->route('admin.siswa.index')->with('success', 'Data siswa diperbarui.');
    }

    public function destroy(Siswa $siswa)
    {
        $siswa->delete();

        return back()->with('success', 'Data siswa dihapus.');
    }

    private function validasi(Request $request, ?Siswa $siswa = null): array
    {
        return $request->validate([
            'nis' => ['required', 'string', 'max:30',
                Rule::unique('siswa', 'nis')->ignore($siswa?->id_siswa, 'id_siswa')],
            'nama'          => 'required|string|max:150',
            'jenis_kelamin' => 'required|in:L,P',
            'id_kelas'      => 'required|exists:kelas,id_kelas',
        ], [
            'required' => ':attribute wajib diisi.',
            'unique'   => ':attribute sudah digunakan.',
            'in'       => ':attribute tidak valid.',
            'exists'   => ':attribute tidak ditemukan.',
            'max'      => ':attribute terlalu panjang.',
        ]);
    }
}
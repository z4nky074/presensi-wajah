<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class KelasController extends Controller
{
    public function index()
    {
        $daftar = Kelas::withCount('siswa')->orderBy('tingkat')->orderBy('nama_kelas')->get();

        return view('kelas.index', ['daftar' => $daftar]);
    }

    public function create()
    {
        return view('kelas.form', ['kelas' => new Kelas()]);
    }

    public function store(Request $request)
    {
        Kelas::create($this->validasi($request));

        return redirect()->route('admin.kelas.index')->with('success', 'Data kelas ditambahkan.');
    }

    public function edit(Kelas $kelas)
    {
        return view('kelas.form', ['kelas' => $kelas]);
    }

    public function update(Request $request, Kelas $kelas)
    {
        $kelas->update($this->validasi($request, $kelas));

        return redirect()->route('admin.kelas.index')->with('success', 'Data kelas diperbarui.');
    }

    public function destroy(Kelas $kelas)
    {
        if ($kelas->siswa()->exists()) {
            return back()->with('error', 'Kelas masih memiliki siswa. Pindahkan atau hapus siswa terlebih dahulu.');
        }

        $kelas->delete();

        return back()->with('success', 'Data kelas dihapus.');
    }

    private function validasi(Request $request, ?Kelas $kelas = null): array
    {
        return $request->validate([
            'nama_kelas' => ['required', 'string', 'max:50',
                Rule::unique('kelas', 'nama_kelas')->ignore($kelas?->id_kelas, 'id_kelas')],
            'jurusan'    => 'required|string|max:100',
            'tingkat'    => 'required|in:X,XI,XII',
            'wali_kelas' => 'nullable|string|max:100',
        ], [
            'required' => ':attribute wajib diisi.',
            'unique'   => ':attribute sudah digunakan.',
            'in'       => ':attribute tidak valid.',
            'max'      => ':attribute terlalu panjang.',
        ]);
    }
}
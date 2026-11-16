<?php

namespace App\Http\Controllers;

use App\Models\Guru;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GuruController extends Controller
{
    public function index(Request $request)
    {
        $query = Guru::orderBy('nama');

        if ($request->filled('q')) {
            $kata = '%'.$request->q.'%';
            $query->where(fn ($w) => $w->where('nama', 'like', $kata)->orWhere('nip', 'like', $kata));
        }

        return view('guru_crud.index', ['daftar' => $query->paginate(20)->withQueryString()]);
    }

    public function create()
    {
        return view('guru_crud.form', ['guru' => new Guru()]);
    }

    public function store(Request $request)
    {
        Guru::create($this->validasi($request));

        return redirect()->route('admin.guru.index')->with('success', 'Data guru ditambahkan.');
    }

    public function edit(Guru $guru)
    {
        return view('guru_crud.form', ['guru' => $guru]);
    }

    public function update(Request $request, Guru $guru)
    {
        $data = $this->validasi($request, $guru);

        // password dikosongkan = password lama tetap dipakai
        if (empty($data['password'])) {
            unset($data['password']);
        }

        $guru->update($data);

        return redirect()->route('admin.guru.index')->with('success', 'Data guru diperbarui.');
    }

    public function destroy(Guru $guru)
    {
        $guru->delete();

        return back()->with('success', 'Data guru dihapus.');
    }

    private function validasi(Request $request, ?Guru $guru = null): array
    {
        return $request->validate([
            'nip' => ['nullable', 'string', 'max:30',
                Rule::unique('guru', 'nip')->ignore($guru?->id_guru, 'id_guru')],
            'nama' => 'required|string|max:150',
            'username' => ['required', 'alpha_dash', 'max:50',
                Rule::unique('guru', 'username')->ignore($guru?->id_guru, 'id_guru'),
                Rule::unique('admin', 'username')],
            'password' => [$guru ? 'nullable' : 'required', 'string', 'min:6', 'max:100'],
            'email'    => 'nullable|email|max:150',
            'no_hp'    => 'nullable|string|max:20',
        ], [
            'required'   => ':attribute wajib diisi.',
            'unique'     => ':attribute sudah digunakan.',
            'min'        => ':attribute minimal :min karakter.',
            'max'        => ':attribute terlalu panjang.',
            'email'      => 'Format :attribute tidak valid.',
            'alpha_dash' => ':attribute hanya boleh huruf, angka, strip, dan garis bawah.',
        ]);
    }
}
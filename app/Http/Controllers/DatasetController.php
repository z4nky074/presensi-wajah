<?php

namespace App\Http\Controllers;

use App\Models\DatasetWajah;
use App\Models\Guru;
use App\Models\Siswa;
use App\Services\FaceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DatasetController extends Controller
{
    public function __construct(private FaceService $face)
    {
    }

    public function index(Request $request)
    {
        $query = DatasetWajah::with(['siswa.kelas', 'guru'])->orderByDesc('tanggal_upload');

        if ($request->input('peran') === 'siswa') {
            $query->whereNotNull('id_siswa');
        } elseif ($request->input('peran') === 'guru') {
            $query->whereNotNull('id_guru');
        }

        return view('dataset.index', [
            'daftar' => $query->paginate(18)->withQueryString(),
            'siswa'  => Siswa::with('kelas')->orderBy('nama')->get(),
            'guru'   => Guru::orderBy('nama')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'peran'    => 'required|in:siswa,guru',
            'id_siswa' => 'required_if:peran,siswa|nullable|exists:siswa,id_siswa',
            'id_guru'  => 'required_if:peran,guru|nullable|exists:guru,id_guru',
            'foto'     => 'required|image|mimes:jpg,jpeg,png|max:4096',
        ], [
            'required'    => ':attribute wajib diisi.',
            'required_if' => ':attribute wajib dipilih.',
            'exists'      => ':attribute tidak ditemukan.',
            'image'       => 'Berkas harus berupa gambar.',
            'mimes'       => 'Foto harus berformat JPG atau PNG.',
            'max'         => 'Ukuran foto maksimal 4 MB.',
        ]);

        $berkas = $request->file('foto');
        $binary = file_get_contents($berkas->getRealPath());

        // 1. Minta Flask mengambil face encoding
        $hasil = $this->face->encode($binary);

        if (! $hasil['ok']) {
            $pesan = match ($hasil['code']) {
                'no_face'        => 'Wajah tidak ditemukan pada foto. Gunakan foto yang menampilkan wajah dengan jelas.',
                'multiple_faces' => 'Foto memuat lebih dari satu wajah. Gunakan foto dengan satu wajah.',
                default          => $hasil['message'],
            };

            return back()->withInput()->with('error', $pesan);
        }

        // 2. Simpan foto di penyimpanan PRIVAT (bukan folder public)
        $kolom = $data['peran'] === 'siswa' ? 'id_siswa' : 'id_guru';
        $ext   = $berkas->guessExtension() ?: 'jpg';
        $path  = 'dataset/'.$data['peran'].'_'.$data[$kolom].'_'.now()->format('YmdHis').'_'.bin2hex(random_bytes(3)).'.'.$ext;
        Storage::disk('local')->put($path, $binary);

        // 3. Simpan encoding ke database
        DatasetWajah::create([
            $kolom           => $data[$kolom],
            'foto'           => $path,
            'face_encoding'  => json_encode($hasil['encoding']),
            'tanggal_upload' => now(),
        ]);

        return redirect()->route('admin.dataset.index')->with('success', 'Dataset wajah berhasil ditambahkan.');
    }

    /** Foto hanya bisa dilihat admin yang login (route berada di grup role:admin). */
    public function foto(DatasetWajah $dataset)
    {
        abort_unless(Storage::disk('local')->exists($dataset->foto), 404);

        return Storage::disk('local')->response($dataset->foto);
    }

    public function destroy(DatasetWajah $dataset)
    {
        Storage::disk('local')->delete($dataset->foto);
        $dataset->delete();

        return back()->with('success', 'Dataset wajah dihapus.');
    }
}
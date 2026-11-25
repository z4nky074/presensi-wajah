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
    /** Jumlah foto minimal per orang agar dataset dianggap lengkap. */
    private const MIN_FOTO = 3;

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

        $siswa = Siswa::with('kelas')->orderBy('nama')->get();
        $guru  = Guru::orderBy('nama')->get();

        // Jumlah foto per orang
        $jumlahSiswa = DatasetWajah::whereNotNull('id_siswa')
            ->selectRaw('id_siswa, COUNT(*) as n')->groupBy('id_siswa')->pluck('n', 'id_siswa');
        $jumlahGuru = DatasetWajah::whereNotNull('id_guru')
            ->selectRaw('id_guru, COUNT(*) as n')->groupBy('id_guru')->pluck('n', 'id_guru');

        // Daftar orang yang fotonya masih kurang dari batas minimal
        $kurang = collect();
        foreach ($siswa as $s) {
            $n = (int) ($jumlahSiswa[$s->id_siswa] ?? 0);
            if ($n < self::MIN_FOTO) {
                $kurang->push(['nama' => $s->nama, 'peran' => 'Siswa', 'kelas' => $s->kelas->nama_kelas ?? '-', 'jumlah' => $n]);
            }
        }
        foreach ($guru as $g) {
            $n = (int) ($jumlahGuru[$g->id_guru] ?? 0);
            if ($n < self::MIN_FOTO) {
                $kurang->push(['nama' => $g->nama, 'peran' => 'Guru', 'kelas' => '-', 'jumlah' => $n]);
            }
        }

        $total = $siswa->count() + $guru->count();

        return view('dataset.index', [
            'daftar'      => $query->paginate(18)->withQueryString(),
            'siswa'       => $siswa,
            'guru'        => $guru,
            'jumlahSiswa' => $jumlahSiswa,
            'jumlahGuru'  => $jumlahGuru,
            'kurang'      => $kurang->sortBy('jumlah')->values(),
            'total'       => $total,
            'lengkap'     => $total - $kurang->count(),
            'min'         => self::MIN_FOTO,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'peran'    => 'required|in:siswa,guru',
            'id_siswa' => 'required_if:peran,siswa|nullable|exists:siswa,id_siswa',
            'id_guru'  => 'required_if:peran,guru|nullable|exists:guru,id_guru',
            'foto'     => 'required|array|min:1|max:10',
            'foto.*'   => 'image|mimes:jpg,jpeg,png|max:4096',
        ], [
            'peran.required'      => 'Jenis pemilik wajib dipilih.',
            'id_siswa.required_if' => 'Siswa wajib dipilih.',
            'id_guru.required_if'  => 'Guru wajib dipilih.',
            'exists'              => 'Pemilik tidak ditemukan.',
            'foto.required'       => 'Pilih minimal satu foto.',
            'foto.max'            => 'Maksimal 10 foto sekaligus.',
            'foto.*.image'        => 'Berkas harus berupa gambar.',
            'foto.*.mimes'        => 'Foto harus berformat JPG atau PNG.',
            'foto.*.max'          => 'Ukuran tiap foto maksimal 4 MB.',
        ]);

        $kolom    = $data['peran'] === 'siswa' ? 'id_siswa' : 'id_guru';
        $berhasil = 0;
        $gagal    = [];

        foreach ($request->file('foto') as $berkas) {
            $nama   = $berkas->getClientOriginalName();
            $binary = file_get_contents($berkas->getRealPath());

            // 1. Minta Flask mengambil face encoding
            $hasil = $this->face->encode($binary);

            if (! $hasil['ok']) {
                $gagal[] = $nama.' ('.$this->pesanGagal($hasil).')';

                if ($hasil['code'] === 'service_down') {
                    break; // layanan mati, percuma melanjutkan foto berikutnya
                }

                continue;
            }

            // 2. Simpan foto di penyimpanan PRIVAT
            $ext  = $berkas->guessExtension() ?: 'jpg';
            $path = 'dataset/'.$data['peran'].'_'.$data[$kolom].'_'.now()->format('YmdHis').'_'.bin2hex(random_bytes(3)).'.'.$ext;
            Storage::disk('local')->put($path, $binary);

            // 3. Simpan encoding ke database
            DatasetWajah::create([
                $kolom           => $data[$kolom],
                'foto'           => $path,
                'face_encoding'  => json_encode($hasil['encoding']),
                'tanggal_upload' => now(),
            ]);

            $berhasil++;
        }

        $redirect = redirect()->route('admin.dataset.index')
            ->withInput($request->only('peran', 'id_siswa', 'id_guru'));

        if ($berhasil > 0) {
            $redirect->with('success', $berhasil.' foto berhasil disimpan.');
        }
        if ($gagal) {
            $redirect->with('error', 'Foto gagal: '.implode('; ', $gagal));
        }

        return $redirect;
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

    private function pesanGagal(array $hasil): string
    {
        return match ($hasil['code']) {
            'no_face'        => 'wajah tidak ditemukan',
            'multiple_faces' => 'lebih dari satu wajah',
            'service_down'   => 'layanan pengenalan wajah tidak dapat dihubungi',
            default          => $hasil['message'],
        };
    }
}
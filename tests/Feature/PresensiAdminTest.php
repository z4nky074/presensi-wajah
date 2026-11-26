<?php

namespace Tests\Feature;

use App\Models\DatasetWajah;
use App\Models\Guru;
use App\Models\JadwalPresensi;
use App\Models\Kelas;
use App\Models\Presensi;
use App\Models\Siswa;
use App\Services\FaceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PresensiAdminTest extends TestCase
{
    use RefreshDatabase;

    private const HARI = [0 => 'Minggu', 1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu'];

    private Siswa $siswa;
    private Guru $guru;

    protected function setUp(): void
    {
        parent::setUp();

        // Rabu, 25 November 2026, 06:30 WIB
        Carbon::setTestNow(Carbon::parse('2026-11-25 06:30:00', 'Asia/Jakarta'));

        $kelas = Kelas::create(['nama_kelas' => 'XII RPL 1', 'jurusan' => 'RPL', 'tingkat' => 'XII']);
        $this->siswa = Siswa::create(['nis' => '1001', 'nama' => 'Siswa Uji', 'jenis_kelamin' => 'L', 'id_kelas' => $kelas->id_kelas]);
        $this->guru = Guru::create(['nama' => 'Guru Uji', 'username' => 'guruuji', 'password' => 'rahasia123']);

        DatasetWajah::create([
            'id_siswa' => $this->siswa->id_siswa, 'foto' => 'x.jpg',
            'face_encoding' => json_encode(array_fill(0, 128, 0.1)),
        ]);
        DatasetWajah::create([
            'id_guru' => $this->guru->id_guru, 'foto' => 'y.jpg',
            'face_encoding' => json_encode(array_fill(0, 128, 0.5)),
        ]);

        foreach (['siswa', 'guru'] as $peran) {
            JadwalPresensi::create(['peran' => $peran, 'hari' => 'Rabu', 'jam_masuk' => '07:00', 'jam_pulang' => '15:00', 'status' => 'aktif']);
        }
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function admin()
    {
        return $this->withSession(['auth_role' => 'admin', 'auth_id' => 1, 'auth_nama' => 'Admin Uji']);
    }

    private function gurulogin()
    {
        return $this->withSession(['auth_role' => 'guru', 'auth_id' => $this->guru->id_guru, 'auth_nama' => 'Guru Uji']);
    }

    private ?object $flaskPalsu = null;

    /**
     * Flask palsu: mengembalikan encoding bernilai konstan, atau kode galat.
     * Satu objek dipakai ulang karena Laravel menyimpan instance controller per route.
     */
    private function flask(float|string $nilai): void
    {
        if ($this->flaskPalsu === null) {
            $this->flaskPalsu = new class extends FaceService {
                public float|string $nilai = 0.0;

                public function __construct() {}

                public function encode(string $binary): array
                {
                    if (is_string($this->nilai)) {
                        return ['ok' => false, 'code' => $this->nilai, 'message' => 'galat '.$this->nilai, 'encoding' => null];
                    }

                    return ['ok' => true, 'code' => 'ok', 'message' => '', 'encoding' => array_fill(0, 128, $this->nilai)];
                }
            };
            $this->app->instance(FaceService::class, $this->flaskPalsu);
        }

        $this->flaskPalsu->nilai = $nilai;
    }

    private function kirim(string $peran, string $jenis)
    {
        return $this->admin()->postJson(route('admin.presensi.proses'), [
            'peran' => $peran,
            'jenis' => $jenis,
            'image' => 'data:image/jpeg;base64,'.base64_encode('gambar-palsu'),
        ]);
    }

    // ---- akses ----

    public function test_tamu_dialihkan_ke_login(): void
    {
        $this->get('/admin/presensi')->assertRedirect(route('login'));
        $this->post('/admin/presensi/proses')->assertRedirect(route('login'));
    }

    public function test_guru_tidak_boleh_membuka_atau_memproses_presensi(): void
    {
        $this->gurulogin()->get('/admin/presensi')->assertForbidden();
        $this->gurulogin()->postJson('/admin/presensi/proses', [])->assertForbidden();
    }

    public function test_route_lama_publik_dan_route_guru_sudah_tidak_ada(): void
    {
        $this->get('/presensi')->assertNotFound();
        $this->postJson('/presensi/proses', [])->assertNotFound();
        $this->gurulogin()->get('/guru/presensi')->assertNotFound();
    }

    public function test_admin_membuka_halaman_kamera_dengan_pilihan_siswa_dan_guru(): void
    {
        $this->admin()->get('/admin/presensi')
            ->assertOk()
            ->assertSee('Presensi Siswa')
            ->assertSee('Presensi Guru')
            ->assertSee('admin\/presensi\/proses', false); // URL di JS berbentuk JSON (garis miring di-escape)
    }

    // ---- pencatatan ----

    public function test_presensi_siswa_datang_hadir_lalu_duplikat_lalu_pulang(): void
    {
        $this->flask(0.1);

        $this->kirim('siswa', 'datang')->assertOk()
            ->assertJson(['status' => 'success', 'nama' => 'Siswa Uji', 'peran' => 'Siswa', 'kelas' => 'XII RPL 1', 'status_presensi' => 'Hadir']);
        $this->assertDatabaseCount('presensi', 1);

        $this->kirim('siswa', 'datang')->assertJson(['status' => 'duplicate']);
        $this->assertDatabaseCount('presensi', 1);

        Carbon::setTestNow(Carbon::parse('2026-11-25 15:05:00', 'Asia/Jakarta'));
        $this->kirim('siswa', 'pulang')->assertJson(['status' => 'success']);
        $this->kirim('siswa', 'pulang')->assertJson(['status' => 'duplicate']);

        $p = Presensi::first();
        $this->assertSame('06:30:00', $p->jam_masuk);
        $this->assertSame('15:05:00', $p->jam_pulang);
        $this->assertSame($this->siswa->id_siswa, $p->id_siswa);
        $this->assertNull($p->id_guru);
        $this->assertDatabaseCount('presensi', 1);
    }

    public function test_presensi_guru_terlambat_tersimpan_sebagai_guru(): void
    {
        $this->flask(0.5);
        Carbon::setTestNow(Carbon::parse('2026-11-25 07:20:00', 'Asia/Jakarta'));

        $this->kirim('guru', 'datang')->assertOk()
            ->assertJson(['status' => 'success', 'nama' => 'Guru Uji', 'peran' => 'Guru', 'status_presensi' => 'Terlambat']);

        $p = Presensi::first();
        $this->assertSame($this->guru->id_guru, $p->id_guru);
        $this->assertNull($p->id_siswa);
    }

    public function test_mode_dipilih_membatasi_pencocokan_peran(): void
    {
        // wajah siswa dipindai saat admin memilih mode guru: tidak boleh dikenali
        $this->flask(0.1);
        $this->kirim('guru', 'datang')->assertJson(['status' => 'unknown']);
        $this->assertDatabaseCount('presensi', 0);

        // wajah guru dipindai saat mode siswa
        $this->flask(0.5);
        $this->kirim('siswa', 'datang')->assertJson(['status' => 'unknown']);
        $this->assertDatabaseCount('presensi', 0);
    }

    public function test_pulang_sebelum_datang_ditolak(): void
    {
        $this->flask(0.1);
        $this->kirim('siswa', 'pulang')->assertJson(['status' => 'no_arrival']);
        $this->assertDatabaseCount('presensi', 0);
    }

    public function test_tanpa_jadwal_aktif_ditolak(): void
    {
        $this->flask(0.1);
        JadwalPresensi::where('peran', 'siswa')->update(['status' => 'nonaktif']);

        $this->kirim('siswa', 'datang')->assertOk()->assertJson(['status' => 'no_schedule']);
        $this->assertDatabaseCount('presensi', 0);
    }

    public function test_galat_flask_tidak_menyimpan_apa_pun(): void
    {
        foreach (['no_face' => 200, 'multiple_faces' => 200, 'service_down' => 503] as $kode => $http) {
            $this->flask($kode);
            $this->kirim('siswa', 'datang')->assertStatus($http)
                ->assertJson(['status' => $kode === 'service_down' ? 'error' : $kode]);
        }
        $this->assertDatabaseCount('presensi', 0);
    }

    public function test_validasi_peran_dan_jenis(): void
    {
        $this->flask(0.1);
        $this->kirim('admin', 'datang')->assertStatus(422);
        $this->kirim('siswa', 'lembur')->assertStatus(422);
        $this->admin()->postJson(route('admin.presensi.proses'), ['jenis' => 'datang', 'image' => 'x'])->assertStatus(422);
        $this->assertDatabaseCount('presensi', 0);
    }

    // ---- rekap ----

    public function test_rekap_dan_dashboard_tampil_untuk_admin_dan_guru_tetapi_tamu_ditolak(): void
    {
        Presensi::create(['id_siswa' => $this->siswa->id_siswa, 'tanggal' => '2026-11-25', 'jam_masuk' => '06:30:00', 'jenis_presensi' => 'datang', 'status' => 'Hadir', 'confidence' => 99]);
        Presensi::create(['id_guru' => $this->guru->id_guru, 'tanggal' => '2026-11-25', 'jam_masuk' => '07:20:00', 'jenis_presensi' => 'datang', 'status' => 'Terlambat', 'confidence' => 90]);

        $this->get('/rekap')->assertRedirect(route('login'));

        $this->admin()->get('/rekap')->assertOk()->assertSee('Siswa Uji')->assertSee('Guru Uji')->assertSee('XII RPL 1');
        $this->admin()->get('/admin')->assertOk()->assertSee('Siswa Uji')->assertSee('Guru Uji')->assertSee('Presensi hari ini');

        $this->flushSession();

        $this->gurulogin()->get('/rekap')->assertOk()->assertSee('Siswa Uji')->assertSee('Guru Uji')->assertSee('XII RPL 1');
        $this->gurulogin()->get('/guru')->assertOk()->assertSee('Siswa Uji')->assertSee('Guru Uji')->assertSee('Presensi hari ini');
    }

    public function test_filter_rekap_peran_status_tanggal_dan_kelas(): void
    {
        Presensi::create(['id_siswa' => $this->siswa->id_siswa, 'tanggal' => '2026-11-25', 'jam_masuk' => '06:30:00', 'jenis_presensi' => 'datang', 'status' => 'Hadir']);
        Presensi::create(['id_guru' => $this->guru->id_guru, 'tanggal' => '2026-11-25', 'jam_masuk' => '07:20:00', 'jenis_presensi' => 'datang', 'status' => 'Terlambat']);
        Presensi::create(['id_guru' => $this->guru->id_guru, 'tanggal' => '2026-11-20', 'jam_masuk' => '07:00:00', 'jenis_presensi' => 'datang', 'status' => 'Hadir']);

        $r = $this->admin()->get('/rekap?peran=guru');
        $r->assertOk()->assertSee('Guru Uji')->assertDontSee('Siswa Uji');

        $this->admin()->get('/rekap?status=Hadir')->assertSee('Siswa Uji')->assertDontSee('Guru Uji');
        $this->admin()->get('/rekap?dari=2026-11-20&sampai=2026-11-25&peran=guru')->assertSee('20-11-2026')->assertSee('25-11-2026');
        $this->admin()->get('/rekap?dari=2026-11-20&sampai=2026-11-20')->assertDontSee('Siswa Uji')->assertSee('20-11-2026');
        $this->admin()->get('/rekap?id_kelas='.$this->siswa->id_kelas)->assertSee('Siswa Uji')->assertDontSee('Guru Uji');
        $this->admin()->get('/rekap?dari=bukan-tanggal')->assertSessionHasErrors('dari');
    }

    public function test_sidebar_hanya_menampilkan_menu_kamera_untuk_admin(): void
    {
        $this->admin()->get('/admin')->assertSee(route('admin.presensi'), false);
        $this->gurulogin()->get('/guru')->assertDontSee('/admin/presensi', false)->assertSee(route('rekap.index'), false);
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin', function (Blueprint $t) {
            $t->id('id_admin');
            $t->string('nama');
            $t->string('username')->unique();
            $t->string('password');
            $t->string('email')->nullable()->unique();
            $t->timestamps();
        });

        Schema::create('guru', function (Blueprint $t) {
            $t->id('id_guru');
            $t->string('nip')->nullable()->unique();
            $t->string('nama');
            $t->string('username')->unique();
            $t->string('password');
            $t->string('email')->nullable();
            $t->string('no_hp', 20)->nullable();
            $t->timestamps();
        });

        Schema::create('kelas', function (Blueprint $t) {
            $t->id('id_kelas');
            $t->string('nama_kelas')->unique();
            $t->string('jurusan');
            $t->string('tingkat', 5);
            $t->string('wali_kelas')->nullable();
            $t->timestamps();
        });

        Schema::create('siswa', function (Blueprint $t) {
            $t->id('id_siswa');
            $t->string('nis')->unique();
            $t->string('nama');
            $t->enum('jenis_kelamin', ['L', 'P']);
            $t->foreignId('id_kelas')->constrained('kelas', 'id_kelas')->restrictOnDelete();
            $t->timestamps();
        });

        Schema::create('dataset_wajah', function (Blueprint $t) {
            $t->id('id_dataset');
            $t->foreignId('id_siswa')->nullable()->constrained('siswa', 'id_siswa')->cascadeOnDelete();
            $t->foreignId('id_guru')->nullable()->constrained('guru', 'id_guru')->cascadeOnDelete();
            $t->string('foto');
            $t->longText('face_encoding'); // JSON: 128 nilai dlib
            $t->timestamp('tanggal_upload')->useCurrent();
            $t->timestamps();
        });

        Schema::create('jadwal_presensi', function (Blueprint $t) {
            $t->id('id_jadwal');
            $t->enum('peran', ['siswa', 'guru']);
            $t->enum('hari', ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu']);
            $t->time('jam_masuk');
            $t->time('jam_pulang');
            $t->enum('status', ['aktif', 'nonaktif'])->default('aktif');
            $t->timestamps();
            $t->unique(['peran', 'hari']);
        });

        Schema::create('presensi', function (Blueprint $t) {
            $t->id('id_presensi');
            $t->foreignId('id_siswa')->nullable()->constrained('siswa', 'id_siswa')->cascadeOnDelete();
            $t->foreignId('id_guru')->nullable()->constrained('guru', 'id_guru')->cascadeOnDelete();
            $t->date('tanggal');
            $t->time('jam_masuk')->nullable();
            $t->time('jam_pulang')->nullable();
            $t->enum('jenis_presensi', ['datang', 'pulang']);
            $t->enum('status', ['Hadir', 'Terlambat']);
            $t->decimal('confidence', 5, 2)->nullable();
            $t->string('metode_verifikasi', 30)->default('face_recognition');
            $t->timestamps();

            $t->unique(['id_siswa', 'tanggal']);
            $t->unique(['id_guru', 'tanggal']);
            $t->index('tanggal');
        });
    }

    public function down(): void
    {
        foreach (['presensi', 'jadwal_presensi', 'dataset_wajah', 'siswa', 'kelas', 'guru', 'admin'] as $tabel) {
            Schema::dropIfExists($tabel);
        }
    }
};
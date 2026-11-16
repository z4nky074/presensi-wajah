<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Presensi extends Model
{
    protected $table = 'presensi';
    protected $primaryKey = 'id_presensi';
    protected $fillable = [
        'id_siswa', 'id_guru', 'tanggal', 'jam_masuk', 'jam_pulang',
        'jenis_presensi', 'status', 'confidence', 'metode_verifikasi',
    ];
    protected $casts = ['tanggal' => 'date:Y-m-d'];

    public function siswa()
    {
        return $this->belongsTo(Siswa::class, 'id_siswa', 'id_siswa');
    }

    public function guru()
    {
        return $this->belongsTo(Guru::class, 'id_guru', 'id_guru');
    }

    public function getNamaAttribute(): string
    {
        return $this->siswa?->nama ?? $this->guru?->nama ?? '-';
    }

    public function getPeranAttribute(): string
    {
        return $this->id_siswa ? 'Siswa' : 'Guru';
    }

    public function getNamaKelasAttribute(): string
    {
        return $this->siswa?->kelas?->nama_kelas ?? '-';
    }
}
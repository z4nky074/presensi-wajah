<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Siswa extends Model
{
    protected $table = 'siswa';
    protected $primaryKey = 'id_siswa';
    protected $fillable = ['nis', 'nama', 'jenis_kelamin', 'id_kelas'];

    public function kelas()
    {
        return $this->belongsTo(Kelas::class, 'id_kelas', 'id_kelas');
    }

    public function dataset()
    {
        return $this->hasMany(DatasetWajah::class, 'id_siswa', 'id_siswa');
    }

    public function presensi()
    {
        return $this->hasMany(Presensi::class, 'id_siswa', 'id_siswa');
    }
}
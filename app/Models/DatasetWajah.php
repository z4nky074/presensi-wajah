<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DatasetWajah extends Model
{
    protected $table = 'dataset_wajah';
    protected $primaryKey = 'id_dataset';
    protected $fillable = ['id_siswa', 'id_guru', 'foto', 'face_encoding', 'tanggal_upload'];
    protected $casts = ['tanggal_upload' => 'datetime'];

    public function siswa()
    {
        return $this->belongsTo(Siswa::class, 'id_siswa', 'id_siswa');
    }

    public function guru()
    {
        return $this->belongsTo(Guru::class, 'id_guru', 'id_guru');
    }

    public function getPemilikAttribute(): ?string
    {
        return $this->siswa?->nama ?? $this->guru?->nama;
    }

    public function getPeranAttribute(): string
    {
        return $this->id_siswa ? 'siswa' : 'guru';
    }
}
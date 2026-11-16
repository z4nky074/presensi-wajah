<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JadwalPresensi extends Model
{
    protected $table = 'jadwal_presensi';
    protected $primaryKey = 'id_jadwal';
    protected $fillable = ['peran', 'hari', 'jam_masuk', 'jam_pulang', 'status'];
}
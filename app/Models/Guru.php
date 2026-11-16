<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Guru extends Model
{
    protected $table = 'guru';
    protected $primaryKey = 'id_guru';
    protected $fillable = ['nip', 'nama', 'username', 'password', 'email', 'no_hp'];
    protected $hidden = ['password'];
    protected $casts = ['password' => 'hashed'];

    public function dataset()
    {
        return $this->hasMany(DatasetWajah::class, 'id_guru', 'id_guru');
    }

    public function presensi()
    {
        return $this->hasMany(Presensi::class, 'id_guru', 'id_guru');
    }
}
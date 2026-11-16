<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Admin extends Model
{
    protected $table = 'admin';
    protected $primaryKey = 'id_admin';
    protected $fillable = ['nama', 'username', 'password', 'email'];
    protected $hidden = ['password'];
    protected $casts = ['password' => 'hashed'];
}
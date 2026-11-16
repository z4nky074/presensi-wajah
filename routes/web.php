<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\KelasController;
use App\Http\Controllers\SiswaController;
use App\Http\Controllers\GuruController;
use App\Http\Controllers\JadwalController;
use App\Http\Controllers\DatasetController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));

Route::get('/login', [AuthController::class, 'form'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1')->name('login.proses');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', fn () => view('admin.dashboard'))->name('dashboard');
    Route::resource('kelas', KelasController::class)->except('show')->parameters(['kelas' => 'kelas']);
    Route::resource('siswa', SiswaController::class)->except('show')->parameters(['siswa' => 'siswa']);
    Route::resource('guru', GuruController::class)->except('show')->parameters(['guru' => 'guru']);

    // Dataset wajah (hanya admin)
    Route::get('/dataset', [DatasetController::class, 'index'])->name('dataset.index');
    Route::post('/dataset', [DatasetController::class, 'store'])->name('dataset.store');
    Route::get('/dataset/{dataset}/foto', [DatasetController::class, 'foto'])->name('dataset.foto');
    Route::delete('/dataset/{dataset}', [DatasetController::class, 'destroy'])->name('dataset.destroy');
});

Route::middleware('role:guru')->prefix('guru')->name('guru.')->group(function () {
    Route::get('/', fn () => view('guru.dashboard'))->name('dashboard');
});

// Jadwal: admin dan guru boleh melihat
Route::get('/jadwal', [JadwalController::class, 'index'])
    ->middleware('role:admin,guru')
    ->name('jadwal.index');

// Jadwal: hanya admin yang boleh mengubah
Route::middleware('role:admin')->group(function () {
    Route::resource('jadwal', JadwalController::class)
        ->except(['index', 'show'])
        ->parameters(['jadwal' => 'jadwal']);
});
<?php

return [
    // Alamat layanan Flask (OpenCV + dlib)
    'url'     => env('FACE_SERVICE_URL', 'http://127.0.0.1:5000'),
    'timeout' => (int) env('FACE_SERVICE_TIMEOUT', 15),

    // Jarak Euclidean maksimum agar dua wajah dianggap sama.
    // dlib bawaan 0.6; 0.5 lebih ketat. Nilai final ditetapkan lewat pengujian.
    'threshold' => (float) env('FACE_THRESHOLD', 0.5),

    // Toleransi keterlambatan (menit) setelah jam masuk pada jadwal.
    'toleransi_menit' => (int) env('FACE_LATE_TOLERANCE', 0),
];
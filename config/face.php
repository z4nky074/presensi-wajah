<?php

return [
    // Alamat layanan Flask (OpenCV + dlib)
    'url'     => env('FACE_SERVICE_URL', 'http://127.0.0.1:5000'),
    'timeout' => (int) env('FACE_SERVICE_TIMEOUT', 15),
];
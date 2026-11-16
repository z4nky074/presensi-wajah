<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class FaceService
{
    /**
     * Minta Flask mengubah foto menjadi face encoding (128 angka).
     *
     * @return array{ok:bool, code:string, message:string, encoding:?array}
     */
    public function encode(string $binary): array
    {
        try {
            $respons = Http::timeout(config('face.timeout'))
                ->attach('image', $binary, 'foto.jpg')
                ->post(rtrim(config('face.url'), '/').'/encode');
        } catch (ConnectionException $e) {
            return $this->gagal('service_down', 'Layanan pengenalan wajah tidak dapat dihubungi.');
        }

        $json = $respons->json() ?? [];

        if (($json['ok'] ?? false) !== true) {
            return $this->gagal($json['code'] ?? 'service_error', $json['message'] ?? 'Wajah tidak dapat diproses.');
        }

        return ['ok' => true, 'code' => 'ok', 'message' => '', 'encoding' => $json['encoding']];
    }

    private function gagal(string $code, string $message): array
    {
        return ['ok' => false, 'code' => $code, 'message' => $message, 'encoding' => null];
    }
}
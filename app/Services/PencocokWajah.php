<?php

namespace App\Services;

use App\Models\DatasetWajah;

/**
 * Mencocokkan face encoding dari kamera dengan dataset.
 * Jarak Euclidean terkecil <= threshold dianggap cocok.
 */
class PencocokWajah
{
    /**
     * @param  string|null  $peran  'siswa' atau 'guru' membatasi pencarian ke dataset peran itu;
     *                              null berarti seluruh dataset.
     * @return array{peran:string,id:int,nama:string,jarak:float,confidence:float}|null
     */
    public function cocokkan(array $encoding, ?string $peran = null): ?array
    {
        $terbaik = null;

        $query = DatasetWajah::with(['siswa', 'guru']);
        if ($peran === 'siswa') {
            $query->whereNotNull('id_siswa');
        } elseif ($peran === 'guru') {
            $query->whereNotNull('id_guru');
        }

        foreach ($query->get() as $d) {
            $ref = json_decode($d->face_encoding, true);
            if (! is_array($ref) || count($ref) !== 128) {
                continue;
            }

            $jarak = $this->jarak($encoding, $ref);

            if ($terbaik === null || $jarak < $terbaik['jarak']) {
                $terbaik = [
                    'peran' => $d->peran,
                    'id'    => $d->id_siswa ?? $d->id_guru,
                    'nama'  => $d->pemilik ?? '-',
                    'jarak' => $jarak,
                ];
            }
        }

        if ($terbaik === null || $terbaik['jarak'] > config('face.threshold')) {
            return null;
        }

        $terbaik['confidence'] = round(max(0, 1 - $terbaik['jarak']) * 100, 2);

        return $terbaik;
    }

    private function jarak(array $a, array $b): float
    {
        $jumlah = 0.0;
        for ($i = 0; $i < 128; $i++) {
            $selisih = $a[$i] - $b[$i];
            $jumlah += $selisih * $selisih;
        }

        return sqrt($jumlah);
    }
}

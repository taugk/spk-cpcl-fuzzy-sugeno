<?php

namespace App\Imports;

use App\Models\Cpcl;
use App\Models\Alamat;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;

class CpclImport implements ToCollection
{
    public function collection(Collection $rows)
    {
        // hapus header
        $rows->shift();

        foreach ($rows as $index => $row) {

            try {
                // =========================
                // 🔥 VALIDASI DASAR
                // =========================
                if (empty($row[0]) || empty($row[1]) || empty($row[2])) {
                    continue;
                }

                // =========================
                // 🔥 NORMALISASI DATA WILAYAH
                // =========================
                $kab = strtoupper(trim($row[5]));
                $kec = strtoupper(trim($row[6]));
                $desa = strtoupper(trim($row[7]));

                // =========================
                // 🔥 CARI ALAMAT (PRIORITAS)
                // =========================
                $alamat = Alamat::where('kabupaten', $kab)
                    ->where('kecamatan', $kec)
                    ->where('desa', $desa)
                    ->first();

                // =========================
                // 🔥 JIKA TIDAK ADA → AUTO CREATE
                // =========================
                if (!$alamat) {
                    $alamat = Alamat::create([
                        'kd_kab'    => '32.08',
                        'kd_kec'    => null,
                        'kd_desa'   => null,
                        'kabupaten' => $kab,
                        'kecamatan' => $kec,
                        'desa'      => $desa,
                    ]);

                    Log::info('Alamat baru dibuat', [
                        'row' => $index + 1,
                        'kab' => $kab,
                        'kec' => $kec,
                        'desa' => $desa
                    ]);
                }

                // =========================
                // 🔥 CLEANING LUAS LAHAN
                // =========================
                $luasRaw = strtolower($row[9] ?? '');
                $luas = str_replace(['ha', ' ', ','], ['', '', '.'], $luasRaw);
                $luas = is_numeric($luas) ? (float)$luas : 0;

                // =========================
                // 🔥 MAPPING STATUS LAHAN
                // =========================
                $statusRaw = strtolower(trim($row[10] ?? ''));

                $statusMap = [
                    'milik'   => 'milik',
                    'sewa'    => 'sewa',
                    'garap'   => 'sewa',
                    'garapan' => 'sewa',
                    'tidak'   => 'bagi_hasil'
                ];

                $status = 'bagi_hasil';

                foreach ($statusMap as $key => $val) {
                    if (str_contains($statusRaw, $key)) {
                        $status = $val;
                        break;
                    }
                }

                // =========================
                // 🔥 SIMPAN CPCL
                // =========================
                Cpcl::create([
                    'nama_kelompok'   => strtoupper(trim($row[0])),
                    'nama_ketua'      => strtoupper(trim($row[1])),
                    'nik_ketua'       => str_pad(trim($row[2]), 16, '0', STR_PAD_LEFT),

                    'bidang'          => strtoupper(trim($row[3])),
                    'rencana_usaha'   => trim($row[4]),

                    'alamat_id'       => $alamat->id,

                    'lokasi'          => trim($row[8]),
                    'luas_lahan'      => $luas,
                    'status_lahan'    => $status,
                    'lama_berdiri'    => (int) $row[11],
                    'hasil_panen'     => (float) $row[12],
                    'latitude'        => $row[13],
                    'longitude'       => $row[14],

                    // file default null
                    'file_proposal'   => null,
                    'file_ktp'        => null,
                    'file_sk'         => null,
                    'foto_lahan'      => null,

                    // status default
                    'status'          => 'baru',
                    'catatan_verifikator' => null,
                ]);

            } catch (\Exception $e) {

                Log::error('Gagal import CPCL', [
                    'row' => $index + 1,
                    'data' => $row,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
}
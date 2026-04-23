<?php

namespace App\Imports;

use App\Models\Cpcl;
use App\Models\Alamat;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Concerns\ToCollection;

class CpclImport implements ToCollection
{
    public function collection(Collection $rows)
    {
        // Hapus header
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
                // 🔥 NORMALISASI DATA
                // =========================
                $namaKelompok = strtoupper(trim($row[0]));
                $namaKetua    = strtoupper(trim($row[1]));
                $nik          = str_pad(trim($row[2]), 16, '0', STR_PAD_LEFT);

                $kab = strtoupper(trim($row[5]));
                $kec = strtoupper(trim($row[6]));
                $desa = strtoupper(trim($row[7]));

                // =========================
                // 🔥 CARI / BUAT ALAMAT
                // =========================
                $alamat = Alamat::where('kabupaten', $kab)
                    ->where('kecamatan', $kec)
                    ->where('desa', $desa)
                    ->first();

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
                // 🔥 GENERATE FILE OTOMATIS
                // =========================
                $fileKtpPath       = "ktp/{$nik}.jpg";
                $fileProposalPath  = "proposal/{$nik}.pdf";
                $fileSkPath        = "sk/{$nik}.pdf";
                $fileLahanPath     = "lahan/{$nik}.jpg";

                // cek file di storage
                $file_ktp = Storage::disk('public')->exists($fileKtpPath)
                    ? $fileKtpPath
                    : 'default/ktp.png';

                $file_proposal = Storage::disk('public')->exists($fileProposalPath)
                    ? $fileProposalPath
                    : 'default/proposal.pdf';

                $file_sk = Storage::disk('public')->exists($fileSkPath)
                    ? $fileSkPath
                    : 'default/sk.pdf';

                $foto_lahan = Storage::disk('public')->exists($fileLahanPath)
                    ? $fileLahanPath
                    : 'default/lahan.jpg';

                // =========================
                // 🔥 SIMPAN CPCL
                // =========================
                Cpcl::create([
                    'nama_kelompok'   => $namaKelompok,
                    'nama_ketua'      => $namaKetua,
                    'nik_ketua'       => $nik,

                    'bidang'          => strtoupper(trim($row[3])),
                    'rencana_usaha'   => trim($row[4]),

                    'alamat_id'       => $alamat->id,

                    'lokasi'          => trim($row[8]),
                    'luas_lahan'      => $luas,
                    'status_lahan'    => $status,
                    'lama_berdiri'    => (int) ($row[11] ?? 0),
                    'hasil_panen'     => (float) ($row[12] ?? 0),
                    'latitude'        => $row[13] ?? null,
                    'longitude'       => $row[14] ?? null,

                    // 🔥 FILE AUTO
                    'file_proposal'   => $file_proposal,
                    'file_ktp'        => $file_ktp,
                    'file_sk'         => $file_sk,
                    'foto_lahan'      => $foto_lahan,

                    // 🔥 STATUS
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
<?php

namespace App\Services;

use App\Models\Cpcl;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CpclService
{
    public function storeCpcl(array $data)
    {
        Log::channel('daily')->info('CPCL SERVICE START', [
            'time' => now()->toDateTimeString(),
            'keys' => array_keys($data),
            'files' => [
                'file_proposal' => $data['file_proposal'] ?? null,
                'file_ktp'      => $data['file_ktp'] ?? null,
                'file_sk'       => $data['file_sk'] ?? null,
                'foto_lahan'    => $data['foto_lahan'] ?? null,
            ]
        ]);

        return DB::transaction(function () use ($data) {

            $slug     = Str::slug($data['nama_kelompok'], '_');
            $time     = now()->format('Ymd_His');
            $uploaded = [];

            try {
                $upload = function ($key, $folder, $prefix) use (&$data, &$uploaded, $slug, $time) {
                    if (!isset($data[$key])) {
                        Log::warning("File {$key} tidak ada");
                        return;
                    }
                    if (!$data[$key] instanceof \Illuminate\Http\UploadedFile) {
                        Log::error("{$key} bukan UploadedFile", ['type' => gettype($data[$key])]);
                        return;
                    }
                    Log::info("Uploading {$key}", [
                        'original' => $data[$key]->getClientOriginalName(),
                        'mime'     => $data[$key]->getMimeType(),
                        'size_kb'  => round($data[$key]->getSize() / 1024, 2),
                    ]);
                    $filename       = "{$prefix}_{$slug}_{$time}." . $data[$key]->extension();
                    $path           = $data[$key]->storeAs("uploads/{$folder}", $filename, 'public');
                    if (!$path) throw new \Exception("Upload {$key} gagal disimpan");
                    $uploaded[$key] = $path;
                    $data[$key]     = $path;
                    Log::info("Upload {$key} sukses", ['path' => $path]);
                };

                $upload('file_proposal', 'proposal', 'proposal');
                $upload('file_ktp',      'ktp',      'ktp');
                $upload('file_sk',       'sk',       'sk');
                $upload('foto_lahan',    'lahan',    'foto');

                $data['status']     = 'baru';
                $data['created_at'] = now();

                Log::info('CPCL FINAL DATA', collect($data)->except([
                    'file_proposal','file_ktp','file_sk','foto_lahan',
                ])->toArray());

                $cpcl = Cpcl::create($data);

                if (!$cpcl) throw new \Exception('Insert CPCL ke database gagal');

                Log::info('CPCL INSERT SUCCESS', [
                    'id'            => $cpcl->id,
                    'nama_kelompok' => $cpcl->nama_kelompok,
                    'status'        => $cpcl->status,
                ]);

                return $cpcl;

            } catch (\Throwable $e) {
                Log::critical('CPCL TRANSACTION FAILED', [
                    'message' => $e->getMessage(),
                    'line'    => $e->getLine(),
                    'file'    => $e->getFile(),
                ]);
                foreach ($uploaded as $key => $file) {
                    try {
                        Storage::disk('public')->delete($file);
                        Log::warning("Rollback delete {$key}", ['path' => $file]);
                    } catch (\Throwable $ex) {
                        Log::emergency("Rollback gagal hapus {$key}", [
                            'path'  => $file,
                            'error' => $ex->getMessage(),
                        ]);
                    }
                }
                throw $e;
            }
        });
    }

    public function updateCpcl(int $id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $cpcl        = Cpcl::findOrFail($id);
            $slugPoktan  = Str::slug($data['nama_kelompok'] ?? $cpcl->nama_kelompok, '_');
            $tanggal     = date('Ymd_His');
            $fileFields  = [
                'file_proposal' => 'proposal',
                'file_ktp'      => 'ktp',
                'file_sk'       => 'sk',
                'foto_lahan'    => 'lahan',
            ];

            foreach ($fileFields as $field => $folder) {
                if (isset($data[$field]) && $data[$field] instanceof \Illuminate\Http\UploadedFile) {
                    if ($cpcl->$field) Storage::disk('public')->delete($cpcl->$field);
                    $prefix       = str_replace('file_', '', $field);
                    $data[$field] = $this->uploadFile($data[$field], $folder, "{$prefix}_{$slugPoktan}_{$tanggal}");
                }
            }

            if ($cpcl->status === 'perlu_perbaikan') {
                $data['status'] = 'baru';
            }

            $cpcl->update($data);
            return $cpcl;
        });
    }

    // =========================================================================
    // VERIFY
    // =========================================================================
    //
    // KENAPA FUZZY HARUS DI LUAR DB::transaction()?
    //
    // DB::transaction() membungkus koneksi database dalam satu unit atomik.
    // Jika ada exception di DALAM transaction → semua query di-ROLLBACK.
    //
    // FuzzySugenoService::hitungDanSimpan() melakukan:
    //   - SELECT dari cpcl, kriteria, sub_kriteria, cpcl_penilaian
    //   - INSERT/UPDATE ke hasil_fuzzy
    //
    // Jika fuzzy gagal dan berada di DALAM transaction:
    //   → cpcl_penilaian yang baru saja di-insert ikut ROLLBACK
    //   → cpcl.status yang baru di-update ikut ROLLBACK
    //   → Data verifikasi HILANG meski admin sudah klik simpan
    //
    // Solusi: pisahkan menjadi 2 blok:
    //   [1] DB::transaction() → simpan penilaian + update status (HARUS atomik)
    //   [2] Luar transaction  → jalankan fuzzy (boleh gagal, bisa diulang)
    //
    // =========================================================================

    public function verifyCpcl(int $id, array $data): Cpcl
    {
        // ── [1] ATOMIK: Simpan penilaian + update status ──────────────────────
        $cpcl = DB::transaction(function () use ($id, $data) {
            $cpcl = Cpcl::findOrFail($id);

            // Update status & catatan di tabel cpcl
            $cpcl->status                = $data['status'];
            $cpcl->catatan_verifikator   = $data['catatan_verifikator'] ?? null;
            $cpcl->save();

            // Simpan nilai penilaian ke cpcl_penilaian
            if (!empty($data['nilai'])) {
                // Hapus penilaian lama agar tidak duplikat
                DB::table('cpcl_penilaian')->where('cpcl_id', $id)->delete();

                $rows = [];
                foreach ($data['nilai'] as $kriteriaId => $isiNilai) {
                    if ($isiNilai === null || $isiNilai === '') {
                        Log::warning("Nilai kosong dilewati", [
                            'cpcl_id'     => $id,
                            'kriteria_id' => $kriteriaId,
                        ]);
                        continue;
                    }
                    $rows[] = [
                        'cpcl_id'     => $id,
                        'kriteria_id' => (int) $kriteriaId,
                        'nilai'       => trim($isiNilai),
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ];
                }

                if (!empty($rows)) {
                    DB::table('cpcl_penilaian')->insert($rows);
                    Log::debug("Tersimpan " . count($rows) . " baris ke cpcl_penilaian", [
                        'cpcl_id' => $id,
                    ]);
                }
            }

            return $cpcl;
        });
        // ← Transaction selesai. cpcl_penilaian & cpcl.status sudah COMMIT ke DB.

        // ── [2] TIDAK ADA HITUNG FUZZY DI SINI ──────────────────────────────
        // Perhitungan fuzzy tidak dijalankan saat verifikasi.
        // Admin mengumpulkan semua CPCL terverifikasi terlebih dahulu,
        // lalu memicu perhitungan massal dari halaman Ranking (hitungSemuaDanRanking).
        // Ini memastikan ranking bersifat komparatif dan konsisten antar alternatif.

        return $cpcl;
    }

    public function deleteCpcl(int $id)
    {
        return DB::transaction(function () use ($id) {
            $cpcl  = Cpcl::findOrFail($id);
            $files = [$cpcl->file_proposal, $cpcl->file_ktp, $cpcl->file_sk, $cpcl->foto_lahan];

            foreach ($files as $file) {
                if ($file) Storage::disk('public')->delete($file);
            }

            return $cpcl->delete();
        });
    }

    private function uploadFile($file, $folder, $customName)
    {
        $extension    = $file->getClientOriginalExtension();
        $fullFileName = $customName . '.' . $extension;
        return $file->storeAs('uploads/' . $folder, $fullFileName, 'public');
    }
}
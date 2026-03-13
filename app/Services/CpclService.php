<?php

namespace App\Services;

use App\Models\Alamat;
use App\Models\Cpcl;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CpclService
{
    // =========================================================================
    // STORE
    // =========================================================================

    public function storeCpcl(array $data): Cpcl
    {
        Log::channel('daily')->info('CPCL SERVICE storeCpcl START', [
            'time' => now()->toDateTimeString(),
            'keys' => array_keys($data),
        ]);

        return DB::transaction(function () use ($data) {

            $slug     = Str::slug($data['nama_kelompok'], '_');
            $time     = now()->format('Ymd_His');
            $uploaded = [];

            try {
                // ── Upload file ───────────────────────────────────────────────
                $upload = function (string $key, string $folder, string $prefix) use (&$data, &$uploaded, $slug, $time) {
                    if (!isset($data[$key])) {
                        Log::warning("storeCpcl: file {$key} tidak ada dalam request");
                        return;
                    }
                    if (!$data[$key] instanceof \Illuminate\Http\UploadedFile) {
                        Log::error("storeCpcl: {$key} bukan UploadedFile", ['type' => gettype($data[$key])]);
                        return;
                    }

                    Log::info("storeCpcl: uploading {$key}", [
                        'original' => $data[$key]->getClientOriginalName(),
                        'mime'     => $data[$key]->getMimeType(),
                        'size_kb'  => round($data[$key]->getSize() / 1024, 2),
                    ]);

                    $filename = "{$prefix}_{$slug}_{$time}." . $data[$key]->extension();
                    $path     = $data[$key]->storeAs("uploads/{$folder}", $filename, 'public');

                    if (!$path) throw new \Exception("Upload {$key} gagal disimpan ke storage");

                    $uploaded[$key] = $path;
                    $data[$key]     = $path;

                    Log::info("storeCpcl: upload {$key} sukses", ['path' => $path]);
                };

                $upload('file_proposal', 'proposal', 'proposal');
                $upload('file_ktp',      'ktp',      'ktp');
                $upload('file_sk',       'sk',       'sk');
                $upload('foto_lahan',    'lahan',    'foto');

                // ── Simpan / temukan alamat, ambil ID-nya ─────────────────────
                $alamat = Alamat::updateOrCreate(
                    [
                        'kd_kab'  => $data['kd_kab'],
                        'kd_kec'  => $data['kd_kec'],
                        'kd_desa' => $data['kd_desa'],
                    ],
                    [
                        'kabupaten' => $data['kabupaten'],
                        'kecamatan' => $data['kecamatan'],
                        'desa'      => $data['desa'],
                    ]
                );

                Log::info('storeCpcl: alamat saved', [
                    'alamat_id' => $alamat->id,
                    'kd_desa'   => $alamat->kd_desa,
                    'desa'      => $alamat->desa,
                ]);

                // ── Bersihkan data: buang field wilayah & sistem ──────────────
                $cpclData = collect($data)->except([
                    'kd_kab', 'kabupaten',
                    'kd_kec', 'kecamatan',
                    'kd_desa', 'desa',
                    '_token', '_method',
                ])->toArray();

                // Pasang foreign key alamat
                $cpclData['alamat_id']   = $alamat->id;
                $cpclData['status']      = 'baru';
                $cpclData['created_at']  = now();

                Log::info('storeCpcl: data final sebelum insert', collect($cpclData)->except([
                    'file_proposal', 'file_ktp', 'file_sk', 'foto_lahan',
                ])->toArray());

                $cpcl = Cpcl::create($cpclData);

                if (!$cpcl) throw new \Exception('Insert CPCL ke database gagal');

                Log::info('storeCpcl: INSERT SUCCESS', [
                    'id'            => $cpcl->id,
                    'nama_kelompok' => $cpcl->nama_kelompok,
                    'alamat_id'     => $cpcl->alamat_id,
                    'status'        => $cpcl->status,
                ]);

                return $cpcl;

            } catch (\Throwable $e) {
                // Rollback: hapus file yang sudah terupload
                Log::critical('storeCpcl: TRANSACTION FAILED — rollback file', [
                    'message' => $e->getMessage(),
                    'line'    => $e->getLine(),
                    'file'    => $e->getFile(),
                ]);

                foreach ($uploaded as $key => $path) {
                    try {
                        Storage::disk('public')->delete($path);
                        Log::warning("storeCpcl: rollback hapus {$key}", ['path' => $path]);
                    } catch (\Throwable $ex) {
                        Log::emergency("storeCpcl: rollback GAGAL hapus {$key}", [
                            'path'  => $path,
                            'error' => $ex->getMessage(),
                        ]);
                    }
                }

                throw $e;
            }
        });
    }

    // =========================================================================
    // UPDATE
    // =========================================================================

    public function updateCpcl(int $id, array $data): Cpcl
    {
        return DB::transaction(function () use ($id, $data) {
            $cpcl       = Cpcl::findOrFail($id);
            $slugPoktan = Str::slug($data['nama_kelompok'] ?? $cpcl->nama_kelompok, '_');
            $tanggal    = now()->format('Ymd_His');

            // ── Ganti file jika ada yang baru diupload ────────────────────────
            $fileFields = [
                'file_proposal' => 'proposal',
                'file_ktp'      => 'ktp',
                'file_sk'       => 'sk',
                'foto_lahan'    => 'lahan',
            ];

            foreach ($fileFields as $field => $folder) {
                if (isset($data[$field]) && $data[$field] instanceof \Illuminate\Http\UploadedFile) {
                    // Hapus file lama
                    if ($cpcl->$field && Storage::disk('public')->exists($cpcl->$field)) {
                        Storage::disk('public')->delete($cpcl->$field);
                    }
                    $prefix       = str_replace('file_', '', $field);
                    $data[$field] = $this->uploadFile($data[$field], $folder, "{$prefix}_{$slugPoktan}_{$tanggal}");
                }
            }

            // ── Update alamat, ambil ID-nya ───────────────────────────────────
            if (!empty($data['kd_kab']) && !empty($data['kd_kec']) && !empty($data['kd_desa'])) {
                $alamat = Alamat::updateOrCreate(
                    [
                        'kd_kab'  => $data['kd_kab'],
                        'kd_kec'  => $data['kd_kec'],
                        'kd_desa' => $data['kd_desa'],
                    ],
                    [
                        'kabupaten' => $data['kabupaten'],
                        'kecamatan' => $data['kecamatan'],
                        'desa'      => $data['desa'],
                    ]
                );

                $data['alamat_id'] = $alamat->id;

                Log::info('updateCpcl: alamat updated', [
                    'alamat_id' => $alamat->id,
                    'kd_desa'   => $alamat->kd_desa,
                ]);
            }

            // ── Bersihkan data: buang field wilayah & sistem ──────────────────
            $cpclData = collect($data)->except([
                'kd_kab', 'kabupaten',
                'kd_kec', 'kecamatan',
                'kd_desa', 'desa',
                '_token', '_method',
            ])->toArray();

            // Jika sebelumnya perlu perbaikan, kembalikan ke baru
            if ($cpcl->status === 'perlu_perbaikan') {
                $cpclData['status'] = 'baru';
            }

            $cpcl->update($cpclData);

            Log::info('updateCpcl: UPDATE SUCCESS', [
                'id'        => $cpcl->id,
                'alamat_id' => $cpcl->alamat_id,
                'status'    => $cpcl->status,
            ]);

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
    // FuzzySugenoService melakukan SELECT & INSERT ke hasil_fuzzy.
    // Jika fuzzy gagal dan berada di DALAM transaction:
    //   → cpcl_penilaian yang baru saja di-insert ikut ROLLBACK
    //   → cpcl.status yang baru di-update ikut ROLLBACK
    //   → Data verifikasi HILANG meski admin sudah klik simpan
    //
    // Solusi: pisahkan menjadi 2 blok:
    //   [1] DB::transaction() → simpan penilaian + update status (HARUS atomik)
    //   [2] Luar transaction  → jalankan fuzzy (boleh gagal, bisa diulang dari Ranking)
    //
    // =========================================================================

    public function verifyCpcl(int $id, array $data): Cpcl
    {
        // ── [1] ATOMIK: Simpan penilaian + update status ──────────────────────
        $cpcl = DB::transaction(function () use ($id, $data) {
            $cpcl = Cpcl::findOrFail($id);

            $cpcl->status              = $data['status'];
            $cpcl->catatan_verifikator = $data['catatan_verifikator'] ?? null;
            $cpcl->save();

            if (!empty($data['nilai'])) {
                DB::table('cpcl_penilaian')->where('cpcl_id', $id)->delete();

                $rows = [];
                foreach ($data['nilai'] as $kriteriaId => $isiNilai) {
                    if ($isiNilai === null || $isiNilai === '') {
                        Log::warning('verifyCpcl: nilai kosong dilewati', [
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
                    Log::debug('verifyCpcl: penilaian tersimpan', [
                        'cpcl_id' => $id,
                        'jumlah'  => count($rows),
                    ]);
                }
            }

            return $cpcl;
        });
        // ← Transaction selesai. cpcl_penilaian & cpcl.status sudah COMMIT.

        // ── [2] Fuzzy TIDAK dijalankan di sini ───────────────────────────────
        // Perhitungan massal dilakukan dari halaman Ranking (hitungSemuaDanRanking).

        return $cpcl;
    }

    // =========================================================================
    // DELETE
    // =========================================================================

    public function deleteCpcl(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $cpcl = Cpcl::findOrFail($id);

            // File akan dihapus oleh CpclObserver@deleting
            // sehingga tidak perlu hapus manual di sini.
            return (bool) $cpcl->delete();
        });
    }

    // =========================================================================
    // PRIVATE HELPER
    // =========================================================================

    private function uploadFile(\Illuminate\Http\UploadedFile $file, string $folder, string $customName): string
    {
        $fullFileName = $customName . '.' . $file->getClientOriginalExtension();
        return $file->storeAs('uploads/' . $folder, $fullFileName, 'public');
    }
}
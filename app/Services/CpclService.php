<?php

namespace App\Services;

use App\Models\Cpcl;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CpclService
{
    /**
     * Menyimpan data CPCL baru beserta file pendukungnya
     */

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

        $slug = Str::slug($data['nama_kelompok'], '_');
        $time = now()->format('Ymd_His');
        $uploaded = [];

        try {

            // ===================== FILE UPLOADER =====================
            $upload = function ($key, $folder, $prefix) use (&$data, &$uploaded, $slug, $time) {

                if (!isset($data[$key])) {
                    Log::warning("File {$key} tidak ada");
                    return;
                }

                if (!$data[$key] instanceof \Illuminate\Http\UploadedFile) {
                    Log::error("{$key} bukan UploadedFile", [
                        'type' => gettype($data[$key]),
                        'value' => $data[$key]
                    ]);
                    return;
                }

                Log::info("Uploading {$key}", [
                    'original' => $data[$key]->getClientOriginalName(),
                    'mime'     => $data[$key]->getMimeType(),
                    'size_kb'  => round($data[$key]->getSize() / 1024, 2)
                ]);

                $filename = "{$prefix}_{$slug}_{$time}." . $data[$key]->extension();
                $path = $data[$key]->storeAs("uploads/{$folder}", $filename, 'public');

                if (!$path) {
                    throw new \Exception("Upload {$key} gagal disimpan");
                }

                $uploaded[$key] = $path;
                $data[$key] = $path;

                Log::info("Upload {$key} sukses", [
                    'path' => $path
                ]);
            };

            // ===================== PROSES FILE =====================
            $upload('file_proposal', 'proposal', 'proposal');
            $upload('file_ktp', 'ktp', 'ktp');
            $upload('file_sk', 'sk', 'sk');
            $upload('foto_lahan', 'lahan', 'foto');

            // ===================== FINAL DATA =====================
            $data['status'] = 'baru';
            $data['created_at'] = now();

            Log::info('CPCL FINAL DATA', collect($data)->except([
                'file_proposal','file_ktp','file_sk','foto_lahan'
            ])->toArray());

            // ===================== INSERT DATABASE =====================
            $cpcl = Cpcl::create($data);

            if (!$cpcl) {
                throw new \Exception('Insert CPCL ke database gagal');
            }

            Log::info('CPCL INSERT SUCCESS', [
                'id' => $cpcl->id,
                'nama_kelompok' => $cpcl->nama_kelompok,
                'status' => $cpcl->status
            ]);

            return $cpcl;

        } catch (\Throwable $e) {

            Log::critical('CPCL TRANSACTION FAILED', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);

            // ===================== ROLLBACK FILE =====================
            foreach ($uploaded as $key => $file) {
                try {
                    Storage::disk('public')->delete($file);
                    Log::warning("Rollback delete {$key}", ['path' => $file]);
                } catch (\Throwable $ex) {
                    Log::emergency("Rollback gagal hapus {$key}", [
                        'path' => $file,
                        'error' => $ex->getMessage()
                    ]);
                }
            }

            throw $e; // wajib agar DB rollback
        }
    });
}



    /**
     * Memperbarui data CPCL (UPTD melakukan perbaikan)
     */
    public function updateCpcl(int $id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $cpcl = Cpcl::findOrFail($id);
            
            $slugPoktan = Str::slug($data['nama_kelompok'] ?? $cpcl->nama_kelompok, '_');
            $tanggal = date('Ymd_His');

            $fileFields = [
                'file_proposal' => 'proposal',
                'file_ktp'      => 'ktp',
                'file_sk'       => 'sk',
                'foto_lahan'    => 'lahan'
            ];

            foreach ($fileFields as $field => $folder) {
                if (isset($data[$field]) && $data[$field] instanceof \Illuminate\Http\UploadedFile) {
                    if ($cpcl->$field) {
                        Storage::disk('public')->delete($cpcl->$field);
                    }
                    $prefix = str_replace('file_', '', $field);
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

    
public function verifyCpcl(int $id, array $data)
{
    return DB::transaction(function () use ($id, $data) {
        $cpcl = \App\Models\Cpcl::findOrFail($id);
        
        // 1. Update Status dan Catatan di tabel CPCL
        $cpcl->status = $data['status'];
        $cpcl->catatan_verifikator = $data['catatan_verifikator'] ?? null;
        $cpcl->save();

        // 2. Proses Simpan ke cpcl_penilaian jika ada input nilai
        if (isset($data['nilai'])) {
            // Hapus penilaian lama untuk CPCL ini agar tidak duplikat
            DB::table('cpcl_penilaian')->where('cpcl_id', $id)->delete();

            foreach ($data['nilai'] as $kriteriaId => $isiNilai) {
                // Simpan nilai mentah hasil verifikasi Admin (Angka atau Teks)
                DB::table('cpcl_penilaian')->insert([
                    'cpcl_id'     => $id,
                    'kriteria_id' => $kriteriaId,
                    'nilai'       => $isiNilai,
                    'created_at'  => now(),
                    'updated_at'  => now()
                ]);
            }
        }

        // 3. Eksekusi Perhitungan Fuzzy jika status TERVERIFIKASI
        if ($data['status'] === 'terverifikasi') {
            // Memanggil Service khusus yang menangani hitungan Fuzzifikasi -> Inferensi -> Defuzzifikasi
            \App\Services\FuzzySugenoService::hitungDanSimpan($id);
        }

        return $cpcl;
    });
}


    /**
     * Menghapus data beserta file fisiknya
     */
    public function deleteCpcl(int $id)
    {
        return DB::transaction(function () use ($id) {
            $cpcl = Cpcl::findOrFail($id);
            $files = [$cpcl->file_proposal, $cpcl->file_ktp, $cpcl->file_sk, $cpcl->foto_lahan];

            foreach ($files as $file) {
                if ($file) { Storage::disk('public')->delete($file); }
            }

            return $cpcl->delete();
        });
    }

    /**
     * Helper upload file
     */
    private function uploadFile($file, $folder, $customName)
    {
        $extension = $file->getClientOriginalExtension();
        $fullFileName = $customName . '.' . $extension;
        return $file->storeAs('uploads/' . $folder, $fullFileName, 'public');
    }
}
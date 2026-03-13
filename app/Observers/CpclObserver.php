<?php

namespace App\Observers;

use App\Models\Cpcl;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CpclObserver
{
    /**
     * Saat CPCL baru dibuat — tidak ada aksi tambahan.
     * File sudah diupload oleh CpclService sebelum Cpcl::create() dipanggil.
     */
    public function created(Cpcl $cpcl): void
    {
        Log::info('CpclObserver@created', [
            'id'            => $cpcl->id,
            'nama_kelompok' => $cpcl->nama_kelompok,
            'alamat_id'     => $cpcl->alamat_id,
        ]);
    }

    /**
     * Saat CPCL diupdate — tidak ada aksi tambahan.
     * File lama sudah dihapus & diganti oleh CpclService sebelum $cpcl->update() dipanggil.
     */
    public function updated(Cpcl $cpcl): void
    {
        //
    }

    /**
     * Saat CPCL akan di-soft-delete:
     * Hapus semua file lampiran dari storage agar tidak menumpuk.
     *
     * Menggunakan `deleting` (bukan `deleted`) agar data masih bisa diakses
     * sebelum soft delete flag diterapkan.
     */
    public function deleting(Cpcl $cpcl): void
    {
        $files = [
            'file_proposal' => $cpcl->file_proposal,
            'file_ktp'      => $cpcl->file_ktp,
            'file_sk'       => $cpcl->file_sk,
            'foto_lahan'    => $cpcl->foto_lahan,
        ];

        foreach ($files as $key => $path) {
            if (!$path) continue;

            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
                Log::info("CpclObserver@deleting: file {$key} dihapus", [
                    'cpcl_id' => $cpcl->id,
                    'path'    => $path,
                ]);
            } else {
                Log::warning("CpclObserver@deleting: file {$key} tidak ditemukan di storage", [
                    'cpcl_id' => $cpcl->id,
                    'path'    => $path,
                ]);
            }
        }
    }

    /**
     * Saat CPCL di-restore dari soft delete — tidak ada aksi.
     * File sudah terhapus saat deleting, tidak bisa dikembalikan.
     */
    public function restored(Cpcl $cpcl): void
    {
        //
    }

    /**
     * Saat CPCL di-force delete (hapus permanen):
     * Pastikan file juga ikut dihapus jika belum terhapus saat soft delete.
     */
    public function forceDeleted(Cpcl $cpcl): void
    {
        $files = [
            'file_proposal' => $cpcl->file_proposal,
            'file_ktp'      => $cpcl->file_ktp,
            'file_sk'       => $cpcl->file_sk,
            'foto_lahan'    => $cpcl->foto_lahan,
        ];

        foreach ($files as $key => $path) {
            if ($path && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
                Log::info("CpclObserver@forceDeleted: file {$key} dihapus", [
                    'cpcl_id' => $cpcl->id,
                    'path'    => $path,
                ]);
            }
        }
    }
}
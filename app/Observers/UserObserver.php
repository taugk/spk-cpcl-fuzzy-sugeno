<?php

namespace App\Observers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class UserObserver
{
    /**
     * Handle the User "creating" event.
     * Dijalankan OTOMATIS sesaat SEBELUM data disimpan ke database.
     */
    public function creating(User $user): void
    {
        // Cek apakah ada user yang sedang login (Admin)
        if (Auth::check()) {
            // Isi kolom created_by dengan ID Admin yang sedang login
            $user->created_by = Auth::id();
        }
    }

    /**
     * Handle the User "created" event.
     * Dijalankan SETELAH data berhasil masuk database.
     */
    public function created(User $user): void
    {
        // Opsional: Log activity atau kirim notifikasi email
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        // Opsional: Log jika ada perubahan data user
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        // Opsional: Clean up data terkait.
        // Contoh: Jika user dihapus, hapus juga data lain yang terhubung (jika tidak pakai cascade di DB)
        // $user->cpcl()->delete();
    }

    /**
     * Handle the User "restored" event.
     */
    public function restored(User $user): void
    {
        //
    }

    /**
     * Handle the User "force deleted" event.
     */
    public function forceDeleted(User $user): void
    {
        //
    }
}
<?php

namespace App\Observers;

use App\Models\Cpcl;
use Illuminate\Support\Facades\Storage;

class CpclObserver
{
    /**
     * Handle the Cpcl "created" event.
     */
    public function created(Cpcl $cpcl): void
    {
        
    }

    /**
     * Handle the Cpcl "updated" event.
     */
    public function updated(Cpcl $cpcl): void
    {
        //
    }

    /**
     * Handle the Cpcl "deleted" event.
     */
    public function deleting(Cpcl $cpcl): void
    {
        $files = [$cpcl->file_proposal, $cpcl->file_ktp, $cpcl->file_sk, $cpcl->foto_lahan];

        foreach ($files as $file) {
            if ($file && Storage::disk('public')->exists($file)) {
                Storage::disk('public')->delete($file);
            }
        }
    }

    /**
     * Handle the Cpcl "restored" event.
     */
    public function restored(Cpcl $cpcl): void
    {
        //
    }

    /**
     * Handle the Cpcl "force deleted" event.
     */
    public function forceDeleted(Cpcl $cpcl): void
    {
        //
    }
}

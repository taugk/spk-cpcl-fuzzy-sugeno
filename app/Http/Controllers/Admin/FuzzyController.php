<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cpcl;
use App\Models\Kriteria;
use App\Services\FuzzySugenoService;
use Illuminate\Http\Request;

class FuzzyController extends Controller
{
    public function kriteria(){
        return view('admin.fuzzy-sugeno.kriteria.index');
    }

    public function subKriteria(){
        return view('admin.fuzzy-sugeno.sub-kriteria.index');
    }

public function perhitungan($id = null)
{
    if ($id) {
        $cpcl = Cpcl::with(['penilaian.kriteria'])
                    ->findOrFail($id);
    } else {
        $cpcl = Cpcl::with(['penilaian.kriteria'])
                    ->latest('updated_at')
                    ->first();

        if (!$cpcl) {
            return redirect()->route('admin.cpcl.index')
                ->with('error', 'Belum ada data yang diverifikasi.');
        }
    }

    // 🔥 PANGGIL ENGINE PERHITUNGAN
    $hasil = FuzzySugenoService::hitung($cpcl->id);

    return view('admin.fuzzy-sugeno.perhitungan.index', compact('cpcl', 'hasil'));
}
}

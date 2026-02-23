<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cpcl;
use App\Models\Kriteria;
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
        // Jika diakses setelah klik "Simpan Keputusan"
        $cpcl = Cpcl::with(['penilaian.kriteria', 'hasilFuzzy'])->findOrFail($id);
    } else {
        // Jika diakses langsung dari menu Sidebar (Ambil data paling gres)
        $cpcl = Cpcl::with(['penilaian.kriteria', 'hasilFuzzy'])
                    ->has('hasilFuzzy')
                    ->latest('updated_at')
                    ->first();
        
        // Jika database benar-benar kosong
        if (!$cpcl) {
            return redirect()->route('admin.cpcl.index')
                             ->with('error', 'Belum ada data yang diverifikasi.');
        }
    }

    $kriteria = \App\Models\Kriteria::with('subKriteria')->get();
    return view('admin.fuzzy-sugeno.perhitungan.index', compact('cpcl', 'kriteria'));
}
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HasilFuzzy;
use App\Models\Cpcl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LaporanController extends Controller
{
    public function index(Request $request)
    {
        // Ambil daftar tahun unik dari data CPCL untuk filter
        $listTahun = Cpcl::select(DB::raw('YEAR(created_at) as tahun'))
                         ->distinct()->pluck('tahun');

        // Ambil daftar lokasi/kecamatan unik
        $listLokasi = Cpcl::distinct()->pluck('lokasi');

        // Ambil daftar bidang unik
        $listBidang = Cpcl::distinct()->pluck('bidang');

        // Query Utama dengan Filter
        $query = HasilFuzzy::with('cpcl');

        if ($request->tahun) {
            $query->whereYear('created_at', $request->tahun);
        }
        if ($request->lokasi) {
            $query->whereHas('cpcl', function($q) use ($request) {
                $q->where('lokasi', $request->lokasi);
            });
        }
        if ($request->bidang) {
            $query->whereHas('cpcl', function($q) use ($request) {
                $q->where('bidang', $request->bidang);
            });
        }

        $data = $query->orderBy('ranking', 'asc')->get();

        return view('admin.laporan.index', compact('data', 'listTahun', 'listLokasi', 'listBidang'));
    }
}
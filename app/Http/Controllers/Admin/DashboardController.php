<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cpcl;
use App\Models\HasilFuzzy;
use App\Models\Kriteria;
use App\Models\SubKriteria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // 1. Statistik Verifikasi
        $totalCpcl = Cpcl::count();
        $countBaru = Cpcl::where('status', 'baru')->count();
        $countTerverifikasi = Cpcl::where('status', 'terverifikasi')->count();
        $countDitolak = Cpcl::where('status', 'ditolak')->count();

        // 2. Statistik Perhitungan Fuzzy
        $countRanking = HasilFuzzy::count();
        $avgSkor = HasilFuzzy::avg('skor_akhir') ?? 0;
        $topRank = HasilFuzzy::with('cpcl')->orderBy('ranking', 'asc')->limit(5)->get();

        // 3. Data Grafik Wilayah (Kolom Lokasi)
        $dataLokasi = Cpcl::select('lokasi', DB::raw('count(*) as total'))
            ->groupBy('lokasi')->orderBy('total', 'desc')->get();
        $lokasiLabels = $dataLokasi->pluck('lokasi');
        $lokasiData = $dataLokasi->pluck('total');

        // 4. Data Grafik Bidang (Padi, Jagung, dll)
        $dataBidang = Cpcl::select('bidang', DB::raw('count(*) as total'))
            ->groupBy('bidang')->get();
        $bidangLabels = $dataBidang->pluck('bidang');
        $bidangData = $dataBidang->pluck('total');

        // 5. Metadata Konfigurasi
        $jmlKriteria = Kriteria::count();
        $jmlSubKriteria = SubKriteria::count();

        return view('admin.dashboard.index', compact(
            'totalCpcl', 'countBaru', 'countTerverifikasi', 'countDitolak',
            'countRanking', 'avgSkor', 'topRank',
            'lokasiLabels', 'lokasiData',
            'bidangLabels', 'bidangData',
            'jmlKriteria', 'jmlSubKriteria'
        ));
    }
}
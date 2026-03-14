<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cpcl;
use App\Models\HasilFuzzy;
use App\Models\Kriteria;
use App\Models\SubKriteria;
use App\Models\Alamat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // 1. Statistik Dasar
        $totalCpcl = Cpcl::count();
        $countBaru = Cpcl::where('status', 'baru')->count();
        $countTerverifikasi = Cpcl::where('status', 'terverifikasi')->count();
        $countDitolak = Cpcl::where('status', 'ditolak')->count();

        // 2. Data Grafik Status (Rasio Verifikasi)
        $statusLabels = ['Baru', 'Terverifikasi', 'Ditolak'];
        $statusData = [$countBaru, $countTerverifikasi, $countDitolak];

        // 3. Statistik Perhitungan Fuzzy & Top 5
        $countRanking = HasilFuzzy::count();
        $avgSkor = HasilFuzzy::avg('skor_akhir') ?? 0;
        
        $topRank = HasilFuzzy::with('cpcl')
            ->orderBy('skor_akhir', 'desc')
            ->limit(5)
            ->get();

        $topSkorLabels = $topRank->map(fn($item) => $item->cpcl->nama_kelompok ?? 'Anonim');
        $topSkorData = $topRank->pluck('skor_akhir');

        // 4. Data Grafik Wilayah (Kecamatan)
        $dataLokasi = Cpcl::leftJoin('alamat', 'cpcl.alamat_id', '=', 'alamat.id')
            ->select('alamat.kecamatan', DB::raw('count(cpcl.id) as total'))
            ->whereNotNull('alamat.kecamatan')
            ->groupBy('alamat.kecamatan')
            ->orderBy('total', 'desc')
            ->limit(10)
            ->get();

        $lokasiLabels = $dataLokasi->pluck('kecamatan');
        $lokasiData = $dataLokasi->pluck('total');

        // 5. Data Grafik Bidang
        $dataBidang = Cpcl::select('bidang', DB::raw('count(*) as total'))
            ->groupBy('bidang')
            ->get();
        $bidangLabels = $dataBidang->pluck('bidang');
        $bidangData = $dataBidang->pluck('total');

        // 6. Data Grafik Tren (Registrasi 6 Bulan Terakhir)
        $trenRegistrasi = Cpcl::select(
        DB::raw('DATE_FORMAT(created_at, "%b %Y") as bulan'),
        DB::raw('count(*) as total')
    )
    ->groupBy(DB::raw('DATE_FORMAT(created_at, "%b %Y")')) // Gunakan format yang sama
    ->orderBy(DB::raw('MIN(created_at)'), 'asc') // Gunakan MIN agar tidak error
    ->limit(6)
    ->get();

$trenLabels = $trenRegistrasi->pluck('bulan');
$trenData = $trenRegistrasi->pluck('total');

        // 7. Metadata
        $jmlKriteria = Kriteria::count();
        $jmlSubKriteria = SubKriteria::count();
        $jmlKecamatanTercover = Alamat::distinct('kecamatan')->count();

        return view('admin.dashboard.index', compact(
            'totalCpcl', 'countBaru', 'countTerverifikasi', 'countDitolak',
            'countRanking', 'avgSkor', 'topRank',
            'lokasiLabels', 'lokasiData',
            'bidangLabels', 'bidangData',
            'statusLabels', 'statusData',
            'topSkorLabels', 'topSkorData',
            'trenLabels', 'trenData',
            'jmlKriteria', 'jmlSubKriteria', 'jmlKecamatanTercover'
        ));
    }
}
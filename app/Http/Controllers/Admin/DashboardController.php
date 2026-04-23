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
    $user = auth()->user();
    $role = $user->role;

    // 1. Inisialisasi Query Dasar dengan Filter Bidang
    // Jika admin (super), tampilkan semua. Jika admin_bidang, filter berdasarkan mapping.
    $cpclQuery = Cpcl::query();
    $fuzzyQuery = HasilFuzzy::query();

    if ($role === 'admin_pangan') {
        $cpclQuery->where('bidang', 'pangan');
        $fuzzyQuery->whereHas('cpcl', fn($q) => $q->where('bidang', 'pangan'));
    } elseif ($role === 'admin_hartibun') {
        $cpclQuery->where('bidang', 'hartibun');
        $fuzzyQuery->whereHas('cpcl', fn($q) => $q->where('bidang', 'hartibun'));
    }

    // 2. Statistik Dasar (Filtered)
    $totalCpcl = (clone $cpclQuery)->count();
    $countBaru = (clone $cpclQuery)->where('status', 'baru')->count();
    $countTerverifikasi = (clone $cpclQuery)->where('status', 'terverifikasi')->count();
    $countDitolak = (clone $cpclQuery)->where('status', 'ditolak')->count();

    // 3. Data Grafik Status (Rasio Verifikasi)
    $statusLabels = ['Baru', 'Terverifikasi', 'Ditolak'];
    $statusData = [$countBaru, $countTerverifikasi, $countDitolak];

    // 4. Statistik Perhitungan Fuzzy & Top 5 (Filtered)
    $countRanking = (clone $fuzzyQuery)->count();
    $avgSkor = (clone $fuzzyQuery)->avg('skor_akhir') ?? 0;
    
    $topRank = (clone $fuzzyQuery)->with('cpcl')
        ->orderBy('skor_akhir', 'desc')
        ->limit(5)
        ->get();

    $topSkorLabels = $topRank->map(fn($item) => $item->cpcl->nama_kelompok ?? 'Anonim');
    $topSkorData = $topRank->pluck('skor_akhir');

    // 5. Data Grafik Wilayah (Filtered)
    $dataLokasi = (clone $cpclQuery)
        ->leftJoin('alamat', 'cpcl.alamat_id', '=', 'alamat.id')
        ->select('alamat.kecamatan', DB::raw('count(cpcl.id) as total'))
        ->whereNotNull('alamat.kecamatan')
        ->groupBy('alamat.kecamatan')
        ->orderBy('total', 'desc')
        ->limit(10)
        ->get();

    $lokasiLabels = $dataLokasi->pluck('kecamatan');
    $lokasiData = $dataLokasi->pluck('total');

    // 6. Data Grafik Bidang (Hanya relevan untuk Super Admin)
    $dataBidang = (clone $cpclQuery)
        ->select('bidang', DB::raw('count(*) as total'))
        ->groupBy('bidang')
        ->get();
    $bidangLabels = $dataBidang->pluck('bidang');
    $bidangData = $dataBidang->pluck('total');

    // 7. Data Grafik Tren (Filtered)
    $trenRegistrasi = (clone $cpclQuery)
        ->select(
            DB::raw('DATE_FORMAT(created_at, "%b %Y") as bulan'),
            DB::raw('count(*) as total')
        )
        ->groupBy(DB::raw('DATE_FORMAT(created_at, "%b %Y")'))
        ->orderBy(DB::raw('MIN(created_at)'), 'asc')
        ->limit(6)
        ->get();

    $trenLabels = $trenRegistrasi->pluck('bulan');
    $trenData = $trenRegistrasi->pluck('total');

    // 8. Metadata
    $jmlKriteria = Kriteria::count();
    $jmlSubKriteria = SubKriteria::count();
    
    // Filter kecamatan tercover sesuai data yang difilter
    $jmlKecamatanTercover = Alamat::whereHas('cpcl', function($q) use ($role) {
        if ($role === 'admin_pangan') $q->where('bidang', 'pangan');
        if ($role === 'admin_hartibun') $q->where('bidang', 'hartibun');
    })->distinct('kecamatan')->count();

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
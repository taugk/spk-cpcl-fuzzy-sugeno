<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cpcl;
use App\Models\HasilFuzzy;
use App\Models\Kriteria;
use App\Models\SubKriteria;
use App\Models\Alamat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        // 🔒 Guard user
        $user = Auth::user();
        if (!$user) {
            abort(403, 'Unauthorized');
        }

        $role = $user->role;

        // 1. Base Query
        $cpclQuery = Cpcl::query();
        $fuzzyQuery = HasilFuzzy::query();

        // Filter berdasarkan Role Admin Bidang
        if ($role === 'admin_pangan') {
            $cpclQuery->where('bidang', 'pangan');
            $fuzzyQuery->whereHas('cpcl', fn($q) => $q->where('bidang', 'pangan'));
        } elseif ($role === 'admin_hartibun') {
            $cpclQuery->where('bidang', 'hartibun');
            $fuzzyQuery->whereHas('cpcl', fn($q) => $q->where('bidang', 'hartibun'));
        }

        // 2. Statistik Status (CPCL)
        $totalCpcl = (clone $cpclQuery)->count();
        $countBaru = (clone $cpclQuery)->where('status', 'baru')->count();
        $countTerverifikasi = (clone $cpclQuery)->where('status', 'terverifikasi')->count();
        $countDitolak = (clone $cpclQuery)->where('status', 'ditolak')->count();

        // Data untuk Chart Pie Status
        $statusLabels = ['Baru', 'Terverifikasi', 'Ditolak'];
        $statusData = [$countBaru, $countTerverifikasi, $countDitolak];

        // 3. Statistik Fuzzy & Ranking
        $countRanking = (clone $fuzzyQuery)->count();
        $avgSkor = (clone $fuzzyQuery)->avg('skor_akhir') ?? 0;

        // Ambil 5 Besar Skor Tertinggi
        $topRank = (clone $fuzzyQuery)
            ->with('cpcl')
            ->orderBy('skor_akhir', 'desc')
            ->limit(5)
            ->get();

        $topSkorLabels = $topRank->map(fn($item) => $item->cpcl?->nama_kelompok ?? 'Anonim');
        $topSkorData = $topRank->pluck('skor_akhir');

        // 4. Grafik Wilayah (Distribusi per Kecamatan)
        $dataLokasi = (clone $cpclQuery)
            ->leftJoin('alamat', 'cpcl.alamat_id', '=', 'alamat.id')
            ->select('alamat.kecamatan', DB::raw('count(cpcl.id) as total'))
            ->whereNotNull('alamat.kecamatan')
            ->groupBy('alamat.kecamatan')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $lokasiLabels = $dataLokasi->pluck('kecamatan');
        $lokasiData = $dataLokasi->pluck('total');

        // 5. Grafik Perbandingan Bidang
        $dataBidang = (clone $cpclQuery)
            ->select('bidang', DB::raw('count(*) as total'))
            ->groupBy('bidang')
            ->get();

        $bidangLabels = $dataBidang->pluck('bidang');
        $bidangData = $dataBidang->pluck('total');

        // 6. Grafik Tren Registrasi (KHUSUS MYSQL)
        $trenRegistrasi = (clone $cpclQuery)
            ->select(
                DB::raw("DATE_FORMAT(created_at, '%b %Y') as bulan"),
                DB::raw('count(*) as total'),
                DB::raw('MIN(created_at) as urutan')
            )
            ->groupBy(DB::raw("DATE_FORMAT(created_at, '%b %Y')"))
            ->orderBy('urutan', 'asc')
            ->limit(6)
            ->get();

        $trenLabels = $trenRegistrasi->pluck('bulan');
        $trenData = $trenRegistrasi->pluck('total');

        // 7. Metadata / Informasi Tambahan
        $jmlKriteria = Kriteria::count();
        $jmlSubKriteria = SubKriteria::count();

        // Menghitung jumlah kecamatan yang tercover berdasarkan filter role
        $jmlKecamatanTercover = Alamat::whereHas('cpcl', function ($q) use ($role) {
            if ($role === 'admin_pangan') {
                $q->where('bidang', 'pangan');
            } elseif ($role === 'admin_hartibun') {
                $q->where('bidang', 'hartibun');
            }
        })->distinct('kecamatan')->count();

        // 8. Return View dengan semua variabel
        return view('admin.dashboard.index', compact(
            'totalCpcl',
            'countBaru',
            'countTerverifikasi',
            'countDitolak',
            'countRanking',
            'avgSkor',
            'topRank',
            'lokasiLabels',
            'lokasiData',
            'bidangLabels',
            'bidangData',
            'statusLabels',
            'statusData',
            'topSkorLabels',
            'topSkorData',
            'trenLabels',
            'trenData',
            'jmlKriteria',
            'jmlSubKriteria',
            'jmlKecamatanTercover'
        ));
    }
}
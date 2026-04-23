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

        if ($role === 'admin_pangan') {
            $cpclQuery->where('bidang', 'pangan');
            $fuzzyQuery->whereHas('cpcl', fn($q) => $q->where('bidang', 'pangan'));
        } elseif ($role === 'admin_hartibun') {
            $cpclQuery->where('bidang', 'hartibun');
            $fuzzyQuery->whereHas('cpcl', fn($q) => $q->where('bidang', 'hartibun'));
        }

        // 2. Statistik
        $totalCpcl = (clone $cpclQuery)->count();
        $countBaru = (clone $cpclQuery)->where('status', 'baru')->count();
        $countTerverifikasi = (clone $cpclQuery)->where('status', 'terverifikasi')->count();
        $countDitolak = (clone $cpclQuery)->where('status', 'ditolak')->count();

        // 3. Grafik Status
        $statusLabels = ['Baru', 'Terverifikasi', 'Ditolak'];
        $statusData = [$countBaru, $countTerverifikasi, $countDitolak];

        // 4. Fuzzy
        $countRanking = (clone $fuzzyQuery)->count();
        $avgSkor = (clone $fuzzyQuery)->avg('skor_akhir') ?? 0;

        $topRank = (clone $fuzzyQuery)
            ->with('cpcl')
            ->orderBy('skor_akhir', 'desc')
            ->limit(5)
            ->get();

        $topSkorLabels = $topRank->map(fn($item) => $item->cpcl?->nama_kelompok ?? 'Anonim');
        $topSkorData = $topRank->pluck('skor_akhir');

        // 5. Grafik Wilayah
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

        // 6. Grafik Bidang
        $dataBidang = (clone $cpclQuery)
            ->select('bidang', DB::raw('count(*) as total'))
            ->groupBy('bidang')
            ->get();

        $bidangLabels = $dataBidang->pluck('bidang');
        $bidangData = $dataBidang->pluck('total');

        // 7. Grafik Tren (FIX PostgreSQL)
        $trenRegistrasi = (clone $cpclQuery)
            ->select(
                DB::raw("TO_CHAR(created_at, 'Mon YYYY') as bulan"),
                DB::raw('count(*) as total'),
                DB::raw('MIN(created_at) as urutan')
            )
            ->groupBy(DB::raw("TO_CHAR(created_at, 'Mon YYYY')"))
            ->orderBy('urutan', 'asc')
            ->limit(6)
            ->get();

        $trenLabels = $trenRegistrasi->pluck('bulan');
        $trenData = $trenRegistrasi->pluck('total');

        // 8. Metadata
        $jmlKriteria = Kriteria::count();
        $jmlSubKriteria = SubKriteria::count();

        $jmlKecamatanTercover = Alamat::whereHas('cpcl', function ($q) use ($role) {
            if ($role === 'admin_pangan') {
                $q->where('bidang', 'pangan');
            } elseif ($role === 'admin_hartibun') {
                $q->where('bidang', 'hartibun');
            }
        })->distinct('kecamatan')->count();

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
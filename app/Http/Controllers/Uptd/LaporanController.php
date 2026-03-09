<?php

namespace App\Http\Controllers\Uptd;

use App\Http\Controllers\Controller;
use App\Models\HasilFuzzy;
use App\Models\Cpcl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LaporanController extends Controller
{
    
    public function index(Request $request)
    {
        // Ambil daftar tahun unik dari data HasilFuzzy untuk filter
        $listTahun = HasilFuzzy::select(DB::raw('YEAR(created_at) as tahun'))
                                ->distinct()
                                ->pluck('tahun')
                                ->sort()
                                ->reverse();

        // Ambil daftar lokasi/kecamatan unik
        $listLokasi = Cpcl::where('status', 'terverifikasi')
                          ->distinct()
                          ->pluck('lokasi')
                          ->sort();

        // Ambil daftar bidang unik
        $listBidang = Cpcl::where('status', 'terverifikasi')
                          ->distinct()
                          ->pluck('bidang')
                          ->sort();

        // Query Utama dengan Filter
        $query = HasilFuzzy::with('cpcl')
                           ->whereHas('cpcl', function($q) {
                               $q->where('status', 'terverifikasi');
                           });

        // Filter Tahun
        if ($request->tahun) {
            $query->whereYear('created_at', $request->tahun);
        }

        // Filter Lokasi
        if ($request->lokasi) {
            $query->whereHas('cpcl', function($q) use ($request) {
                $q->where('lokasi', $request->lokasi);
            });
        }

        // Filter Bidang
        if ($request->bidang) {
            $query->whereHas('cpcl', function($q) use ($request) {
                $q->where('bidang', $request->bidang);
            });
        }

        // Get Data Sorted by Ranking
        $dataSK = $query->orderBy('ranking', 'asc')->get();

        // Calculate Statistics
        $totalKelompok = $dataSK->count();
        $totalLuas = $dataSK->sum(function($item) {
            return $item->cpcl->luas_lahan ?? 0;
        });

        // Distribusi Prioritas
        $prioritasDistribusi = [];
        $prioritasDistribusi['Prioritas I'] = $dataSK->where('skala_prioritas', 'Prioritas I')->count();
        $prioritasDistribusi['Prioritas II'] = $dataSK->where('skala_prioritas', 'Prioritas II')->count();
        $prioritasDistribusi['Prioritas III'] = $dataSK->where('skala_prioritas', 'Prioritas III')->count();
        $prioritasDistribusi['Prioritas IV'] = $dataSK->where('skala_prioritas', 'Prioritas IV')->count();

        // Distribusi Bidang
        $bidangDistribusi = [];
        foreach ($dataSK as $hasil) {
            $bidang = $hasil->cpcl->bidang;
            if (!isset($bidangDistribusi[$bidang])) {
                $bidangDistribusi[$bidang] = 0;
            }
            $bidangDistribusi[$bidang]++;
        }

        $totalPrioritas1 = $prioritasDistribusi['Prioritas I'];

        return view('uptd.laporan.index', [
            'dataSK' => $dataSK,
            'listTahun' => $listTahun,
            'listBidang' => $listBidang,
            'listLokasi' => $listLokasi,
            'totalKelompok' => $totalKelompok,
            'totalLuas' => number_format($totalLuas, 2),
            'totalPrioritas1' => $totalPrioritas1,
            'prioritasDistribusi' => $prioritasDistribusi,
            'bidangDistribusi' => $bidangDistribusi,
        ]);
    }

    
    public function print(Cpcl $cpcl)
    {
        // Load hasil fuzzy terbaru untuk CPCL ini
        $hasilFuzzy = $cpcl->hasilFuzzy()->first();

        if (!$hasilFuzzy) {
            abort(404, 'Data hasil penilaian tidak ditemukan');
        }

        // Generate nomor SK jika belum ada
        $nomorSK = $this->generateNomorSK($cpcl, $hasilFuzzy);
        $tanggalSK = now()->translatedFormat('d F Y');

        return view('uptd.laporan.cetak-sk', [
            'cpcl' => $cpcl,
            'hasilFuzzy' => $hasilFuzzy,
            'nomorSK' => $nomorSK,
            'tanggalSK' => $tanggalSK,
        ]);
    }

    /**
     * Print SK untuk multiple kelompok tani (bulk print)
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function printBulk(Request $request)
    {
        $cpclIds = $request->input('cpcl_ids', []);
        
        if (empty($cpclIds)) {
            return redirect()->route('uptd.laporan.index')
                          ->with('error', 'Pilih minimal satu kelompok tani');
        }

        // Ambil data kelompok yang dipilih
        $cpclList = Cpcl::whereIn('id', $cpclIds)
                        ->with('hasilFuzzy')
                        ->get();

        $skList = [];
        foreach ($cpclList as $cpcl) {
            $hasilFuzzy = $cpcl->hasilFuzzy;
            if ($hasilFuzzy) {
                $skList[] = [
                    'cpcl' => $cpcl,
                    'hasilFuzzy' => $hasilFuzzy,
                    'nomorSK' => $this->generateNomorSK($cpcl, $hasilFuzzy),
                    'tanggalSK' => now()->translatedFormat('d F Y'),
                ];
            }
        }

        return view('uptd.laporan.cetak-sk-bulk', [
            'skList' => $skList,
        ]);
    }

    
    public function show(Cpcl $cpcl)
    {
        // Load hasil fuzzy terbaru untuk CPCL ini
        $hasilFuzzy = $cpcl->hasilFuzzy()->first();

        if (!$hasilFuzzy) {
            abort(404, 'Data hasil penilaian tidak ditemukan');
        }

        return view('uptd.laporan.detail-sk', [
            'cpcl' => $cpcl,
            'hasilFuzzy' => $hasilFuzzy,
        ]);
    }

    /**
     * Generate nomor SK otomatis
     * Format: NOMOR/SK/UPTD/TAHUN
     *
     * @param Cpcl $cpcl
     * @param HasilFuzzy $hasilFuzzy
     * @return string
     */
    private function generateNomorSK(Cpcl $cpcl, HasilFuzzy $hasilFuzzy)
    {
        // Ambil ranking
        $ranking = $hasilFuzzy->ranking ?? 1;
        $tahun = now()->year;

        // Format: 001/SK/UPTD/2024
        $nomor = str_pad($ranking, 3, '0', STR_PAD_LEFT);
        
        return "{$nomor}/SK/UPTD/{$tahun}";
    }

    /**
     * Download SK sebagai PDF (jika menggunakan package)
     *
     * @param Cpcl $cpcl
     * @return \Illuminate\Http\Response
     */
    public function downloadPDF(Cpcl $cpcl)
    {
        // Implementasi menggunakan barryvdh/laravel-dompdf atau similar
        // Untuk sekarang, gunakan print() dan print to PDF dari browser
        
        return $this->print($cpcl);
    }

    /**
     * Mark SK as printed/terbitkan
     *
     * @param Cpcl $cpcl
     * @return \Illuminate\Http\RedirectResponse
     */
    public function markAsPrinted(Cpcl $cpcl)
    {
        $hasilFuzzy = $cpcl->hasilFuzzy()->first();

        if ($hasilFuzzy) {
            // Tandai sebagai sudah diterbitkan (jika ada field status_sk)
            // $hasilFuzzy->update(['status_sk' => 'terbitkan']);
        }

        return redirect()->route('uptd.laporan-sk')
                       ->with('success', 'SK telah ditandai sebagai terbitkan');
    }
}
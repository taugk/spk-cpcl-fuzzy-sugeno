<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cpcl;
use App\Models\HasilFuzzy;
use App\Services\FuzzySugenoService;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class FuzzyController extends Controller
{
    /**
     * Helper untuk memfilter query berdasarkan role user.
     * Pastikan kolom 'bidang' sesuai dengan yang ada di tabel cpcl Anda.
     */
    private function filterByRole($query)
    {
        $user = Auth::user();
        
        if ($user->role === 'admin_pangan') {
            return $query->where('bidang', 'pangan'); // Sesuaikan value 'pangan'
        } elseif ($user->role === 'admin_hartibun') {
            return $query->where('bidang', 'hartibun'); // Sesuaikan value 'hartibun'
        }
        
        // Jika role 'admin' (super), tidak difilter (bisa lihat semua)
        return $query;
    }

    public function kriteria()
    {
        return view('admin.fuzzy-sugeno.kriteria.index');
    }

    public function subKriteria()
    {
        return view('admin.fuzzy-sugeno.sub-kriteria.index');
    }

    public function perhitungan(Request $request)
{
    // Mengambil periode dari request, default tahun saat ini
    $periode = $request->get('periode', date('Y'));

    // 1. Hitung CPCL Terverifikasi yang BELUM dihitung (Filtered by Role)
    // Menambahkan filter agar admin bidang hanya melihat data bidangnya sendiri
    $queryCount = Cpcl::where('status', 'terverifikasi')
        ->whereYear('created_at', $periode)
        ->whereDoesntHave('hasilFuzzy');
    
    $cpclVerifiedIsNotCount = $this->filterByRole($queryCount)->count();

    // 2. Daftar Periode (Hanya tampilkan tahun yang memiliki data hasil fuzzy)
    $queryPeriode = HasilFuzzy::join('cpcl', 'hasil_fuzzy.cpcl_id', '=', 'cpcl.id');
    $queryPeriode = $this->filterByRole($queryPeriode);
    
    $periodeList = $queryPeriode->selectRaw('YEAR(cpcl.created_at) as tahun')
        ->distinct()
        ->orderByDesc('tahun')
        ->pluck('tahun');

    // Pastikan tahun berjalan ada di list filter meskipun belum ada data
    if (!$periodeList->contains(date('Y'))) {
        $periodeList->prepend(date('Y'));
    }

    // 3. Ambil Hasil Ranking (Filtered by Role)
    // Menambahkan Eager Loading 'cpcl.alamat' untuk mengambil data Desa & Kecamatan
    $queryRanking = HasilFuzzy::with(['cpcl.alamat']) 
        ->join('cpcl', 'hasil_fuzzy.cpcl_id', '=', 'cpcl.id')
        ->whereYear('cpcl.created_at', $periode)
        ->whereNotNull('hasil_fuzzy.ranking');
    
    $hasilRanking = $this->filterByRole($queryRanking)
        // Diurutkan berdasarkan skor_akhir tertinggi (Descending)
        ->orderBy('hasil_fuzzy.skor_akhir', 'desc') 
        ->select('hasil_fuzzy.*')
        ->get();

    // 4. Hitung Total Terverifikasi untuk info badge (Filtered by Role)
    $queryTotal = Cpcl::where('status', 'terverifikasi')
        ->whereYear('created_at', $periode);
    
    $totalTerverifikasi = $this->filterByRole($queryTotal)->count();

    $totalBelumDihitung = $cpclVerifiedIsNotCount;

    // Mengirimkan data ke View
    return view('admin.fuzzy-sugeno.perhitungan.index', compact(
        'hasilRanking', 
        'periode', 
        'periodeList',
        'totalTerverifikasi', 
        'totalBelumDihitung',
        'cpclVerifiedIsNotCount'
    ));
}

    public function proses(Request $request)
    {
        $request->validate([
            'periode' => 'required|digits:4|integer',
        ]);

        $periode = $request->input('periode');
        $user = Auth::user();

        try {
            // Pastikan Service Anda mendukung parameter filter bidang jika diperlukan
            // Atau service akan memproses data yang dikirimkan
            // Di sini kita asumsikan hitungSemuaDanRanking diproses berdasarkan periode
            // Namun untuk keamanan, Anda bisa memodifikasi service agar menerima 'bidang'
            
            $bidang = null;
            if ($user->role === 'admin_pangan') $bidang = 'pangan';
            if ($user->role === 'admin_hartibun') $bidang = 'hartibun';

            // Contoh pemanggilan jika service Anda mendukung filter bidang:
            $ranked = FuzzySugenoService::hitungSemuaDanRanking($periode, $bidang);

            if ($ranked->isEmpty()) {
                return back()->with('warning', 'Tidak ada data ' . ($bidang ?? '') . ' berstatus "terverifikasi" untuk periode ' . $periode);
            }

            return redirect()
                ->route('admin.perhitungan.index', ['periode' => $periode])
                ->with('success', "Berhasil menghitung {$ranked->count()} data CPCL bidang ".($bidang ?? 'Semua')." periode {$periode}.");

        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal memproses: ' . $e->getMessage());
        }
    }

    public function detail($id)
    {
        // Temukan CPCL, pastikan user tidak bisa mengintip ID milik bidang lain
        $query = Cpcl::where('id', $id);
        $cpcl = $this->filterByRole($query)->firstOrFail();

        if ($cpcl->status !== 'terverifikasi') {
            return redirect()
                ->route('admin.perhitungan.index')
                ->with('warning', "CPCL #{$id} ({$cpcl->nama_kelompok}) belum terverifikasi.");
        }

        $sinkron = FuzzySugenoService::cekSinkronisasiData($id);
        if (!$sinkron['is_valid']) {
            session()->flash('sync_error', $sinkron['messages']);
        }

        $hasil = FuzzySugenoService::hitung($id);

        return view('admin.fuzzy-sugeno.perhitungan.detail', compact('hasil'));
    }

    public function historisPerhitungan(Request $request)
    {
        $user = Auth::user();

        // 1. Tentukan filter bidang berdasarkan role user
        $roleBidang = null;
        if ($user->role === 'admin_pangan') {
            $roleBidang = 'Pangan';
        } elseif ($user->role === 'admin_hartibun') {
            $roleBidang = 'Hartibun';
        }

        // 2. Ambil daftar tahun unik (Gunakan Carbon untuk mendapatkan tahun berjalan jika perlu)
        $queryTahun = Cpcl::select(DB::raw('YEAR(created_at) as tahun'))->distinct();
        if ($roleBidang) {
            $queryTahun->where('bidang', $roleBidang);
        }
        $listTahun = $queryTahun->pluck('tahun');

        // Menggunakan Carbon untuk mendapatkan tahun saat ini secara aman
        $tahunSekarang = Carbon::now()->year; 
        if (!$listTahun->contains($tahunSekarang)) {
            $listTahun->prepend($tahunSekarang);
        }

        // 3. Ambil daftar lokasi/kecamatan unik
        $queryLokasi = Cpcl::distinct();
        if ($roleBidang) {
            $queryLokasi->where('bidang', $roleBidang);
        }
        $listLokasi = $queryLokasi->pluck('lokasi');

        // 4. Ambil daftar bidang unik 
        if ($roleBidang) {
            $listBidang = collect([$roleBidang]);
        } else {
            $listBidang = Cpcl::distinct()->pluck('bidang');
        }

        // 5. Query Utama Hasil Fuzzy
        $query = HasilFuzzy::with(['cpcl.alamat'])
            ->where('status_kelayakan', 'Dipertimbangkan');

        if ($roleBidang) {
            $query->whereHas('cpcl', function($q) use ($roleBidang) {
                $q->where('bidang', $roleBidang);
            });
        }

        // Filter Tambahan
        if ($request->tahun) {
            $query->whereHas('cpcl', function($q) use ($request) {
                $q->whereYear('created_at', $request->tahun);
            });
        }
        
        if ($request->lokasi) {
            $query->whereHas('cpcl', function($q) use ($request) {
                $q->where('lokasi', $request->lokasi);
            });
        }
        
        if ($request->bidang && !$roleBidang) {
            $query->whereHas('cpcl', function($q) use ($request) {
                $q->where('bidang', $request->bidang);
            });
        }

        $data = $query->orderBy('skor_akhir', 'desc')->get();

        return view('admin.fuzzy-sugeno.perhitungan.historis', compact(
            'data', 
            'listTahun', 
            'listLokasi', 
            'listBidang'
        ));
    }
            
}
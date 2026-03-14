<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cpcl;
use App\Models\HasilFuzzy;
use App\Services\FuzzySugenoService;
use Illuminate\Http\Request;

class FuzzyController extends Controller
{
    /**
     * Tampilkan halaman kriteria.
     */
    public function kriteria()
    {
        return view('admin.fuzzy-sugeno.kriteria.index');
    }

    /**
     * Tampilkan halaman sub-kriteria.
     */
    public function subKriteria()
    {
        return view('admin.fuzzy-sugeno.sub-kriteria.index');
    }

    /**
     * Tampilkan halaman perhitungan dan ranking.
     */
    public function perhitungan(Request $request)
    {
        $periode = $request->get('periode', date('Y'));

        // Ambil data CPCL yang sudah verifikasi tapi belum dihitung/masuk ke tabel hasil_fuzzy
        // Pastikan model Cpcl memiliki relasi 'hasilFuzzy'
        $cpclVerifiedIsNotCount = Cpcl::where('status', 'terverifikasi')
            ->whereYear('created_at', $periode)
            ->whereDoesntHave('hasilFuzzy') 
            ->count();

        // Ambil daftar tahun yang tersedia
        $periodeList = HasilFuzzy::join('cpcl', 'hasil_fuzzy.cpcl_id', '=', 'cpcl.id')
            ->selectRaw('YEAR(cpcl.created_at) as tahun')
            ->distinct()
            ->orderByDesc('tahun')
            ->pluck('tahun');

        if (!$periodeList->contains(date('Y'))) {
            $periodeList->prepend(date('Y'));
        }

        // Ambil hasil ranking
        $hasilRanking = HasilFuzzy::with('cpcl')
            ->join('cpcl', 'hasil_fuzzy.cpcl_id', '=', 'cpcl.id')
            ->whereYear('cpcl.created_at', $periode)
            ->whereNotNull('hasil_fuzzy.ranking')
            ->orderBy('hasil_fuzzy.ranking')
            ->select('hasil_fuzzy.*')
            ->get();

        // Hitung total CPCL terverifikasi
        $totalTerverifikasi = Cpcl::where('status', 'terverifikasi')
            ->whereYear('created_at', $periode)
            ->count();

        // Total yang belum dihitung (bisa menggunakan variabel di atas atau hitungan manual)
        $totalBelumDihitung = $cpclVerifiedIsNotCount;

        return view('admin.fuzzy-sugeno.perhitungan.index', compact(
            'hasilRanking', 
            'periode', 
            'periodeList',
            'totalTerverifikasi', 
            'totalBelumDihitung',
            'cpclVerifiedIsNotCount'
        ));
    }

    /**
     * Proses hitung semua CPCL terverifikasi dan simpan ranking.
     */
    public function proses(Request $request)
    {
        $request->validate([
            'periode' => 'required|digits:4|integer',
        ]);

        $periode = $request->input('periode');

        try {
            $ranked = FuzzySugenoService::hitungSemuaDanRanking($periode);

            if ($ranked->isEmpty()) {
                return back()->with('warning', 'Tidak ada CPCL berstatus "terverifikasi" untuk periode ' . $periode . '.');
            }

            return redirect()
                ->route('admin.perhitungan.index', ['periode' => $periode])
                ->with('success', "Berhasil menghitung dan meranking {$ranked->count()} CPCL untuk periode {$periode}.");

        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal memproses: ' . $e->getMessage());
        }
    }

    /**
     * Tampilkan detail langkah-langkah fuzzy untuk 1 CPCL.
     */
    public function detail(int $id)
{
    $cpcl = Cpcl::findOrFail($id);

    if ($cpcl->status !== 'terverifikasi') {
        return redirect()
            ->route('admin.perhitungan.index')
            ->with('warning', "CPCL #{$id} ({$cpcl->nama_kelompok}) belum terverifikasi.");
    }

    // ✅ CEK SINKRONISASI DATA (Validator yang kita buat tadi)
    $sinkron = FuzzySugenoService::cekSinkronisasiData($id);
    if (!$sinkron['is_valid']) {
        session()->flash('sync_error', $sinkron['messages']);
    }

    // Ambil hasil perhitungan
    $hasil = FuzzySugenoService::hitung($id);

    return view('admin.fuzzy-sugeno.perhitungan.detail', compact('hasil'));
}
}
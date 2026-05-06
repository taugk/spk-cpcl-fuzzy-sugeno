<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HasilFuzzy;
use App\Models\Cpcl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class LaporanController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        // 1. Logika Penentuan Bidang Berdasarkan Role
        $roleBidang = null;
        if ($user->role === 'admin_pangan') {
            $roleBidang = 'Pangan';
        } elseif ($user->role === 'admin_hartibun') {
            $roleBidang = 'Hartibun';
        }

        // 2. Ambil daftar tahun unik (Filtered by Role)
        $queryTahun = Cpcl::select(DB::raw('YEAR(created_at) as tahun'))->distinct();
        if ($roleBidang) {
            $queryTahun->where('bidang', $roleBidang);
        }
        $listTahun = $queryTahun->pluck('tahun');

        // 3. Ambil daftar lokasi/kecamatan unik (Filtered by Role)
        $queryLokasi = Cpcl::distinct();
        if ($roleBidang) {
            $queryLokasi->where('bidang', $roleBidang);
        }
        $listLokasi = $queryLokasi->pluck('lokasi');

        // 4. Ambil daftar bidang unik 
        // Jika Admin Bidang, maka list bidang hanya berisi bidang dia sendiri
        if ($roleBidang) {
            $listBidang = collect([$roleBidang]);
        } else {
            $listBidang = Cpcl::distinct()->pluck('bidang');
        }

        // 5. Query Utama Hasil Fuzzy dengan Proteksi Role
        $query = HasilFuzzy::with(['cpcl.alamat']);

        // Filter WAJIB berdasarkan Role Admin
        if ($roleBidang) {
            $query->whereHas('cpcl', function($q) use ($roleBidang) {
                $q->where('bidang', $roleBidang);
            });
        }

        // Filter Tambahan dari Request User (UI)
        if ($request->tahun) {
            $query->whereYear('created_at', $request->tahun);
        }
        if ($request->lokasi) {
            $query->whereHas('cpcl', function($q) use ($request) {
                $q->where('lokasi', $request->lokasi);
            });
        }
        
        // Hanya jalankan filter bidang manual jika user adalah Super Admin
        if ($request->bidang && !$roleBidang) {
            $query->whereHas('cpcl', function($q) use ($request) {
                $q->where('bidang', $request->bidang);
            });
        }

        $data = $query->orderBy('ranking', 'asc')->get();

        return view('admin.laporan.index', compact('data', 'listTahun', 'listLokasi', 'listBidang'));
    }
}
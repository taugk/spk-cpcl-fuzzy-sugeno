<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Exports\CpclExport;
use App\Imports\CpclImport;
use App\Models\Cpcl;
use App\Models\Kriteria;
use App\Services\CpclService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class CpclController extends Controller
{
    protected CpclService $cpclService;

    public function __construct(CpclService $cpclService)
    {
        $this->cpclService = $cpclService;
    }

    /**
     * Menentukan prefix folder view berdasarkan role.
     * Admin, Admin Pangan, dan Admin Hartibun menggunakan folder 'admin'.
     */
    private function getRolePrefix(): string
    {
        $role = Auth::user()->role;
        $adminRoles = ['admin', 'admin_pangan', 'admin_hartibun'];
        
        return in_array($role, $adminRoles) ? 'admin' : 'uptd';
    }

    /**
     * SECURITY SCOPE: Inti dari pembatasan data per bidang.
     * Digunakan di semua query (index, detail, edit, delete).
     */
    private function secureQuery()
    {
        $user = Auth::user();
        $query = Cpcl::query()->with('alamat');

        // Filter berdasarkan Role Bidang
        if ($user->role === 'admin_pangan') {
            $query->where('bidang', 'PANGAN');
        } elseif ($user->role === 'admin_hartibun') {
            $query->where('bidang', 'HARTIBUN');
        }

        // Jika role 'admin' (Super Admin), query tidak difilter (tampil semua).
        return $query;
    }

    // =========================================================================
    // INDEX & FILTER
    // =========================================================================

    public function index(Request $request)
    {
        $role  = $this->getRolePrefix();
        $query = $this->secureQuery(); // Otomatis filter bidang sesuai login

        $this->applyFilters($query, $request);

        $data = $query->latest()->paginate(10)->withQueryString();
        return view("$role.data-cpcl.index", compact('data'));
    }

    public function verified(Request $request)
    {
        $role  = $this->getRolePrefix();
        $query = $this->secureQuery()->where('status', 'terverifikasi');

        $this->applyFilters($query, $request);

        $data = $query->latest()->paginate(10)->withQueryString();
        return view("$role.data-cpcl.terverifikasi", compact('data'));
    }

    public function belum(Request $request)
{
    $role  = $this->getRolePrefix();
    $query = $this->secureQuery()
                  ->whereNotIn('status', ['terverifikasi', 'ditolak', 'perlu_perbaikan']);

    // Logika Search Tanpa LIKE (Exact Match untuk Indexing)
    if ($request->filled('search')) {
        $search = $request->search;
        $query->where(function($q) use ($search) {
            // Menggunakan '=' agar database menggunakan B-Tree Index secara optimal
            $q->where('nama_kelompok', $search)
              ->orWhere('nik_ketua', $search);
        });
    }

    $this->applyFilters($query, $request);

    $data = $query->latest()->paginate(10)->withQueryString();

    // Cek jika request adalah AJAX
    if ($request->ajax()) {
        return view("$role.data-cpcl.belum-verifikasi", compact('data'));
    }

    return view("$role.data-cpcl.belum-verifikasi", compact('data'));
}

    public function perbaikan(Request $request)
    {
        $role  = $this->getRolePrefix();

        $query = $this->secureQuery()->where('status', 'perlu_perbaikan');
        $this->applyFilters($query, $request);


        $data = $query->latest()->paginate(10)->withQueryString();


        return view("$role.data-cpcl.perbaikan", compact('data'));
    }

    public function ditolak(Request $request)
    {
        $role  = $this->getRolePrefix();

        

        $query = $this->secureQuery()->where('status', 'ditolak');
        $this->applyFilters($query, $request);

        $data = $query->latest()->paginate(10)->withQueryString();

        return view("$role.data-cpcl.ditolak", compact('data'));
    }

    private function applyFilters($query, Request $request): void
    {
        if ($request->filled('kecamatan')) {
            $query->whereHas('alamat', fn($q) =>
                $q->where('kecamatan', 'LIKE', '%' . $request->kecamatan . '%')
            );
        }

        if ($request->filled('rencana_usaha')) {
            $query->where('rencana_usaha', 'LIKE', '%' . $request->rencana_usaha . '%');
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn($q) => $q
                ->where('nama_kelompok', 'LIKE', "%$s%")
                ->orWhere('nama_ketua',  'LIKE', "%$s%")
                ->orWhere('nik_ketua',   'LIKE', "%$s%")
            );
        }
    }

    // =========================================================================
    // CRUD VIEWS (With Scoping Security)
    // =========================================================================

    public function create()
    {
        $role = $this->getRolePrefix();
        return view("$role.data-cpcl.form");
    }

    public function edit($id)
    {
        $role = $this->getRolePrefix();
        // findOrFail akan 404 jika admin pangan mencoba akses ID hartibun
        $cpcl = $this->secureQuery()->findOrFail($id);
        
        return view("$role.data-cpcl.form", compact('cpcl'));
    }

    public function detail($id)
    {
        $role = $this->getRolePrefix();
        $cpcl = $this->secureQuery()->findOrFail($id);
        
        return view("$role.data-cpcl.detail", compact('cpcl'));
    }

    public function laporan()
    {
        $role = $this->getRolePrefix();
        $data = $this->secureQuery()
                    ->where('status', 'terverifikasi')
                    ->latest()
                    ->get();
                    
        return view("$role.data-cpcl.laporan", compact('data'));
    }

    // =========================================================================
    // STORE & UPDATE
    // =========================================================================

    public function store(Request $request)
    {
        $role = $this->getRolePrefix();
        $this->validateCpcl($request, false);

        try {
            $this->cpclService->storeCpcl($request->all());
            return redirect()->route("$role.cpcl.index")->with('success', 'Data CPCL berhasil ditambahkan');
        } catch (\Throwable $e) {
            Log::error('CPCL STORE FAILED: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Gagal menyimpan data');
        }
    }

    public function update(Request $request, $id)
    {
        $role = $this->getRolePrefix();
        
        // Pastikan record yang diupdate adalah milik bidang si admin
        $this->secureQuery()->findOrFail($id);
        
        $this->validateCpcl($request, true);

        try {
            $this->cpclService->updateCpcl($id, $request->all());
            return redirect()->route("$role.cpcl.index")->with('success', 'Data CPCL berhasil diperbarui');
        } catch (\Throwable $e) {
            Log::error('CPCL UPDATE FAILED: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Gagal memperbarui data');
        }
    }

public function export(Request $request)
{
    $user    = Auth::user();
    $role    = $user->role;
    $page    = $request->input('page_context');
    $filters = $request->only(['kecamatan', 'rencana_usaha', 'search']);

    if (!in_array($role, ['admin', 'admin_pangan', 'admin_hartibun'])) {
        return back()->with('error', 'Anda tidak memiliki akses untuk mengekspor data.');
    }

    $pageLabel = match ($page) {
        'terverifikasi' => 'Terverifikasi',
        'belum'         => 'Belum-Verifikasi',
        'perbaikan'     => 'Perlu-Perbaikan',
        'ditolak'       => 'Ditolak',
        default         => 'Semua-Data',
    };

    $filename = "Export-CPCL-{$pageLabel}-" . now()->format('Ymd-His') . ".xlsx";

    // Tambahkan cookie sebagai sinyal bahwa export selesai
    cookie()->queue(cookie('export_done', '1', 1)); // expire 1 menit

    return (new CpclExport($role, $page, $filters))->download($filename);
}

    private function validateCpcl(Request $request, bool $isUpdate = false): array
    {
        return $request->validate([
            'nama_kelompok' => 'required|string|max:255',
            'nama_ketua'    => 'required|string|max:255',
            'nik_ketua'     => 'required|digits:16',
            'bidang'        => 'required|in:HARTIBUN,PANGAN',
            'rencana_usaha' => 'required|string',
            'kd_kab'        => 'required|string',
            'kabupaten'     => 'required|string',
            'kd_kec'        => 'required|string',
            'kecamatan'     => 'required|string',
            'kd_desa'       => 'required|string',
            'desa'          => 'required|string',
            'lokasi'        => 'required|string',
            'luas_lahan'    => 'required|numeric|min:0',
            'lama_berdiri'  => 'required|integer|min:0',
            'hasil_panen'   => 'required|numeric|min:0',
            'status_lahan'  => 'required|in:milik,sewa,tidak_memiliki',
            'file_proposal' => ($isUpdate ? 'nullable' : 'required') . '|mimes:pdf|max:2048',
            'file_ktp'      => ($isUpdate ? 'nullable' : 'required') . '|mimes:pdf,jpg,jpeg,png|max:2048',
            'file_sk'       => 'nullable|mimes:pdf|max:2048',
            'foto_lahan'    => 'nullable|image|mimes:jpg,jpeg,png|max:3072',
        ]);
    }

    // =========================================================================
    // VERIFIKASI
    // =========================================================================

    public function showVerification($id)
    {
        $role = $this->getRolePrefix();
        
        // Gunakan 'penilaian' untuk Eager Loading
        $cpcl = $this->secureQuery()
            ->with(['Penilaian', 'alamat']) 
            ->findOrFail($id);

        $kriteria = Kriteria::with('subKriteria')->get();
        
        return view("$role.data-cpcl.verifikasi", compact('cpcl', 'kriteria'));
    }

    public function verify(Request $request, $id)
{
    $role = $this->getRolePrefix();
    
    // 1. Cek otorisasi (Akan melempar 404 jika ID tidak ditemukan atau tidak sesuai bidang)
    $this->secureQuery()->findOrFail($id);

    // 2. Validasi input
    $request->validate([
        'nilai'               => 'required|array',
        'nilai.*'             => 'required', // Tambahan: Memastikan setiap kriteria di dalam array ada isinya
        'status'              => 'required|in:terverifikasi,ditolak,baru,perlu_perbaikan',
        'catatan_verifikator' => 'nullable|string|max:500',
    ], [
        // Custom message agar user tahu kriteria mana yang belum diisi
        'nilai.*.required' => 'Semua nilai kriteria wajib diisi sebelum verifikasi.',
    ]);

    try {
        // 3. Eksekusi Service (Logika updateOrInsert ada di sini)
        $this->cpclService->verifyCpcl($id, $request->all());

        // 4. Redirect dengan feedback sukses
        return redirect()
            ->route("$role.cpcl.index")
            ->with('success', 'Data verifikasi CPCL berhasil diperbarui');

    } catch (\Throwable $e) {
        // 5. Logging Error yang lebih detail untuk admin/developer
        Log::error('CPCL VERIFICATION FAILED', [
            'cpcl_id' => $id,
            'user_id' => auth()->id(),
            'message' => $e->getMessage(),
            'file'    => $e->getFile(),
            'line'    => $e->getLine()
        ]);

        // Kembali ke halaman sebelumnya dengan input yang sudah diketik user
        return back()
            ->withInput()
            ->with('error', 'Gagal memverifikasi data. Silakan coba lagi atau hubungi admin.');
    }
}

    public function import(Request $request)
{
    try {

        $request->validate([
            'file_excel' => 'required|file|mimes:xlsx,xls,csv'
        ]);

        Excel::import(
            new CpclImport,
            $request->file('file_excel')
        );

        return response()->json([
            'success' => true,
            'message' => 'Data CPCL berhasil diimport'
        ]);

    } catch (\Exception $e) {

        Log::error('Import CPCL gagal', [
            'error' => $e->getMessage()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Gagal import: ' . $e->getMessage()
        ], 500);
    }
}

    // =========================================================================
    // DELETE
    // =========================================================================

    public function destroy($id)
    {
        $role = $this->getRolePrefix();
        try {
            // Gunakan secureQuery agar admin bidang tidak bisa hapus data bidang lain via script/URL
            $cpcl = $this->secureQuery()->findOrFail($id);
            $this->cpclService->deleteCpcl($cpcl->id);
            
            return redirect()->route("$role.cpcl.index")->with('success', 'Data CPCL berhasil dihapus');
        } catch (\Throwable $e) {
            Log::error('CPCL DELETE FAILED: ' . $e->getMessage());
            return back()->with('error', 'Gagal menghapus data');
        }
    }
public function truncate()
{
    $user = auth()->user();
    $role = $user->role;

    // Pastikan hanya role tertentu yang bisa menghapus massal
    if (!in_array($role, ['admin', 'admin_pangan', 'admin_hartibun'])) {
        return redirect()->back()->with('error', 'Anda tidak memiliki akses.');
    }

    try {
        // 1. Tentukan query dasar untuk mengambil data yang akan dihapus
        $query = Cpcl::query();
        if ($role === 'admin_pangan') {
            $query->where('bidang', 'PANGAN');
        } elseif ($role === 'admin_hartibun') {
            $query->where('bidang', 'HARTIBUN');
        }
        // Jika role 'admin' -> $query tidak diberi filter (semua data)

        // 2. Ambil data yang akan dihapus (untuk menghapus file fisik)
        $cpcls = $query->get(['id', 'file_proposal', 'file_ktp', 'file_sk', 'foto_lahan']);

        // 3. Hapus file fisik dari storage
        foreach ($cpcls as $cpcl) {
            $storage = Storage::disk('public');
            $files = ['file_proposal', 'file_ktp', 'file_sk', 'foto_lahan'];
            foreach ($files as $file) {
                if ($cpcl->$file && $storage->exists($cpcl->$file)) {
                    $storage->delete($cpcl->$file);
                }
            }
        }

        // 4. Hapus data dari database (perhatikan foreign key)
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Hapus dari tabel relasi terlebih dahulu (sesuaikan dengan struktur Anda)
        // Misal: cpcl_penilaian, hasil_fuzzy, dll.
        if ($role === 'admin') {
            // Admin super: hapus semua
            DB::table('hasil_fuzzy')->truncate();
            DB::table('cpcl_penilaian')->truncate();
            DB::table('cpcl')->truncate();
        } else {
            // Admin bidang: hapus hanya data yang sesuai role
            $ids = $cpcls->pluck('id')->toArray();
            if (!empty($ids)) {
                DB::table('hasil_fuzzy')->whereIn('cpcl_id', $ids)->delete();
                DB::table('cpcl_penilaian')->whereIn('cpcl_id', $ids)->delete();
                DB::table('cpcl')->whereIn('id', $ids)->delete();
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        return redirect()->route('admin.cpcl.index')->with('success', 'Data sesuai bidang berhasil dihapus.');

    } catch (\Exception $e) {
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        Log::error("DELETE BY ROLE ERROR: " . $e->getMessage());
        return redirect()->back()->with('error', 'Gagal menghapus data: ' . $e->getMessage());
    }
}
}
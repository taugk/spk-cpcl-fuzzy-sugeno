<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cpcl;
use App\Models\Kriteria;
use App\Services\CpclService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

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
        $query = $this->secureQuery()->where('status', '!=', 'terverifikasi' )->where('status', '!=', 'ditolak')->where('status', '!=', 'perlu_perbaikan');

        $this->applyFilters($query, $request);

        $data = $query->latest()->paginate(10)->withQueryString();
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
            'status_lahan'  => 'required|in:milik,sewa,bagi_hasil',
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
        $role     = $this->getRolePrefix();
        $cpcl     = $this->secureQuery()->findOrFail($id);
        $kriteria = Kriteria::with('subKriteria')->get();
        
        return view("$role.data-cpcl.verifikasi", compact('cpcl', 'kriteria'));
    }

    public function verify(Request $request, $id)
    {
        $role = $this->getRolePrefix();
        
        // Cek otorisasi bidang sebelum proses verifikasi
        $this->secureQuery()->findOrFail($id);

        $request->validate([
            'nilai'               => 'required|array',
            'status'              => 'required|in:terverifikasi,ditolak,baru,perlu_perbaikan',
            'catatan_verifikator' => 'nullable|string|max:500',
        ]);

        try {
            $this->cpclService->verifyCpcl($id, $request->all());
            return redirect()->route("$role.cpcl.index")->with('success', 'Status CPCL berhasil diperbarui');
        } catch (\Throwable $e) {
            Log::error('CPCL VERIFICATION FAILED: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Gagal memverifikasi data');
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
}
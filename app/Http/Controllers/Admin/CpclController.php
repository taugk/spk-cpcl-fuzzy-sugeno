<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cpcl;
use App\Models\Kriteria;
use App\Services\CpclService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CpclController extends Controller
{
    protected CpclService $cpclService;

    public function __construct(CpclService $cpclService)
    {
        $this->cpclService = $cpclService;
    }

    private function getRolePrefix()
    {
        return Auth::user()->role === 'admin' ? 'admin' : 'uptd';
    }

    public function index(Request $request)
    {
        $role = $this->getRolePrefix();
        $query = Cpcl::query();

        if ($request->filled('kecamatan')) {
            $query->where('lokasi', 'LIKE', '%' . $request->kecamatan . '%');
        }

        if ($request->filled('rencana_usaha')) {
            $query->where('rencana_usaha', 'LIKE', '%' . $request->rencana_usaha . '%');
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nama_kelompok', 'LIKE', "%$search%")
                  ->orWhere('nama_ketua', 'LIKE', "%$search%")
                  ->orWhere('nik_ketua', 'LIKE', "%$search%");
            });
        }

        $data = $query->latest()->paginate(10)->withQueryString();
        return view("$role.data-cpcl.index", compact('data'));
    }

    public function verified(Request $request)
    {
        $role = $this->getRolePrefix();
        $query = Cpcl::where('status', 'terverifikasi');

        if ($request->filled('kecamatan')) {
            $query->where('lokasi', 'LIKE', '%' . $request->kecamatan . '%');
        }

        if ($request->filled('rencana_usaha')) {
            $query->where('rencana_usaha', 'LIKE', '%' . $request->rencana_usaha . '%');
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nama_kelompok', 'LIKE', "%$search%")
                  ->orWhere('nama_ketua', 'LIKE', "%$search%")
                  ->orWhere('nik_ketua', 'LIKE', "%$search%");
            });
        }

        $data = $query->latest()->paginate(10)->withQueryString();
        return view("$role.data-cpcl.terverifikasi", compact('data'));
    }


    public function belum(Request $request)
    {
        $role = $this->getRolePrefix();
        $query = Cpcl::where('status', '!=', 'terverifikasi');

        if ($request->filled('kecamatan')) {
            $query->where('lokasi', 'LIKE', '%' . $request->kecamatan . '%');
        }

        if ($request->filled('rencana_usaha')) {
            $query->where('rencana_usaha', 'LIKE', '%' . $request->rencana_usaha . '%');
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nama_kelompok', 'LIKE', "%$search%")
                  ->orWhere('nama_ketua', 'LIKE', "%$search%")
                  ->orWhere('nik_ketua', 'LIKE', "%$search%");
            });
        }

        $data = $query->latest()->paginate(10)->withQueryString();
        return view("$role.data-cpcl.belum-verifikasi", compact('data'));
    }

    public function create()
    {
        $role = $this->getRolePrefix();
        return view("$role.data-cpcl.form");
    }

    /**
     * Method untuk menampilkan form Edit
     */
    public function edit($id)
    {
        $role = $this->getRolePrefix();
        $cpcl = Cpcl::findOrFail($id);
        
        // Menggunakan view form yang sama dengan Create
        return view("$role.data-cpcl.form", compact('cpcl'));
    }

    public function detail($id)
    {
        $role = $this->getRolePrefix();
        $cpcl = Cpcl::findOrFail($id);
        return view("$role.data-cpcl.detail", compact('cpcl'));
    }

    public function laporan()
    {
        $role = $this->getRolePrefix();
        $data = Cpcl::where('status', 'terverifikasi')->latest()->get();
        return view("$role.data-cpcl.laporan", compact('data'));
    }

    public function store(Request $request)
    {
        $role = $this->getRolePrefix();
        $this->validateCpcl($request, false); // false = create (file wajib)

        try {
            $this->cpclService->storeCpcl($request->all());
            return redirect()->route("$role.cpcl.index")->with('success', 'Data CPCL berhasil ditambahkan');
        } catch (\Throwable $e) {
            Log::error('CPCL STORE FAILED: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Gagal menyimpan data');
        }
    }

    /**
     * Method untuk memproses Update data
     */
    public function update(Request $request, $id)
    {
        $role = $this->getRolePrefix();
        $this->validateCpcl($request, true); // true = update (file opsional)

        try {
            $this->cpclService->updateCpcl($id, $request->all());
            return redirect()->route("$role.cpcl.index")->with('success', 'Data CPCL berhasil diperbarui');
        } catch (\Throwable $e) {
            Log::error('CPCL UPDATE FAILED: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Gagal memperbarui data');
        }
    }

    /**
     * Helper Validasi agar tidak duplikasi kode
     */
    private function validateCpcl(Request $request, $isUpdate = false)
    {
        $rules = [
            'nama_kelompok' => 'required|string|max:255',
            'nama_ketua'    => 'required|string|max:255',
            'nik_ketua'     => 'required|digits:16',
            'bidang'        => 'required|in:HARTIBUN,PANGAN',
            'rencana_usaha' => 'required|string',
            'lokasi'        => 'required|string',
            'luas_lahan'    => 'required|numeric|min:0',
            'lama_berdiri'  => 'required|integer|min:0',
            'hasil_panen'   => 'required|numeric|min:0',
            'status_lahan'  => 'required|in:milik,sewa,garapan',
            'file_proposal' => ($isUpdate ? 'nullable' : 'required') . '|mimes:pdf|max:2048',
            'file_ktp'      => ($isUpdate ? 'nullable' : 'required') . '|mimes:pdf,jpg,jpeg,png|max:2048',
            'file_sk'       => 'nullable|mimes:pdf|max:2048',
            'foto_lahan'    => 'nullable|image|mimes:jpg,jpeg,png|max:3072',
        ];

        return $request->validate($rules);
    }

    public function showVerification($id)
    {
        $role = $this->getRolePrefix();
        $cpcl = Cpcl::findOrFail($id);
        $kriteria = Kriteria::with('subKriteria')->get();
        return view("$role.data-cpcl.verifikasi", compact('cpcl', 'kriteria'));
    }

    public function verify(Request $request, $id)
{
    $role = $this->getRolePrefix();

    Log::info('Incoming CPCL Verification Request', [
        'cpcl_id' => $id,
        'data' => $request->all(),
        'timestamp' => now(),
    ]);

    $request->validate([
        'nilai' => 'required|array',
        'status' => 'required|in:terverifikasi,ditolak,baru',
        'catatan_verifikator' => 'nullable|string|max:500',
    ]);

    try {
        // 1. Proses simpan penilaian dan eksekusi FuzzySugenoService
        $this->cpclService->verifyCpcl($id, $request->all());

        Log::info('CPCL VERIFIED SUCCESS', [
            'cpcl_id' => $id,
            'status' => $request->status,
            'timestamp' => now(),
        ]);

        // 2. JIKA STATUS TERVERIFIKASI, ARAHKAN KE HALAMAN AUDIT
        // Pastikan nama route sesuai dengan yang ada di web.php (admin.perhitungan.index)
        if ($request->status == 'terverifikasi') {
            return redirect()
                ->route('admin.perhitungan.index', ['id' => $id]) 
                ->with('success', 'Verifikasi berhasil! Memulai simulasi audit perhitungan...');
        }

        // 3. Jika ditolak atau status lain, kembali ke daftar utama CPCL
        return redirect()
            ->route("$role.cpcl.index")
            ->with('success', 'Status CPCL berhasil diperbarui.');

    } catch (\Throwable $e) {
        Log::error('CPCL VERIFICATION FAILED', [
            'cpcl_id' => $id,
            'error_message' => $e->getMessage(),
        ]);

        return back()
            ->withInput()
            ->with('error', 'Gagal memverifikasi data: ' . $e->getMessage());
    }
}

    public function destroy($id)
    {
        $role = $this->getRolePrefix();
        try {
            $cpcl = Cpcl::findOrFail($id);
            $cpcl->delete();
            return redirect()->route("$role.cpcl.index")->with('success', 'Data CPCL berhasil dihapus');
        } catch (\Throwable $e) {
            Log::error('CPCL DELETE FAILED: ' . $e->getMessage());
            return back()->with('error', 'Gagal menghapus data');
        }
    }
}
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kriteria;
use App\Models\SubKriteria;
use Illuminate\Http\Request;
use Illuminate\Support\Str; 
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class KriteriaController extends Controller
{
    public function index()
    {
        $kriteria = Kriteria::with('subKriteria')->get();
        return view('admin.fuzzy-sugeno.kriteria.index', compact('kriteria'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_kriteria' => 'required|string|max:255',
            'jenis_kriteria' => 'required|in:kontinu,diskrit',
        ]);

        //buat kode_kriteria otomatis berdasarkan jumlah data + 1
        $count = Kriteria::count() + 1;
        $kode = 'C' . str_pad($count, 1, STR_PAD_LEFT);

        $data = $request->all();
        $data['kode_kriteria'] = $kode;
        $data['mapping_field'] = Str::slug($request->nama_kriteria, '_');


        Kriteria::create($data);

        return redirect()->back()->with('success', 'Kriteria berhasil ditambahkan.');
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'nama_kriteria' => 'required|string|max:255',
            'jenis_kriteria' => 'required|in:kontinu,diskrit',
        ]);

        $kriteria = Kriteria::findOrFail($id);
        
        $data = $request->all();
        $data['mapping_field'] = Str::slug($request->nama_kriteria, '_');
        
        $kriteria->update($data);

        return redirect()->back()->with('success', 'Kriteria berhasil diperbarui.');
    }

    public function destroy(string $id)
    {
        $kriteria = Kriteria::findOrFail($id);
        $kriteria->delete();

        return redirect()->back()->with('success', 'Kriteria berhasil dihapus.');
    }

    public function subKriteria(Request $request)
{
    $query = Kriteria::with('subKriteria');

    // Optional filter
    if ($request->filter) {
        $query->where('id', $request->filter);
    }

    $data = $query->paginate(5); // ✅ WAJIB paginate()

    return view('admin.fuzzy-sugeno.sub-kriteria.index', compact('data'));
}

public function storeSub(Request $request)
{
    $validated = $request->validate([
        'kriteria_id'          => 'required|exists:kriteria,id',
        'nama_sub_kriteria'    => 'required|array',
        'nama_sub_kriteria.*'  => 'required|string|max:255',
        'tipe_kurva'           => 'required|array',
        'tipe_kurva.*'         => 'required|in:bahu_kiri,trapesium,bahu_kanan,diskrit',
        // Validasi nullable karena tergantung tipe kurva
        'batas_bawah.*'        => 'nullable|numeric',
        'batas_tengah_1.*'     => 'nullable|numeric',
        'batas_tengah_2.*'     => 'nullable|numeric',
        'batas_atas.*'         => 'nullable|numeric',
        'nilai_diskrit.*'      => 'nullable|numeric|min:0|max:1',
    ]);

    DB::transaction(function () use ($request) {
        foreach ($request->nama_sub_kriteria as $index => $nama) {
            SubKriteria::create([
                'kriteria_id'       => $request->kriteria_id,
                'nama_sub_kriteria' => $nama,
                'tipe_kurva'        => $request->tipe_kurva[$index],
                'batas_bawah'       => $request->batas_bawah[$index] ?? null,
                'batas_tengah_1'    => $request->batas_tengah_1[$index] ?? null,
                'batas_tengah_2'    => $request->batas_tengah_2[$index] ?? null,
                'batas_atas'        => $request->batas_atas[$index] ?? null,
                'nilai_konsekuen'   => $request->nilai_diskrit[$index] ?? null, // Sesuaikan nama kolom migration
            ]);
        }
    });

    return redirect()->back()->with('success', 'Sub-kriteria berhasil disimpan.');
}

public function updateSub(Request $request, $id)
{
    // Gunakan logika yang sama dengan store, tapi hapus dulu data lama (Sync)
    $request->validate([
        'kriteria_id' => 'required|exists:kriteria,id',
        'nama_sub_kriteria' => 'required|array',
    ]);

    DB::transaction(function () use ($request) {
        // Hapus data lama berdasarkan kriteria_id
        SubKriteria::where('kriteria_id', $request->kriteria_id)->delete();

        foreach ($request->nama_sub_kriteria as $index => $nama) {
            SubKriteria::create([
                'kriteria_id'       => $request->kriteria_id,
                'nama_sub_kriteria' => $nama,
                'tipe_kurva'        => $request->tipe_kurva[$index],
                'batas_bawah'       => $request->batas_bawah[$index] ?? null,
                'batas_tengah_1'    => $request->batas_tengah_1[$index] ?? null,
                'batas_tengah_2'    => $request->batas_tengah_2[$index] ?? null,
                'batas_atas'        => $request->batas_atas[$index] ?? null,
                'nilai_konsekuen'   => $request->nilai_diskrit[$index] ?? null,
            ]);
        }
    });

    return redirect()->back()->with('success', 'Sub-kriteria berhasil diperbarui.');
}
    public function destroySub(string $id)
    {
        $subKriteria = SubKriteria::findOrFail($id);
        $subKriteria->delete();

        return redirect()->back()->with('success', 'Sub-kriteria berhasil dihapus.');
    }
}
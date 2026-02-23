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
   

    try {

        $validated = $request->validate([
            'kriteria_id'      => 'required|exists:kriteria,id',
            'nama_sub_kriteria'   => 'required|array',
            'nama_sub_kriteria.*'       => 'required|string|max:255',
            'batas_bawah'      => 'required|array',
            'batas_bawah.*'    => 'required|numeric|min:0',
            'batas_tengah'     => 'required|array',
            'batas_tengah.*'   => 'required|numeric|min:0',
            'batas_atas'       => 'required|array',
            'batas_atas.*'     => 'required|numeric|min:0',
        ]);



        $kriteriaId   = $validated['kriteria_id'];
        $namaSubs     = $validated['nama_sub_kriteria'];
        $batasBawah   = $validated['batas_bawah'];
        $batasTengah  = $validated['batas_tengah'];
        $batasAtas    = $validated['batas_atas'];

        DB::transaction(function () use ($kriteriaId, $namaSubs, $batasBawah, $batasTengah, $batasAtas) {

            foreach ($namaSubs as $index => $nama) {

                

                SubKriteria::create([
                    'kriteria_id'       => $kriteriaId,
                    'nama_sub_kriteria' => $nama,
                    'batas_bawah'       => $batasBawah[$index],
                    'batas_tengah'      => $batasTengah[$index],
                    'batas_atas'        => $batasAtas[$index],
                ]);
            }
        });

      
        return redirect()->back()->with(
            'success',
            count($namaSubs) . ' Sub-kriteria berhasil ditambahkan.'
        );

    } catch (\Exception $e) {

        

        return redirect()->back()->with('error', 'Terjadi kesalahan. Cek log.');
    }
}

public function updateSub(Request $request, string $id)
{
    $request->validate([
        'kriteria_id'      => 'required|exists:kriteria,id',
        'nama_sub_kriteria'         => 'required|array',
        'nama_sub_kriteria.*'       => 'required|string|max:255',
        'batas_bawah'      => 'required|array',
        'batas_bawah.*'    => 'required|numeric|min:0',
        'batas_tengah'     => 'required|array',
        'batas_tengah.*'   => 'required|numeric|min:0',
        'batas_atas'       => 'required|array',
        'batas_atas.*'     => 'required|numeric|min:0',
    ]);

    $kriteriaId   = $request->kriteria_id;
    $namaSubs     = $request->nama_sub_kriteria;
    $batasBawah   = $request->batas_bawah;
    $batasTengah  = $request->batas_tengah;
    $batasAtas    = $request->batas_atas;

    DB::transaction(function () use ($kriteriaId, $namaSubs, $batasBawah, $batasTengah, $batasAtas) {

        // Hapus lama
        SubKriteria::where('kriteria_id', $kriteriaId)->delete();

        foreach ($namaSubs as $index => $nama) {
            SubKriteria::create([
                'kriteria_id'       => $kriteriaId,
                'nama_sub_kriteria' => $nama,
                'batas_bawah'       => $batasBawah[$index],
                'batas_tengah'      => $batasTengah[$index],
                'batas_atas'        => $batasAtas[$index],
            ]);
        }
    });

    return redirect()->back()->with(
        'success',
        'Grup Sub-kriteria berhasil diperbarui.'
    );
}

    public function destroySub(string $id)
    {
        $subKriteria = SubKriteria::findOrFail($id);
        $subKriteria->delete();

        return redirect()->back()->with('success', 'Sub-kriteria berhasil dihapus.');
    }
}
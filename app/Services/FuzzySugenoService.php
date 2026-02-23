<?php

namespace App\Services;

use App\Models\Cpcl;
use App\Models\Kriteria;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FuzzySugenoService
{
    public static function hitungDanSimpan($cpcl_id)
    {
        Log::info("========================================================");
        Log::info("START: PROSES PERHITUNGAN FUZZY SUGENO (ID: $cpcl_id)");
        Log::info("========================================================");

        $cpcl = Cpcl::find($cpcl_id);
        if (!$cpcl) return false;

        $kriteriaList = Kriteria::with('subKriteria')->get();
        $jumlahKriteria = $kriteriaList->count();
        $nilaiC = []; 

        // =======================================================
        // A. TAHAP FUZZIFIKASI & MATRIKS NILAI KEANGGOTAAN
        // =======================================================
        Log::info("A. TAHAP FUZZIFIKASI & MATRIKS NILAI KEANGGOTAAN");
        
        foreach ($kriteriaList as $kriteria) {
            $penilaian = DB::table('cpcl_penilaian')
                ->where('cpcl_id', $cpcl_id)
                ->where('kriteria_id', $kriteria->id)
                ->first();

            $nilaiInput = $penilaian ? $penilaian->nilai : 0;
            $skorKriteria = 0; 

            Log::info("Kriteria {$kriteria->kode_kriteria} ({$kriteria->nama_kriteria}) | Input: $nilaiInput");

            if ($kriteria->jenis_kriteria == 'diskrit') {
                foreach ($kriteria->subKriteria as $sub) {
                    if (strtolower(trim($nilaiInput)) == strtolower(trim($sub->nama_sub_kriteria))) {
                        $skorKriteria = (float) $sub->nilai_diskrit; 
                        Log::info("   -> Matriks μ({$sub->nama_sub_kriteria}): $skorKriteria");
                        break;
                    }
                }
            } 
            else {
                $x = (float) $nilaiInput;
                $total_mu_bobot = 0;
                $total_mu = 0;

                foreach ($kriteria->subKriteria as $sub) {
                    $mu = 0;
                    $a = (float) $sub->batas_bawah;
                    $b = (float) $sub->batas_tengah_1;
                    $c = (float) $sub->batas_tengah_2;
                    $d = (float) $sub->batas_atas;

                    if ($sub->tipe_kurva == 'bahu_kiri') {
                        if ($x <= $c) $mu = 1;
                        elseif ($x > $c && $x < $d) $mu = ($d - $x) / ($d - $c);
                        $bobot = 0.2;
                    } 
                    elseif ($sub->tipe_kurva == 'trapesium') {
                        if ($x > $a && $x < $b) $mu = ($x - $a) / ($b - $a);
                        elseif ($x >= $b && $x <= $c) $mu = 1;
                        elseif ($x > $c && $x < $d) $mu = ($d - $x) / ($d - $c);
                        $bobot = 0.6;
                    } 
                    elseif ($sub->tipe_kurva == 'bahu_kanan') {
                        if ($x > $a && $x < $b) $mu = ($x - $a) / ($b - $a);
                        elseif ($x >= $b) $mu = 1;
                        $bobot = 1.0;
                    }

                    if ($mu > 0) {
                        Log::info("   -> Matriks μ({$sub->nama_sub_kriteria}): " . round($mu, 4) . " [Bobot: $bobot]");
                    }
                    
                    $total_mu_bobot += ($mu * $bobot);
                    $total_mu += $mu;
                }

                $skorKriteria = $total_mu > 0 ? ($total_mu_bobot / $total_mu) : 0;
            }

            Log::info("   -> Skor Representasi (C) {$kriteria->kode_kriteria}: " . round($skorKriteria, 4));
            $nilaiC[] = $skorKriteria; 
        }

        // =======================================================
        // B. PEMBENTUKAN ATURAN & PROSES INFERENSI
        // =======================================================
        Log::info("B. PEMBENTUKAN ATURAN & INFERENSI (SUGENO ORDE NOL)");
        Log::info("   Rule Aktual: IF C1 AND C2 AND C3 AND C4 AND C5 THEN Ki");
        
        // 1. Operator Inferensi (Firing Strength)
        $alpha = min($nilaiC);
        Log::info("   1. Operator Inferensi (AND -> MIN)");
        Log::info("      Firing Strength (α) = min(" . implode(", ", array_map(fn($n) => round($n, 2), $nilaiC)) . ")");
        Log::info("      Hasil α-predikat = " . round($alpha, 4));

        // 2. Perhitungan Firing Strength / Konsekuen
        $totalSkorC = array_sum($nilaiC);
        $ki = $totalSkorC / $jumlahKriteria;
        Log::info("   2. Penentuan Nilai Konsekuen (Ki)");
        Log::info("      Ki = (Sum of C) / $jumlahKriteria = " . round($ki, 4));

        // =======================================================
        // C. TAHAP DEFUZZIFIKASI
        // =======================================================
        Log::info("C. TAHAP DEFUZZIFIKASI (WEIGHTED AVERAGE)");
        
        $z = $ki; // Berdasarkan prinsip 1 Alternatif = 1 Rule
        $skorAkhir = round($z * 100, 2);
        $statusKelayakan = $skorAkhir >= 70 ? 'Layak' : 'Tidak Layak';

        Log::info("   Hasil Defuzzifikasi (Z) = $z");
        Log::info("   Skor Akhir (Z * 100) = $skorAkhir %");
        Log::info("   Kesimpulan = $statusKelayakan");

        // Simpan ke Database
        DB::table('hasil_fuzzy')->updateOrInsert(
            ['cpcl_id' => $cpcl->id],
            [
                'nilai_alpha' => $alpha, 
                'nilai_z' => $z,
                'skor_akhir' => $skorAkhir,
                'status_kelayakan' => $statusKelayakan,
                'created_at' => now(),
                'updated_at' => now()
            ]
        );

        Log::info("========================================================");
        Log::info("END: PERHITUNGAN SELESAI");
        Log::info("========================================================");

        return true;
    }
}
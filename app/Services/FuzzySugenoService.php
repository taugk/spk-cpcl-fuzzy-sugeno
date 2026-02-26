<?php

namespace App\Services;

use App\Models\Cpcl;
use App\Models\Kriteria;
use App\Models\HasilFuzzy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FuzzySugenoService
{
    /**
     * Menghitung skor kelayakan dan menyimpan hasil ke database.
     */
    public static function hitungDanSimpan($cpcl_id)
    {
        $hasil = self::hitung($cpcl_id);
        
        HasilFuzzy::updateOrCreate(
            ['cpcl_id' => $cpcl_id],
            [
                'nilai_alpha'      => $hasil['alpha'],
                'nilai_z'          => $hasil['ki'],
                'skor_akhir'       => $hasil['skor_akhir'],
                'status_kelayakan' => $hasil['status'],
                'skala_prioritas'  => $hasil['skala_prioritas'],
                'interpretasi'     => $hasil['interpretasi']
            ]
        );
        
        return $hasil;
    }

    /**
     * Inti perhitungan Fuzzy Sugeno Orde Nol sesuai metodologi.
     */
    public static function hitung($cpcl_id)
    {
        $cpcl = Cpcl::findOrFail($cpcl_id);
        
        $kriteriaList = Kriteria::with(['subKriteria'])->orderBy('id', 'asc')->get();
        $detailFuzzifikasi = [];
        $nilaiC = [];

        foreach ($kriteriaList as $kriteria) {
            $penilaian = DB::table('cpcl_penilaian')
                ->where('cpcl_id', $cpcl_id)
                ->where('kriteria_id', $kriteria->id)
                ->first();

            $rawInput = $penilaian ? $penilaian->nilai : '';
            $x = self::mapInputToValue($rawInput, $kriteria->kode_kriteria); 

            $total_mu_bobot = 0;
            $total_mu = 0;
            $subDetail = [];

            foreach ($kriteria->subKriteria as $sub) {
                $mu = 0;
                $db_k = (float) ($sub->nilai_konsekuen ?? 0);
                
                // Jika k kosong, gunakan normalisasi x / batas_atas agar skala tetap 0-1
                $k = ($db_k > 0) ? $db_k : (($x > 0) ? min($x / ((float)$sub->batas_atas ?: 1), 1) : 0);

                $a = (float) $sub->batas_bawah;
                $b = (float) $sub->batas_tengah_1;
                $c = (float) $sub->batas_tengah_2;
                $d = (float) $sub->batas_atas;

                // Tahap Fuzzifikasi
                if ($sub->tipe_kurva == 'diskrit') {
                    if (strtolower(trim($rawInput)) == strtolower(trim($sub->nama_sub_kriteria))) $mu = 1.0;
                } else {
                    if ($sub->tipe_kurva == 'bahu_kiri') {
                        if ($x <= $c) $mu = 1.0;
                        elseif ($x > $c && $x < $d) $mu = ($d - $x) / ($d - $c);
                    } elseif ($sub->tipe_kurva == 'trapesium') {
                        if ($x > $a && $x < $b) $mu = ($x - $a) / ($b - $a);
                        elseif ($x >= $b && $x <= $c) $mu = 1.0;
                        elseif ($x > $c && $x < $d) $mu = ($d - $x) / ($d - $c);
                    } elseif ($sub->tipe_kurva == 'bahu_kanan') {
                        if ($x >= $b) $mu = 1.0;
                        elseif ($x > $a && $x < $b) $mu = ($x - $a) / ($b - $a);
                    }
                }

                $subDetail[] = [
                    'nama' => $sub->nama_sub_kriteria, 'mu' => $mu, 'k' => $k, 
                    'tipe' => $sub->tipe_kurva, 'a' => $a, 'b' => $b, 'c' => $c, 'd' => $d
                ];
                $total_mu_bobot += ($mu * $k);
                $total_mu += $mu;
            }

            // Direct Evaluation (C) per kriteria
            $C = $total_mu > 0 ? ($total_mu_bobot / $total_mu) : 0;
            $detailFuzzifikasi[] = [
                'kode' => $kriteria->kode_kriteria, 'nama' => $kriteria->nama_kriteria, 
                'input' => $rawInput, 'x' => $x, 'sub' => $subDetail, 'C' => $C
            ];
            $nilaiC[] = $C;
        }

        // TAHAP INFERENSI: Alpha (Firing Strength) dan Ki (Konsekuen Kolektif)
        $alpha = count($nilaiC) > 0 ? min($nilaiC) : 0;
        $ki = count($nilaiC) > 0 ? (array_sum($nilaiC) / count($nilaiC)) : 0;

        // TAHAP DEFUZZIFIKASI & INTERPRETASI SKALA PRIORITAS
        $z = $ki;
        $skorPersen = round($z * 100, 2);
        
        $skala = self::getSkalaPrioritas($z);

        return [
            'cpcl' => $cpcl,
            'fuzzifikasi' => $detailFuzzifikasi,
            'nilaiC' => $nilaiC,
            'alpha' => $alpha,
            'ki' => $z,
            'skor_akhir' => $skorPersen,
            'status' => $skala['status'],
            'skala_prioritas' => $skala['prioritas'],
            'interpretasi' => $skala['interpretasi']
        ];
    }

    /**
     * Menentukan Skala Prioritas berdasarkan Nilai Akhir (Z)
     */
    private static function getSkalaPrioritas($z)
    {
        if ($z > 0.80) {
            return ['prioritas' => 'Prioritas I', 'interpretasi' => 'Sangat Diprioritaskan', 'status' => 'Layak'];
        } elseif ($z > 0.60) {
            return ['prioritas' => 'Prioritas II', 'interpretasi' => 'Diprioritaskan', 'status' => 'Layak'];
        } elseif ($z > 0.40) {
            return ['prioritas' => 'Prioritas III', 'interpretasi' => 'Dipertimbangkan', 'status' => 'Tidak Layak'];
        } else {
            return ['prioritas' => 'Prioritas IV', 'interpretasi' => 'Tidak Diprioritaskan', 'status' => 'Tidak Layak'];
        }
    }

    private static function mapInputToValue($input, $kodeKriteria) {
        if (is_numeric($input)) return (float) $input;
        $map = [
            'C1' => ['Sempit' => 0.2, 'Sedang' => 0.5, 'Luas' => 1.0],
            'C3' => ['Baru / Pemula' => 1.0, 'Lama' => 4.0, 'Sangat Lama' => 10.0],
            'C4' => ['Rendah' => 4.0, 'Sedang' => 6.0, 'Tinggi' => 8.0],
        ];
        return $map[$kodeKriteria][$input] ?? 0.0;
    }
}
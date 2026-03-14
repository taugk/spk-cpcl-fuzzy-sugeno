<?php

namespace App\Services;

use App\Models\Cpcl;
use App\Models\Kriteria;
use App\Models\HasilFuzzy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

/**
 * Fuzzy Sugeno Orde Nol - Final Production Version
 * Fitur: Hybrid Data Source, Validator Sinkronisasi, Mass Ranking, & Robust Numeric Parsing
 */
class FuzzySugenoService
{
    /**
     * Menghitung dan menyimpan hasil ke database untuk 1 ID CPCL
     */
    public static function hitungDanSimpan(int $cpcl_id): array
    {
        Log::info("=== START: Hitung & Simpan Fuzzy Sugeno | CPCL ID: {$cpcl_id} ===");
        $hasil = self::hitung($cpcl_id);

        HasilFuzzy::updateOrCreate(
            ['cpcl_id' => $cpcl_id],
            [
                'nilai_alpha'      => $hasil['alpha'],
                'nilai_z'          => $hasil['z'],
                'skor_akhir'       => $hasil['skor_akhir'],
                'status_kelayakan' => $hasil['status_kelayakan'],
                'skala_prioritas'  => $hasil['skala_prioritas'],
                'interpretasi'     => $hasil['interpretasi'],
            ]
        );

        return $hasil;
    }

    /**
     * Menghitung semua CPCL terverifikasi dan memberikan Ranking per Periode
     */
    public static function hitungSemuaDanRanking(?string $periode = null): Collection
    {
        $query = Cpcl::where('status', 'terverifikasi');
        if ($periode) {
            $query->whereYear('created_at', $periode);
        }
        $cpclList = $query->get();

        $hasilList = [];
        foreach ($cpclList as $cpcl) {
            try {
                $hasil = self::hitung($cpcl->id);
                $hasilList[] = array_merge(['cpcl_id' => $cpcl->id], $hasil);
            } catch (\Exception $e) {
                Log::error("Gagal hitung CPCL ID {$cpcl->id}: " . $e->getMessage());
            }
        }

        $ranked = collect($hasilList)->sortByDesc('skor_akhir')->values();

        DB::transaction(function () use ($ranked) {
            foreach ($ranked as $rank => $item) {
                HasilFuzzy::updateOrCreate(
                    ['cpcl_id' => $item['cpcl_id']],
                    [
                        'nilai_alpha'      => $item['alpha'],
                        'nilai_z'          => $item['z'],
                        'skor_akhir'       => $item['skor_akhir'],
                        'status_kelayakan' => $item['status_kelayakan'],
                        'skala_prioritas'  => $item['skala_prioritas'],
                        'interpretasi'     => $item['interpretasi'],
                        'ranking'          => $rank + 1,
                    ]
                );
            }
        });

        return $ranked;
    }

    /**
     * CORE LOGIC: Perhitungan Fuzzy Sugeno Orde Nol
     */
    public static function hitung(int $cpcl_id): array
    {
        $cpcl         = Cpcl::findOrFail($cpcl_id);
        $kriteriaList = Kriteria::with('subKriteria')->orderBy('id')->get();
        $fuzzifikasi  = [];

        // ---------- STEP 1: FUZZIFIKASI ----------
        foreach ($kriteriaList as $kriteria) {
            $field = $kriteria->mapping_field;
            
            if ($kriteria->jenis_kriteria === 'kontinu') {
                // 1. KONTINU: Ambil nilai riil murni dari tabel CPCL (Lahan, Pengalaman, Panen)
                $inputVal = $cpcl->{$field} ?? '0';
            } else {
                // 2. DISKRIT: Ambil hasil verifikasi kategori dari tabel cpcl_penilaian
                $inputVal = DB::table('cpcl_penilaian')
                    ->where('cpcl_id', $cpcl_id)
                    ->where('kriteria_id', $kriteria->id)
                    ->value('nilai');

                if (is_null($inputVal) || $inputVal === '') {
                    $inputVal = $cpcl->{$field} ?? '-';
                }
            }

            $inputString = (string) $inputVal;
            $himpunanAktif = [];

            foreach ($kriteria->subKriteria as $sub) {
                $mu = self::hitungMu($sub, $inputString);
                
                $z_konsekuen = (float) ($sub->nilai_konsekuen ?? 0);
                if ($z_konsekuen <= 0) {
                    $z_konsekuen = self::getFallbackK($sub->nama_sub_kriteria);
                }

                if ($mu > 0) {
                    $himpunanAktif[] = [
                        'nama'   => $sub->nama_sub_kriteria, 
                        'mu'     => round($mu, 4), 
                        'z'      => $z_konsekuen, 
                        'k'      => $z_konsekuen, 
                        'params' => [
                            'a' => $sub->batas_bawah, 'b' => $sub->batas_tengah_1,
                            'c' => $sub->batas_tengah_2, 'd' => $sub->batas_atas,
                            'tipe' => $sub->tipe_kurva
                        ]
                    ];
                }
            }

            $fuzzifikasi[] = [
                'kode'     => $kriteria->kode_kriteria, 
                'nama'     => $kriteria->nama_kriteria,
                'input'    => $inputString,
                'himpunan' => $himpunanAktif,
                'sub'      => $himpunanAktif 
            ];
        }

        // ---------- STEP 2: EVALUASI RULE ----------
        $inputRules = [];
        foreach ($fuzzifikasi as $f) {
            if (!empty($f['himpunan'])) {
                $inputRules[$f['kode']] = $f['himpunan'];
            }
        }

        $rules = []; $sumAlphaZ = 0.0; $sumAlpha = 0.0;

        if (!empty($inputRules)) {
            $kombinasi = self::kartesian($inputRules);
            foreach ($kombinasi as $idx => $combo) {
                $muValues = array_column($combo, 'mu');
                $alpha    = min($muValues);

                $zValues  = array_column($combo, 'z');
                $zRule    = count($zValues) > 0 ? array_sum($zValues) / count($zValues) : 0.0;

                $sumAlphaZ += ($alpha * $zRule);
                $sumAlpha  += $alpha;

                $anteceden = [];
                foreach ($combo as $kode => $h) {
                    $anteceden[] = ['kriteria' => $kode, 'himpunan' => $h['nama']];
                }

                $rules[] = [
                    'rule_id'   => 'R' . str_pad($idx + 1, 3, '0', STR_PAD_LEFT),
                    'anteceden' => $anteceden,
                    'alpha'     => round($alpha, 4), 
                    'z_rule'    => round($zRule, 4),
                    'alpha_x_z' => round($alpha * $zRule, 4)
                ];
            }
        }

        // ---------- STEP 3: DEFUZZIFIKASI ----------
        $z_final = $sumAlpha > 0 ? ($sumAlphaZ / $sumAlpha) : 0.0;
        $skala   = self::getSkalaPrioritas($z_final);

        return [
            'cpcl'             => $cpcl,
            'fuzzifikasi'      => $fuzzifikasi,
            'rules'            => $rules,
            'sum_alpha_z'      => round($sumAlphaZ, 4),
            'sum_alpha'        => round($sumAlpha, 4),
            'alpha'            => !empty($rules) ? max(array_column($rules, 'alpha')) : 0,
            'z'                => round($z_final, 4),
            'skor_akhir'       => round($z_final * 100, 2),
            'status_kelayakan' => $skala['status'],
            'skala_prioritas'  => $skala['prioritas'],
            'interpretasi'     => $skala['interpretasi'],
        ];
    }

    /**
     * VALIDATOR: Sinkronisasi aturan Hybrid
     */
    public static function cekSinkronisasiData(int $cpcl_id): array
    {
        $cpcl = Cpcl::findOrFail($cpcl_id);
        $kriteriaList = Kriteria::with('subKriteria')->get();
        $errors = [];

        foreach ($kriteriaList as $kriteria) {
            $field = $kriteria->mapping_field;

            if ($kriteria->jenis_kriteria === 'kontinu') {
                $nilai = $cpcl->{$field} ?? null;
                // Bersihkan teks (handling jika 5.0 atau "5 Tahun")
                $clean = preg_replace('/[^0-9.]/', '', (string)$nilai);
                
                if (is_null($nilai) || $nilai === '') {
                    $errors[] = "Cek {$kriteria->kode_kriteria}: Data profil kosong.";
                } elseif (!is_numeric($clean)) {
                    $errors[] = "Cek {$kriteria->kode_kriteria}: Nilai '{$nilai}' bukan angka valid.";
                }
            } else {
                $nilai = DB::table('cpcl_penilaian')->where('cpcl_id', $cpcl_id)->where('kriteria_id', $kriteria->id)->value('nilai');
                if (is_null($nilai) || $nilai === '') {
                    $errors[] = "Cek {$kriteria->kode_kriteria}: Belum diverifikasi admin.";
                } else {
                    $isMatch = false;
                    foreach ($kriteria->subKriteria as $sub) {
                        if (strtolower(trim((string)$nilai)) === strtolower(trim($sub->nama_sub_kriteria))) {
                            $isMatch = true; break;
                        }
                    }
                    if (!$isMatch) {
                        $errors[] = "Cek {$kriteria->kode_kriteria}: Kategori '{$nilai}' tidak valid.";
                    }
                }
            }
        }
        return ['is_valid' => empty($errors), 'messages' => $errors];
    }

    /**
     * HANDLING FLOAT & STRING: Membersihkan input agar sinkron dengan kurva fuzzy
     */
    private static function hitungMu(object $sub, string $input): float
    {
        if ($sub->tipe_kurva === 'diskrit') {
            return (strtolower(trim($input)) === strtolower(trim($sub->nama_sub_kriteria))) ? 1.0 : 0.0;
        }

        // ✅ REVISI HANDLING: Menghapus semua karakter kecuali angka dan titik desimal
        // Ini memastikan nilai seperti "5.0", "5", atau "5 Tahun" semuanya menjadi float(5.0)
        $cleanInput = preg_replace('/[^0-9.]/', '', $input);
        
        if (!is_numeric($cleanInput)) return 0.0;
        
        $x = (float) $cleanInput;
        $a = (float) $sub->batas_bawah; 
        $b = (float) $sub->batas_tengah_1;
        $c = (float) $sub->batas_tengah_2; 
        $d = (float) $sub->batas_atas;

        return match ($sub->tipe_kurva) {
            'bahu_kiri'  => ($x <= $c) ? 1.0 : (($x >= $d) ? 0.0 : ($d - $x) / ($d - $c)),
            'bahu_kanan' => ($x <= $a) ? 0.0 : (($x >= $b) ? 1.0 : ($x - $a) / ($b - $a)),
            'trapesium'  => ($x <= $a || $x >= $d) ? 0.0 : 
                            (($x >= $b && $x <= $c) ? 1.0 : 
                            (($x < $b) ? ($x - $a) / ($b - $a) : ($d - $x) / ($d - $c))),
            default      => 0.0,
        };
    }

    private static function kartesian(array $arrays): array
    {
        $result = [[]];
        foreach ($arrays as $kode => $pool) {
            $append = [];
            foreach ($result as $existing) {
                foreach ($pool as $item) { $append[] = $existing + [$kode => $item]; }
            }
            $result = $append;
        }
        return $result;
    }

    private static function getFallbackK(string $namaSub): float
    {
        $n = strtolower($namaSub);
        return match (true) {
            str_contains($n, 'sangat') || str_contains($n, 'luas') || str_contains($n, 'tinggi') || str_contains($n, 'baik') || str_contains($n, 'milik') => 1.0,
            str_contains($n, 'sedang') || str_contains($n, 'cukup') || str_contains($n, 'sewa') => 0.7,
            default => 0.4,
        };
    }

    private static function getSkalaPrioritas(float $z): array
    {
        return match (true) {
            $z >= 0.80 => ['prioritas' => 'Prioritas I', 'interpretasi' => 'Sangat Diprioritaskan', 'status' => 'Layak'],
            $z >= 0.60 => ['prioritas' => 'Prioritas II', 'interpretasi' => 'Diprioritaskan', 'status' => 'Layak'],
            $z >= 0.40 => ['prioritas' => 'Prioritas III', 'interpretasi' => 'Dipertimbangkan', 'status' => 'Tidak Layak'],
            default    => ['prioritas' => 'Prioritas IV', 'interpretasi' => 'Tidak Diprioritaskan', 'status' => 'Tidak Layak'],
        };
    }
}
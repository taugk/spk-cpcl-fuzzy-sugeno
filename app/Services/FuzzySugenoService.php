<?php

namespace App\Services;

use App\Models\Cpcl;
use App\Models\Kriteria;
use App\Models\HasilFuzzy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

/**
 * Fuzzy Sugeno Orde Nol dengan Direct Evaluation
 * 
 * Perbaikan dari versi sebelumnya:
 * 1. Implementasi Sugeno Orde Nol yang benar (z = Σ(αᵢ × zᵢ) / Σ(αᵢ))
 * 2. Direct Evaluation: evaluasi semua kombinasi rule langsung
 * 3. Pendekatan Bahu (Shoulder): semakin tinggi nilai Z semakin layak
 * 4. Fix defuzzifikasi: menggunakan weighted average dengan konsekuen per rule
 * 5. Fallback yang lebih robust untuk nilai konsekuen dan input
 */
class FuzzySugenoService
{
    // =========================================================================
    // 1. PUBLIC: Hitung + Simpan per ID
    // =========================================================================
    public static function hitungDanSimpan(int $cpcl_id): array
    {
        Log::info("=== START: Hitung dan Simpan Fuzzy Sugeno | CPCL ID: {$cpcl_id} ===");
        
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

    // =========================================================================
    // 2. PUBLIC: Hitung SEMUA CPCL untuk Ranking
    // =========================================================================
    public static function hitungSemuaDanRanking(?string $periode = null): Collection
    {
        $query = Cpcl::where('status', 'terverifikasi');
        if ($periode) {
            $query->whereYear('created_at', $periode);
        }
        $cpclList = $query->get();

        Log::info("=== START: Batch Hitung Semua CPCL ===", ['total' => $cpclList->count()]);

        $hasilList = [];
        foreach ($cpclList as $cpcl) {
            try {
                $hasil = self::hitung($cpcl->id);
                // Gabungkan data perhitungan dengan ID CPCL
                $hasilList[] = array_merge(['cpcl_id' => $cpcl->id], $hasil);
            } catch (\Exception $e) {
                Log::error("Gagal hitung CPCL ID {$cpcl->id}: " . $e->getMessage());
            }
        }

        // Urutkan berdasarkan skor_akhir tertinggi (Descending)
        // Semakin tinggi semakin layak (pendekatan bahu)
        $ranked = collect($hasilList)->sortByDesc('skor_akhir')->values();

        // Simpan ke DB dalam transaksi agar aman
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

    // =========================================================================
    // 3. CORE: Fuzzy Sugeno Orde Nol Logic
    // =========================================================================
    /**
     * Hitung Fuzzy Sugeno Orde Nol untuk 1 CPCL
     * 
     * Alur:
     * 1. Fuzzifikasi: Konversi input crisp → derajat keanggotaan per himpunan fuzzy
     * 2. Rule Evaluation: Evaluasi semua rule dengan MIN (t-norm Mamdani)
     * 3. Defuzzifikasi Sugeno: z_out = Σ(αᵢ × zᵢ) / Σ(αᵢ)
     * 4. Scaling: Konversi z (0-1) ke skor (0-100)
     * 5. Interpretasi: Status kelayakan berdasarkan pendekatan bahu
     */
    public static function hitung(int $cpcl_id): array
    {
        $cpcl         = Cpcl::findOrFail($cpcl_id);
        $kriteriaList = Kriteria::with('subKriteria')->orderBy('id')->get();
        $fuzzifikasi  = [];

        // ========== STEP 1: FUZZIFIKASI ==========
        // Konversi input crisp (nilai terukur) menjadi derajat keanggotaan fuzzy
        foreach ($kriteriaList as $kriteria) {
            $field = $kriteria->mapping_field;
            
            // ✅ FIX v2.0.3: Handle kriteria diskrit & kontinu dengan benar
            if ($kriteria->jenis_kriteria === 'kontinu') {
                // Kriteria kontinu: ambil langsung dari field di tabel cpcl
                $rawInput = (string) ($cpcl->{$field} ?? '');
            } else {
                // Kriteria diskrit: ambil dari field mapping di tabel cpcl
                $rawInput = (string) ($cpcl->{$field} ?? '');
                
                // ✅ Fallback: Jika field kosong, coba dari cpcl_penilaian
                if (empty($rawInput)) {
                    $rawInput = trim(DB::table('cpcl_penilaian')
                        ->where('cpcl_id', $cpcl_id)
                        ->where('kriteria_id', $kriteria->id)
                        ->value('nilai') ?? '');
                }
            }

            $inputVal = empty($rawInput) ? '0' : $rawInput;
            $himpunan = [];

            // Hitung derajat keanggotaan untuk setiap himpunan fuzzy
            foreach ($kriteria->subKriteria as $sub) {
                $mu = self::hitungMu($sub, $inputVal);
                
                // Ambil nilai konsekuen (Sugeno Orde Nol: z = konstanta)
                $k = (float) ($sub->nilai_konsekuen ?? 0);
                
                // Fallback: jika 0 di DB, gunakan nilai default berbasis nama
                if ($k == 0) {
                    $k = self::getFallbackK($sub->nama_sub_kriteria);
                }

                if ($mu > 0) { // Hanya masukkan himpunan yang aktif (μ > 0)
                    $himpunan[] = [
                        'nama' => $sub->nama_sub_kriteria, 
                        'mu'   => $mu, 
                        'k'    => $k
                    ];
                }
            }

            $fuzzifikasi[] = [
                'kode'              => $kriteria->kode_kriteria, 
                'nama'              => $kriteria->nama_kriteria,
                'input'             => $inputVal,
                'jenis_kriteria'    => $kriteria->jenis_kriteria,
                'mapping_field'     => $field,
                'himpunan'          => $himpunan,
                'sub'               => $himpunan // Alias untuk kompatibilitas dengan view
            ];
        }

        // ========== STEP 2: RULE EVALUATION (Direct Evaluation) ==========
        // Ekstrak himpunan aktif per kriteria
        $himpunanAktif = [];
        foreach ($fuzzifikasi as $kFuzz) {
            if (!empty($kFuzz['himpunan'])) {
                $himpunanAktif[$kFuzz['kode']] = $kFuzz['himpunan'];
            }
        }

        $rules = [];
        $sumAlphaZ = 0.0;  // Σ(αᵢ × zᵢ)
        $sumAlpha  = 0.0;  // Σ(αᵢ)

        // Direct Evaluation: Ciptakan semua kombinasi rule (Cartesian Product)
        if (!empty($himpunanAktif)) {
            $kombinasi = self::kartesian($himpunanAktif);
            
            foreach ($kombinasi as $idx => $combo) {
                // α = MIN(μ₁, μ₂, ..., μₙ) - t-norm Mamdani
                $muValues = array_column($combo, 'mu');
                $alpha    = min($muValues);

                // z_rule = AVERAGE(k₁, k₂, ..., kₙ) - Sugeno Orde Nol
                $kValues  = array_column($combo, 'k');
                $zRule    = count($kValues) > 0 ? array_sum($kValues) / count($kValues) : 0.0;

                // Akumulasi untuk defuzzifikasi
                $sumAlphaZ += $alpha * $zRule;
                $sumAlpha  += $alpha;

                // Susun anteceden untuk keperluan log
                $anteceden = [];
                foreach ($combo as $kode => $h) {
                    $anteceden[] = ['kriteria' => $kode, 'himpunan' => $h['nama']];
                }

                $rules[] = [
                    'rule_id'   => 'R' . str_pad($idx + 1, 3, '0', STR_PAD_LEFT),
                    'anteceden' => $anteceden,
                    'alpha'     => round($alpha, 6), 
                    'z_rule'    => round($zRule, 6),
                    'alpha_x_z' => round($alpha * $zRule, 6)
                ];
            }
        }

        // ========== STEP 3: DEFUZZIFIKASI SUGENO ORDE Nmethodology ==========
        // Rumus Sugeno Orde Nol: z_out = Σ(αᵢ × zᵢ) / Σ(αᵢ)
        $z = $sumAlpha > 0 ? ($sumAlphaZ / $sumAlpha) : 0.0;
        $z = round($z, 6);

        // ========== STEP 4: SCALING & INTERPRETASI ==========
        // Konversi z (0-1) ke skor (0-100) dengan pendekatan bahu
        // Semakin tinggi Z semakin layak
        $skala = self::getSkalaPrioritas($z);

        // ========== STEP 5: PENGEMBALIAN HASIL ==========
        return [
            // Data untuk View (detail.blade.php)
            'cpcl'             => $cpcl,
            'fuzzifikasi'      => $fuzzifikasi,
            'rules'            => $rules,
            'sum_alpha_z'      => round($sumAlphaZ, 6),
            'sum_alpha'        => round($sumAlpha, 6),
            
            // Data untuk Database
            'alpha'            => !empty($rules) ? max(array_column($rules, 'alpha')) : 0.0,
            'z'                => $z,
            'skor_akhir'       => round($z * 100, 2),
            'status_kelayakan' => $skala['status'],
            'skala_prioritas'  => $skala['prioritas'],
            'interpretasi'     => $skala['interpretasi'],
        ];
    }

    // =========================================================================
    // 4. HELPERS
    // =========================================================================
    
    /**
     * Memberikan nilai konsekuen default (Sugeno Orde Nol)
     * Jika nilai di database = 0, gunakan nilai berdasarkan semantik nama
     * 
     * Skala default:
     * - Sangat/Tinggi/Luas: 1.0 (paling layak)
     * - Sedang/Cukup/Lengkap: 0.7 (layak)
     * - Rendah/Kurang/Sempit: 0.4 (kurang layak)
     * - Default: 0.5
     */
    private static function getFallbackK(string $namaSub): float
    {
        $n = strtolower($namaSub);
        
        return match (true) {
            str_contains($n, 'sangat') 
            || str_contains($n, 'luas') 
            || str_contains($n, 'tinggi')
            || str_contains($n, 'baik')
            || str_contains($n, 'lengkap') => 1.0,
            
            str_contains($n, 'sedang') 
            || str_contains($n, 'cukup')
            || str_contains($n, 'menengah') => 0.7,
            
            str_contains($n, 'rendah') 
            || str_contains($n, 'kurang')
            || str_contains($n, 'sempit')
            || str_contains($n, 'buruk')
            || str_contains($n, 'baru') => 0.4,
            
            default => 0.5,
        };
    }

    /**
     * Menghitung derajat keanggotaan (μ) berdasarkan tipe kurva
     * 
     * Supported Curves:
     * - bahu_kiri: Derajat tinggi di awal, menurun ke nol (≤ d)
     * - bahu_kanan: Derajat rendah di awal, naik ke satu (≥ b)
     * - trapesium: Naik (a→b), tetap tinggi (b→c), turun (c→d)
     * 
     * Parameter:
     * - a (batas_bawah): Batas paling bawah
     * - b (batas_tengah_1): Batas naik
     * - c (batas_tengah_2): Batas turun
     * - d (batas_atas): Batas paling atas
     */
    private static function hitungMu(object $sub, string $input): float
    {
        // Jika input non-numerik, gunakan string matching
        if (!is_numeric($input)) {
            return strtolower($input) === strtolower(trim($sub->nama_sub_kriteria)) ? 1.0 : 0.0;
        }

        $x = (float) $input;
        $a = (float) ($sub->batas_bawah ?? 0);
        $b = (float) ($sub->batas_tengah_1 ?? 0);
        $c = (float) ($sub->batas_tengah_2 ?? 0);
        $d = (float) ($sub->batas_atas ?? 0);

        // Validasi parameter kurva untuk menghindari division by zero
        if ($b == $a) $b = $a + 0.001;
        if ($c == $b) $c = $b + 0.001;
        if ($d == $c) $d = $c + 0.001;

        return match ($sub->tipe_kurva) {
            // Bahu Kiri: Derajat 1 sampai c, kemudian turun linear menuju d
            'bahu_kiri'  => ($x <= $c) 
                ? 1.0 
                : (($x >= $d) ? 0.0 : (($d - $x) / ($d - $c))),

            // Trapesium: Naik (a→b), plateau (b→c), turun (c→d)
            'trapesium'  => ($x <= $a || $x >= $d) 
                ? 0.0 
                : (($x >= $b && $x <= $c) 
                    ? 1.0 
                    : ($x < $b 
                        ? (($x - $a) / ($b - $a)) 
                        : (($d - $x) / ($d - $c)))),

            // Bahu Kanan: Derajat 0 sampai a, kemudian naik linear menuju b
            'bahu_kanan' => ($x <= $a) 
                ? 0.0 
                : (($x >= $b) ? 1.0 : (($x - $a) / ($b - $a))),

            default      => 0.0,
        };
    }

    /**
     * Menghasilkan kombinasi rule melalui Cartesian Product
     * 
     * Contoh:
     * Input: ['K1' => [h1, h2], 'K2' => [h3, h4]]
     * Output: [[K1=>h1, K2=>h3], [K1=>h1, K2=>h4], [K1=>h2, K2=>h3], [K1=>h2, K2=>h4]]
     */
    private static function kartesian(array $arrays): array
    {
        $result = [[]];
        
        foreach ($arrays as $kode => $pool) {
            $append = [];
            foreach ($result as $existing) {
                foreach ($pool as $item) {
                    $append[] = $existing + [$kode => $item];
                }
            }
            $result = $append;
        }
        
        return $result;
    }

    /**
     * Menentukan skala prioritas dari nilai Z (0-1)
     * 
     * Pendekatan Bahu: Semakin tinggi Z semakin layak
     * 
     * Skala:
     * - Z > 0.80: Prioritas I (Sangat Diprioritaskan) → Layak
     * - 0.60 < Z ≤ 0.80: Prioritas II (Diprioritaskan) → Layak
     * - 0.40 < Z ≤ 0.60: Prioritas III (Dipertimbangkan) → Tidak Layak
     * - Z ≤ 0.40: Prioritas IV (Tidak Diprioritaskan) → Tidak Layak
     */
    private static function getSkalaPrioritas(float $z): array
    {
        return match (true) {
            $z > 0.80 => [
                'prioritas'   => 'Prioritas I',
                'interpretasi' => 'Sangat Diprioritaskan',
                'status'       => 'Layak'
            ],
            $z > 0.60 => [
                'prioritas'    => 'Prioritas II',
                'interpretasi' => 'Diprioritaskan',
                'status'       => 'Layak'
            ],
            $z > 0.40 => [
                'prioritas'    => 'Prioritas III',
                'interpretasi' => 'Dipertimbangkan',
                'status'       => 'Tidak Layak'
            ],
            default   => [
                'prioritas'    => 'Prioritas IV',
                'interpretasi' => 'Tidak Diprioritaskan',
                'status'       => 'Tidak Layak'
            ],
        };
    }
}
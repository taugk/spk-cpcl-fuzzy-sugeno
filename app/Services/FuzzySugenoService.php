<?php

namespace App\Services;

use App\Models\Cpcl;
use App\Models\Kriteria;
use App\Models\HasilFuzzy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

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
                        'ranking'          => $rank + 1, // Menyimpan peringkat
                    ]
                );
            }
        });

        return $ranked;
    }

    // =========================================================================
    // 3. CORE: Fuzzy Sugeno Logic
    // =========================================================================
    public static function hitung(int $cpcl_id): array
    {
        $cpcl         = Cpcl::findOrFail($cpcl_id);
        $kriteriaList = Kriteria::with('subKriteria')->orderBy('id')->get();
        $fuzzifikasi  = [];

        // --- STEP 1: Fuzzifikasi ---
        foreach ($kriteriaList as $kriteria) {
            $field = $kriteria->mapping_field;
            
            // Ambil input dari tabel cpcl atau cpcl_penilaian
            $rawInput = ($kriteria->jenis_kriteria === 'kontinu') 
                        ? (string) ($cpcl->{$field} ?? '') 
                        : trim(DB::table('cpcl_penilaian')->where('cpcl_id', $cpcl_id)->where('kriteria_id', $kriteria->id)->value('nilai') ?? '');

            // Fallback: Jika input kosong, berikan '0' agar perhitungan tidak error
            $inputVal = empty($rawInput) ? '0' : $rawInput;
            $himpunan = [];

            foreach ($kriteria->subKriteria as $sub) {
                $mu = self::hitungMu($sub, $inputVal);
                $k  = (float) ($sub->nilai_konsekuen ?? 0);
                
                // Fallback Konsekuen: Jika di DB 0, berikan nilai standar berdasarkan namanya
                if ($k == 0) {
                    $k = self::getFallbackK($sub->nama_sub_kriteria);
                }

                $himpunan[] = [
                    'nama' => $sub->nama_sub_kriteria, 
                    'mu' => $mu, 
                    'k' => $k
                ];
            }
            $fuzzifikasi[] = [
                'kode'     => $kriteria->kode_kriteria, 
                'nama'     => $kriteria->nama_kriteria,
                'input'    => $inputVal,
                'himpunan' => $himpunan,
                'sub'      => $himpunan // Alias untuk kompatibilitas dengan view
            ];
        }

        // --- STEP 2: Evaluasi Rule Base ---
        $himpunanAktif = [];
        foreach ($fuzzifikasi as $kFuzz) {
            $aktif = array_filter($kFuzz['himpunan'], fn($h) => $h['mu'] > 0);
            if (!empty($aktif)) {
                $himpunanAktif[$kFuzz['kode']] = array_values($aktif);
            }
        }

        $rules = [];
        if (!empty($himpunanAktif)) {
            $kombinasi = self::kartesian($himpunanAktif);
            foreach ($kombinasi as $idx => $combo) {
                $muValues = array_column($combo, 'mu');
                $kValues  = array_column($combo, 'k');

                $alpha = min($muValues);
                $zRule = array_sum($kValues) / count($kValues);
                
                // Menyusun anteceden untuk keperluan log/view
                $anteceden = [];
                foreach ($combo as $kode => $h) {
                    $anteceden[] = ['kriteria' => $kode, 'himpunan' => $h['nama']];
                }

                $rules[] = [
                    'rule_id'   => 'R' . str_pad($idx + 1, 3, '0', STR_PAD_LEFT),
                    'anteceden' => $anteceden,
                    'alpha'     => $alpha, 
                    'z_rule'    => $zRule,
                    'alpha_x_z' => $alpha * $zRule
                ];
            }
        }

        // --- STEP 3: Defuzzifikasi ---
        $sumAlphaZ = array_sum(array_column($rules, 'alpha_x_z'));
        $sumAlpha  = array_sum(array_column($rules, 'alpha'));
        
        $z         = $sumAlpha > 0 ? ($sumAlphaZ / $sumAlpha) : 0.0;
        $skala     = self::getSkalaPrioritas($z);

        // --- PENGEMBALIAN DATA ---
        // PENTING: Jangan hapus 'cpcl', 'fuzzifikasi', dan 'rules'. View membutuhkannya.
        return [
            // Data untuk View (detail.blade.php)
            'cpcl'             => $cpcl,
            'fuzzifikasi'      => $fuzzifikasi,
            'rules'            => $rules,
            'sum_alpha_z'      => round($sumAlphaZ, 6),
            'sum_alpha'        => round($sumAlpha, 6),
            
            // Data untuk Database
            'alpha'            => !empty($rules) ? max(array_column($rules, 'alpha')) : 0.0,
            'z'                => round($z, 6),
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
     * Memberikan nilai konsekuen default jika di database bernilai 0
     */
    private static function getFallbackK(string $namaSub): float
    {
        $n = strtolower($namaSub);
        return match (true) {
            str_contains($n, 'sangat') || str_contains($n, 'luas') || str_contains($n, 'tinggi') => 1.0,
            str_contains($n, 'sedang') || str_contains($n, 'lengkap') || str_contains($n, 'cukup') => 0.7,
            str_contains($n, 'rendah') || str_contains($n, 'kurang') || str_contains($n, 'baru') || str_contains($n, 'sempit') => 0.4,
            default => 0.5,
        };
    }

    /**
     * Menghitung nilai keanggotaan (mu)
     */
    private static function hitungMu(object $sub, string $input): float
    {
        if (!is_numeric($input)) {
            return strtolower($input) === strtolower(trim($sub->nama_sub_kriteria)) ? 1.0 : 0.0;
        }

        $x = (float) $input;
        $a = (float) $sub->batas_bawah;
        $b = (float) $sub->batas_tengah_1;
        $c = (float) $sub->batas_tengah_2;
        $d = (float) $sub->batas_atas;

        return match ($sub->tipe_kurva) {
            'bahu_kiri'  => ($x <= $c) ? 1.0 : (($x >= $d) ? 0.0 : ($d - $x) / ($d - $c)),
            'trapesium'  => ($x <= $a || $x >= $d) ? 0.0 : (($x >= $b && $x <= $c) ? 1.0 : ($x < $b ? ($x - $a) / ($b - $a) : ($d - $x) / ($d - $c))),
            'bahu_kanan' => ($x <= $a) ? 0.0 : (($x >= $b) ? 1.0 : ($x - $a) / ($b - $a)),
            default      => 0.0,
        };
    }

    /**
     * Menghasilkan kombinasi rule (Cross Join / Cartesian Product)
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
     */
    private static function getSkalaPrioritas(float $z): array
    {
        return match (true) {
            $z > 0.80 => ['prioritas' => 'Prioritas I',   'interpretasi' => 'Sangat Diprioritaskan', 'status' => 'Layak'],
            $z > 0.60 => ['prioritas' => 'Prioritas II',  'interpretasi' => 'Diprioritaskan',        'status' => 'Layak'],
            $z > 0.40 => ['prioritas' => 'Prioritas III', 'interpretasi' => 'Dipertimbangkan',      'status' => 'Tidak Layak'],
            default   => ['prioritas' => 'Prioritas IV',  'interpretasi' => 'Tidak Diprioritaskan',  'status' => 'Tidak Layak'],
        };
    }
}
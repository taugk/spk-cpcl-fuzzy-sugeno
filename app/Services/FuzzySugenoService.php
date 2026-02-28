<?php

namespace App\Services;

use App\Models\Cpcl;
use App\Models\Kriteria;
use App\Models\HasilFuzzy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FuzzySugenoService
{
    // =========================================================================
    // PUBLIC: Hitung + Simpan ke DB
    // =========================================================================

    public static function hitungDanSimpan(int $cpcl_id): array
    {
        Log::info("=== START: Hitung dan Simpan Fuzzy Sugeno untuk CPCL ID: {$cpcl_id} ===");

        $hasil = self::hitung($cpcl_id);

        HasilFuzzy::updateOrCreate(
            ['cpcl_id' => $cpcl_id],
            [
                'nilai_alpha'      => $hasil['alpha'],
                'nilai_z'          => $hasil['z'],
                'skor_akhir'       => $hasil['skor_akhir'],
                'status_kelayakan' => $hasil['status_kelayakan'],
            ]
        );

        Log::info("=== END: Hitung dan Simpan Selesai untuk CPCL ID: {$cpcl_id} ===", [
            'skor_akhir' => $hasil['skor_akhir'],
            'status'     => $hasil['status_kelayakan'],
        ]);

        return $hasil;
    }

    // =========================================================================
    // CORE: Fuzzy Sugeno Orde Nol — Direct Alternative Rule Base
    // =========================================================================

    public static function hitung(int $cpcl_id): array
    {
        Log::debug("Memulai proses hitung() untuk CPCL ID: {$cpcl_id}");

        $cpcl         = Cpcl::findOrFail($cpcl_id);
        $kriteriaList = Kriteria::with('subKriteria')->orderBy('id')->get();

        // ── STEP 1: Fuzzifikasi ──────────────────────────────────────────────
        Log::debug("--- STEP 1: Fuzzifikasi ---");

        $fuzzifikasi = [];

        foreach ($kriteriaList as $kriteria) {

            if ($kriteria->jenis_kriteria === 'kontinu') {
                $field    = $kriteria->mapping_field;
                $rawInput = '';

                if ($field && array_key_exists($field, $cpcl->getAttributes())) {
                    $rawInput = (string) $cpcl->$field;
                    Log::debug("Kriteria [{$kriteria->kode_kriteria}] {$kriteria->nama_kriteria} | Field: {$field} | Nilai dari cpcl: '{$rawInput}'");
                } else {
                    Log::warning("mapping_field '{$field}' tidak ditemukan di tabel cpcl untuk kriteria [{$kriteria->kode_kriteria}]");
                }

            } else {
                $penilaian = DB::table('cpcl_penilaian')
                    ->where('cpcl_id', $cpcl_id)
                    ->where('kriteria_id', $kriteria->id)
                    ->first();

                $rawInput = trim($penilaian?->nilai ?? '');
                Log::debug("Kriteria [{$kriteria->kode_kriteria}] {$kriteria->nama_kriteria} | Input dari penilaian: '{$rawInput}'");
            }

            $x = is_numeric($rawInput) ? (float) $rawInput : null;

            $himpunan = [];
            foreach ($kriteria->subKriteria as $sub) {
                $mu = self::hitungMu($sub, $rawInput);
                $k  = (float) ($sub->nilai_konsekuen ?? 0);

                // ── MENGHITUNG KONSEKUEN KONTINU JIKA NOL ────────────────────
                if ($k == 0.0 && $kriteria->jenis_kriteria === 'kontinu') {
                    $k = self::hitungKonsekuenKontinu($sub, $x);
                }

                Log::debug("  -> Sub: {$sub->nama_sub_kriteria} | Kurva: {$sub->tipe_kurva} | mu: {$mu} | k: {$k}");

                $himpunan[] = [
                    'sub_id' => $sub->id,
                    'nama'   => $sub->nama_sub_kriteria,
                    'tipe'   => $sub->tipe_kurva,
                    'a'      => (float) $sub->batas_bawah,
                    'b'      => (float) $sub->batas_tengah_1,
                    'c'      => (float) $sub->batas_tengah_2,
                    'd'      => (float) $sub->batas_atas,
                    'k'      => $k,
                    'mu'     => round($mu, 6),
                ];
            }

            $fuzzifikasi[] = [
                'kriteria_id'   => $kriteria->id,
                'kode'          => $kriteria->kode_kriteria,
                'nama'          => $kriteria->nama_kriteria,
                'jenis'         => $kriteria->jenis_kriteria,
                'mapping_field' => $kriteria->mapping_field ?? null,
                'input'         => $rawInput,
                'x'             => $x,
                'himpunan'      => $himpunan,
                'sub'           => $himpunan,
            ];
        }

        // ── STEP 2 & 3: Bentuk Rule + Hitung Firing Strength (α) ───────────
        Log::debug("--- STEP 2 & 3: Rule Base & Firing Strength ---");

        $himpunanAktifPerKriteria = [];

        foreach ($fuzzifikasi as $kFuzz) {
            $aktif = array_filter($kFuzz['himpunan'], fn($h) => $h['mu'] > 0);

            if (empty($aktif)) {
                Log::warning("PERINGATAN: Tidak ada himpunan aktif untuk kriteria {$kFuzz['kode']} dengan input '{$kFuzz['input']}'");
                continue;
            }

            Log::debug("Himpunan aktif untuk {$kFuzz['kode']}: " .
                implode(', ', array_column(array_values($aktif), 'nama')));

            $himpunanAktifPerKriteria[$kFuzz['kode']] = array_values($aktif);
        }

        $rules = [];

        if (!empty($himpunanAktifPerKriteria)) {
            $kombinasi = self::kartesian($himpunanAktifPerKriteria);
            Log::debug("Total kombinasi rules terbentuk: " . count($kombinasi));

            foreach ($kombinasi as $idx => $combo) {
                $muValues = array_column($combo, 'mu');
                $kValues  = array_column($combo, 'k');

                $alpha = min($muValues);                          
                $zRule = array_sum($kValues) / count($kValues);  

                $anteceden = [];
                foreach ($combo as $kode => $h) {
                    $anteceden[] = [
                        'kriteria' => $kode,
                        'himpunan' => $h['nama'],
                        'mu'       => $h['mu'],
                        'k'        => $h['k'],
                    ];
                }

                $ruleId = 'R' . str_pad($idx + 1, 3, '0', STR_PAD_LEFT);
                Log::debug("{$ruleId} | alpha: {$alpha} | z_rule: {$zRule} | alpha_x_z: " . round($alpha * $zRule, 6));

                $rules[] = [
                    'rule_id'   => $ruleId,
                    'anteceden' => $anteceden,
                    'alpha'     => round($alpha, 6),
                    'z_rule'    => round($zRule, 6),
                    'alpha_x_z' => round($alpha * $zRule, 6),
                ];
            }
        } else {
            Log::warning("Tidak ada kriteria dengan himpunan aktif. Semua skor = 0.");
        }

        // ── STEP 4: Defuzzifikasi — Weighted Average ─────────────────────────
        Log::debug("--- STEP 4: Defuzzifikasi ---");

        $sumAlphaZ = array_sum(array_column($rules, 'alpha_x_z'));
        $sumAlpha  = array_sum(array_column($rules, 'alpha'));
        $z         = $sumAlpha > 0 ? ($sumAlphaZ / $sumAlpha) : 0.0;
        $skorAkhir = round($z * 100, 2);

        Log::debug("Hasil Defuzzifikasi: " . json_encode([
            'Sum(alpha * z)' => round($sumAlphaZ, 6),
            'Sum(alpha)'     => round($sumAlpha, 6),
            'z (crisp)'      => round($z, 6),
            'Skor Akhir'     => $skorAkhir,
        ]));

        $alphaGlobal = !empty($rules) ? max(array_column($rules, 'alpha')) : 0.0;
        $skala       = self::getSkalaPrioritas($z);

        Log::debug("Skala Prioritas: {$skala['prioritas']} - {$skala['status']}");

        return [
            'cpcl'             => $cpcl,
            'fuzzifikasi'      => $fuzzifikasi,
            'rules'            => $rules,
            'sum_alpha_z'      => round($sumAlphaZ, 6),
            'sum_alpha'        => round($sumAlpha, 6),
            'alpha'            => round($alphaGlobal, 6),
            'z'                => round($z, 6),
            'skor_akhir'       => $skorAkhir,
            'status_kelayakan' => $skala['status'],
            'skala_prioritas'  => $skala['prioritas'],
            'interpretasi'     => $skala['interpretasi'],
        ];
    }

    // =========================================================================
    // FUZZIFIKASI: Hitung μ satu sub_kriteria terhadap nilai input
    // =========================================================================

    private static function hitungMu(object $sub, string $rawInput): float
    {
        $rawInput = trim($rawInput);

        if (!is_numeric($rawInput)) {
            return strtolower($rawInput) === strtolower(trim($sub->nama_sub_kriteria))
                ? 1.0
                : 0.0;
        }

        $x = (float) $rawInput;
        $a = (float) $sub->batas_bawah;
        $b = (float) $sub->batas_tengah_1;
        $c = (float) $sub->batas_tengah_2;
        $d = (float) $sub->batas_atas;

        return match ($sub->tipe_kurva) {
            'bahu_kiri' => match (true) {
                $x <= $c       => 1.0,
                $x >= $d       => 0.0,
                ($d - $c) == 0 => 0.0,
                default        => ($d - $x) / ($d - $c),
            },
            'trapesium' => match (true) {
                $x <= $a             => 0.0,
                $x >= $d             => 0.0,
                $x >= $b && $x <= $c => 1.0,
                $x < $b              => ($b - $a) == 0 ? 0.0 : ($x - $a) / ($b - $a),
                default              => ($d - $c) == 0 ? 0.0 : ($d - $x) / ($d - $c),
            },
            'bahu_kanan' => match (true) {
                $x <= $a       => 0.0,
                $x >= $b       => 1.0,
                ($b - $a) == 0 => 0.0,
                default        => ($x - $a) / ($b - $a),
            },
            default => 0.0,
        };
    }

    // =========================================================================
    // HELPER: Kalkulasi Konsekuen Kontinu
    // =========================================================================

    private static function hitungKonsekuenKontinu(object $sub, ?float $x): float
    {
        // Berhubung perhitungan Anda menggunakan basis persentase z* (0 hingga 1),
        // fungsi ini memetakan tipe kurva himpunan ke nilai baku konsekuen Sugeno.
        // Anda bebas mengatur bobot benefit/cost di sini.

        return match ($sub->tipe_kurva) {
            'bahu_kiri'  => 0.25, // Nilai default representasi 'Kurang / Rendah'
            'trapesium'  => 0.60, // Nilai default representasi 'Cukup / Sedang'
            'bahu_kanan' => 0.95, // Nilai default representasi 'Sangat Baik / Tinggi'
            default      => 0.50,
        };
    }

    // =========================================================================
    // HELPER: Kombinasi Kartesian
    // =========================================================================

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

    // =========================================================================
    // HELPER: Skala Prioritas
    // =========================================================================

    private static function getSkalaPrioritas(float $z): array
    {
        return match (true) {
            $z > 0.80 => ['prioritas' => 'Prioritas I',   'interpretasi' => 'Sangat Diprioritaskan', 'status' => 'Layak'],
            $z > 0.60 => ['prioritas' => 'Prioritas II',  'interpretasi' => 'Diprioritaskan',        'status' => 'Layak'],
            $z > 0.40 => ['prioritas' => 'Prioritas III', 'interpretasi' => 'Dipertimbangkan',       'status' => 'Tidak Layak'],
            default   => ['prioritas' => 'Prioritas IV',  'interpretasi' => 'Tidak Diprioritaskan',  'status' => 'Tidak Layak'],
        };
    }
}
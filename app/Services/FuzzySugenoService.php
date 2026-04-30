<?php

namespace App\Services;

use App\Models\Cpcl;
use App\Models\Kriteria;
use App\Models\HasilFuzzy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

/**
 * FuzzySugenoService
 *
 * Alur perhitungan disesuaikan dengan naskah BAB IV:
 * ─────────────────────────────────────────────────
 * 1. Fuzzifikasi
 *    • C1 (Luas Lahan)            – kontinu, kurva bahu kiri / segitiga / bahu kanan
 *    • C2 (Kepemilikan Lahan)     – diskrit, μ tetap: Milik=0.70 | Sewa/Garap=0.40 | Tidak=0.20
 *    • C3 (Lama Berdiri Kelompok) – kontinu, kurva bahu kiri / segitiga / bahu kanan
 *    • C4 (Produktivitas)         – kontinu, kurva bahu kiri / segitiga / bahu kanan
 *    • C5 (Kelengkapan Dokumen)   – diskrit, μ tetap: Sangat=0.80 | Lengkap=0.50 | Tidak=0.30
 *
 * 2. Rule Base (R1–R16 sesuai Tabel 3 naskah)
 *    Operator AND → MIN atas μ kelima kriteria.
 *    Hanya rule yang cocok (α > 0) yang masuk perhitungan.
 *
 * 3. Defuzzifikasi – Weighted Average (Sugeno):
 *    Z = Σ(αᵢ × kᵢ) / Σ(αᵢ)
 *    kᵢ (nilai konsekuen rule) sesuai naskah:
 *      Tidak Layak      = 0.25  (R1,R2,R8,R9,R15)
 *      Dipertimbangkan  = 0.50  (R3,R4,R11,R12,R13)
 *      Layak            = 0.75  (R5,R6,R7,R10,R14)
 *      Sangat Layak     = 1.00  (R16)
 *
 * 4. Skala Prioritas (Tabel 3.26 naskah):
 *    0.81–1.00 → Prioritas I   (Sangat Diprioritaskan)
 *    0.61–0.80 → Prioritas II  (Diprioritaskan)
 *    0.41–0.60 → Prioritas III (Dipertimbangkan)
 *    ≤ 0.40    → Prioritas IV  (Tidak Diprioritaskan)
 */
class FuzzySugenoService
{
    // =========================================================================
    // RULE BASE NASKAH (R1–R16)
    // Setiap rule: [C1, C2, C3, C4, C5, k_output]
    // Label himpunan dalam huruf kecil agar pencocokan lebih robust.
    // =========================================================================
    private const RULES = [
        'R1'  => ['c1' => 'sempit',  'c2' => 'tidak memiliki', 'c3' => 'baru',       'c4' => 'rendah', 'c5' => 'tidak lengkap',  'k' => 0.25],
        'R2'  => ['c1' => 'sempit',  'c2' => 'sewa',           'c3' => 'baru',       'c4' => 'rendah', 'c5' => 'tidak lengkap',  'k' => 0.25],
        'R3'  => ['c1' => 'sempit',  'c2' => 'milik',          'c3' => 'lama',       'c4' => 'sedang', 'c5' => 'lengkap',        'k' => 0.50],
        'R4'  => ['c1' => 'sedang',  'c2' => 'sewa',           'c3' => 'lama',       'c4' => 'sedang', 'c5' => 'lengkap',        'k' => 0.25],
        'R5'  => ['c1' => 'sedang',  'c2' => 'milik',          'c3' => 'lama',       'c4' => 'tinggi', 'c5' => 'sangat lengkap', 'k' => 0.75],
        'R6'  => ['c1' => 'luas',    'c2' => 'milik',          'c3' => 'sangat lama','c4' => 'tinggi', 'c5' => 'sangat lengkap', 'k' => 0.75],
        'R7'  => ['c1' => 'luas',    'c2' => 'sewa',           'c3' => 'lama',       'c4' => 'tinggi', 'c5' => 'lengkap',        'k' => 0.75],
        'R8'  => ['c1' => 'luas',    'c2' => 'tidak memiliki', 'c3' => 'baru',       'c4' => 'rendah', 'c5' => 'tidak lengkap',  'k' => 0.25],
        'R9'  => ['c1' => 'sedang',  'c2' => 'tidak memiliki', 'c3' => 'baru',       'c4' => 'rendah', 'c5' => 'tidak lengkap',  'k' => 0.25],
        'R10' => ['c1' => 'sedang',  'c2' => 'milik',          'c3' => 'lama',       'c4' => 'tinggi', 'c5' => 'lengkap',        'k' => 0.75],
        'R11' => ['c1' => 'sedang',  'c2' => 'milik',          'c3' => 'baru',       'c4' => 'sedang', 'c5' => 'lengkap',        'k' => 0.50],
        'R12' => ['c1' => 'luas',    'c2' => 'sewa',           'c3' => 'baru',       'c4' => 'sedang', 'c5' => 'lengkap',        'k' => 0.50],
        'R13' => ['c1' => 'sempit',  'c2' => 'milik',          'c3' => 'baru',       'c4' => 'sedang', 'c5' => 'lengkap',        'k' => 0.50],
        'R14' => ['c1' => 'luas',    'c2' => 'milik',          'c3' => 'lama',       'c4' => 'sedang', 'c5' => 'sangat lengkap', 'k' => 0.75],
        'R15' => ['c1' => 'sedang',  'c2' => 'sewa',           'c3' => 'lama',       'c4' => 'rendah', 'c5' => 'tidak lengkap',  'k' => 0.25],
        'R16' => ['c1' => 'luas',    'c2' => 'milik',          'c3' => 'lama',       'c4' => 'tinggi', 'c5' => 'sangat lengkap', 'k' => 1.00],
    ];

    // =========================================================================
    // PUBLIC: Hitung + Simpan ke tabel hasil_fuzzy
    // =========================================================================
    public static function hitungDanSimpan(int $cpcl_id): array
    {
        Log::info("╔══════════════════════════════════════════════════════════╗");
        Log::info("║  [FUZZY SUGENO] START hitungDanSimpan | CPCL ID: {$cpcl_id}");
        Log::info("╚══════════════════════════════════════════════════════════╝");

        try {
            // ── Validasi data ────────────────────────────────────────────────
            Log::debug("[VALIDASI] Memeriksa kelengkapan data CPCL ID: {$cpcl_id}");
            $validasi = self::cekSinkronisasiData($cpcl_id);

            if (!$validasi['is_valid']) {
                Log::warning("[VALIDASI] GAGAL – CPCL ID {$cpcl_id}: " . implode(' | ', $validasi['messages']));
                throw new \Exception("Data tidak valid: " . implode(', ', $validasi['messages']));
            }

            Log::debug("[VALIDASI] OK – semua kriteria terisi.");

            // ── Hitung ──────────────────────────────────────────────────────
            $hasil = self::hitung($cpcl_id);

            // ── Simpan ke DB ─────────────────────────────────────────────────
            Log::debug("[DB] Menyimpan hasil ke tabel hasil_fuzzy …");
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
            Log::info("[DB] Tersimpan → Z={$hasil['z']} | Skor={$hasil['skor_akhir']}% | {$hasil['skala_prioritas']} ({$hasil['status_kelayakan']})");

            Log::info("╔══════════════════════════════════════════════════════════╗");
            Log::info("║  [FUZZY SUGENO] SELESAI | CPCL ID: {$cpcl_id}");
            Log::info("╚══════════════════════════════════════════════════════════╝");

            return $hasil;

        } catch (\Exception $e) {
            Log::error("╔══════════════════════════════════════════════════════════╗");
            Log::error("║  [FUZZY SUGENO] ERROR | CPCL ID: {$cpcl_id}");
            Log::error("║  Pesan  : " . $e->getMessage());
            Log::error("║  File   : " . $e->getFile() . " (baris " . $e->getLine() . ")");
            Log::error("╚══════════════════════════════════════════════════════════╝");
            throw $e;
        }
    }

    // =========================================================================
    // PUBLIC: Inti perhitungan Fuzzy Sugeno
    // =========================================================================
    public static function hitung(int $cpcl_id): array
    {
        $cpcl         = Cpcl::findOrFail($cpcl_id);
        $kriteriaList = Kriteria::with('subKriteria')->orderBy('id')->get();

        Log::info("┌─────────────────────────────────────────────────────────┐");
        Log::info("│ [HITUNG] CPCL ID: {$cpcl_id} | Nama: {$cpcl->nama_kelompok}");
        Log::info("└─────────────────────────────────────────────────────────┘");

        // =====================================================================
        // LANGKAH 1 – FUZZIFIKASI
        // Mengubah nilai crisp setiap kriteria menjadi derajat keanggotaan μ.
        // =====================================================================
        Log::info("── [LANGKAH 1] FUZZIFIKASI ──────────────────────────────────");

        $fuzzifikasi = [];

        foreach ($kriteriaList as $kriteria) {
            $field = $kriteria->mapping_field;
            $kode  = strtolower($kriteria->kode_kriteria); // 'c1' … 'c5'

            // Ambil nilai input
            if ($kriteria->jenis_kriteria === 'kontinu') {
                $inputVal = $cpcl->{$field} ?? '0';
                Log::debug("  [{$kriteria->kode_kriteria}] Jenis: kontinu | Field: {$field} | Nilai raw: {$inputVal}");
            } else {
                $inputVal = DB::table('cpcl_penilaian')
                    ->where('cpcl_id', $cpcl_id)
                    ->where('kriteria_id', $kriteria->id)
                    ->value('nilai') ?? $cpcl->{$field} ?? '-';
                Log::debug("  [{$kriteria->kode_kriteria}] Jenis: diskrit  | Field: {$field} | Nilai raw: {$inputVal}");
            }

            $inputString = (string) $inputVal;

            // Hitung μ tiap himpunan
            $himpunanAktif  = [];
            $himpunanSkip   = [];

            foreach ($kriteria->subKriteria as $sub) {
                $mu = self::hitungMu($sub, $inputString);

                if ($mu > 0) {
                    $himpunanAktif[] = [
                        'nama'   => $sub->nama_sub_kriteria,
                        'mu'     => round($mu, 4),
                        // Disertakan untuk kebutuhan visualisasi chart di view
                        'tipe'   => $sub->tipe_kurva,
                        'params' => [
                            'tipe' => $sub->tipe_kurva,
                            'a'    => (float) ($sub->batas_bawah    ?? 0),
                            'b'    => (float) ($sub->batas_tengah_1 ?? 0),
                            'c'    => (float) ($sub->batas_tengah_2 ?? 0),
                            'd'    => (float) ($sub->batas_atas     ?? 0),
                        ],
                    ];
                    Log::debug("    ✔ Himpunan [{$sub->nama_sub_kriteria}] μ = " . round($mu, 4)
                        . " | tipe_kurva: {$sub->tipe_kurva}");
                } else {
                    $himpunanSkip[] = [
                        'nama'   => $sub->nama_sub_kriteria,
                        'mu'     => 0.0,
                        'tipe'   => $sub->tipe_kurva,
                        'params' => [
                            'tipe' => $sub->tipe_kurva,
                            'a'    => (float) ($sub->batas_bawah    ?? 0),
                            'b'    => (float) ($sub->batas_tengah_1 ?? 0),
                            'c'    => (float) ($sub->batas_tengah_2 ?? 0),
                            'd'    => (float) ($sub->batas_atas     ?? 0),
                        ],
                    ];
                }
            }

            // Semua himpunan (aktif + tidak aktif) untuk keperluan visualisasi kurva
            $semuaHimpunan = array_merge($himpunanAktif, $himpunanSkip);

            $skipNama = array_column($himpunanSkip, 'nama');
            if (!empty($skipNama)) {
                Log::debug("    ✘ Tidak aktif: " . implode(', ', $skipNama));
            }

            if (empty($himpunanAktif)) {
                Log::warning("  [{$kriteria->kode_kriteria}] ⚠ Tidak ada himpunan aktif untuk input '{$inputString}'");
            }

            $fuzzifikasi[$kode] = [
                'kode'           => $kriteria->kode_kriteria,
                'nama'           => $kriteria->nama_kriteria,
                'input'          => $inputString,
                'jenis'          => $kriteria->jenis_kriteria,
                'himpunan'       => $himpunanAktif,   // hanya yang μ > 0
                'semua_himpunan' => $semuaHimpunan,   // semua, untuk chart kurva
            ];
        }

        // Buat lookup μ: [kode_kriteria][nama_himpunan_lower] = μ
        $muLookup = [];
        foreach ($fuzzifikasi as $kode => $data) {
            foreach ($data['himpunan'] as $h) {
                $muLookup[$kode][strtolower($h['nama'])] = $h['mu'];
            }
        }

        Log::debug("  [LOOKUP μ] " . json_encode($muLookup));

        // =====================================================================
        // LANGKAH 2 – INFERENSI FUZZY (RULE BASE R1–R16)
        // Operator AND → MIN atas μ kelima kriteria.
        // =====================================================================
        Log::info("── [LANGKAH 2] INFERENSI RULE BASE ─────────────────────────");

        $rules       = [];
        $totalAlphaZ = 0.0;
        $totalAlpha  = 0.0;
        $maxAlpha    = 0.0;
        $ruleAktif   = 0;
        $ruleSkip    = 0;

        foreach (self::RULES as $ruleId => $rule) {
            $muPerKriteria = [];
            $matchGagal    = false;
            $debugAnteceden = [];

            foreach (['c1', 'c2', 'c3', 'c4', 'c5'] as $kode) {
                $labelRule = $rule[$kode];
                $mu        = self::cariMuDariLookup($muLookup[$kode] ?? [], $labelRule);

                $debugAnteceden[] = "{$kode}='{$labelRule}'(μ=" . round($mu, 4) . ")";

                if ($mu <= 0) {
                    $matchGagal = true;
                    break;
                }
                $muPerKriteria[$kode] = $mu;
            }

            if ($matchGagal) {
                Log::debug("  [{$ruleId}] ✘ SKIP  – " . implode(', ', $debugAnteceden));
                $ruleSkip++;
                continue;
            }

            // Operator AND → MIN (firing strength)
            $alpha = min($muPerKriteria);

            if ($alpha <= 0) {
                Log::debug("  [{$ruleId}] ✘ SKIP  – α=0 setelah MIN");
                $ruleSkip++;
                continue;
            }

            $k        = $rule['k'];
            $alphaXk  = round($alpha * $k, 4);

            Log::info("  [{$ruleId}] ✔ AKTIF – " . implode(', ', $debugAnteceden));
            Log::info("           α = min(" . implode(', ', array_map(fn($v) => round($v, 4), $muPerKriteria)) . ") = " . round($alpha, 4));
            Log::info("           k = {$k}  |  α×k = {$alphaXk}");

            $rules[] = [
                'rule_id'   => $ruleId,
                'anteceden' => array_map(
                    fn($kode) => [
                        'kriteria' => $fuzzifikasi[$kode]['nama'] ?? $kode,
                        'himpunan' => $rule[$kode],
                        'mu'       => round($muPerKriteria[$kode], 4),
                    ],
                    ['c1', 'c2', 'c3', 'c4', 'c5']
                ),
                'alpha'     => round($alpha, 4),
                'k'         => $k,
                'alpha_x_k' => $alphaXk,
            ];

            $totalAlphaZ += ($alpha * $k);
            $totalAlpha  += $alpha;
            $ruleAktif++;

            if ($alpha > $maxAlpha) {
                $maxAlpha = $alpha;
            }
        }

        Log::info("  Ringkasan rule: {$ruleAktif} aktif, {$ruleSkip} skip dari " . count(self::RULES) . " total.");
        Log::info("  Σ(α×k) = " . round($totalAlphaZ, 4) . "  |  Σα = " . round($totalAlpha, 4));

        // =====================================================================
        // LANGKAH 3 – DEFUZZIFIKASI (Weighted Average)
        // Z = Σ(αᵢ × kᵢ) / Σ(αᵢ)
        // =====================================================================
        Log::info("── [LANGKAH 3] DEFUZZIFIKASI (Weighted Average) ────────────");

        if ($totalAlpha <= 0) {
            Log::warning("  ⚠ Σα = 0! Tidak ada rule aktif → Z = 0.0");
        }

        $z_final = $totalAlpha > 0 ? ($totalAlphaZ / $totalAlpha) : 0.0;
        $skala   = self::getSkalaPrioritas($z_final);

        Log::info("  Z = " . round($totalAlphaZ, 4) . " / " . round($totalAlpha, 4) . " = " . round($z_final, 4));
        Log::info("  Skor Akhir   : " . round($z_final * 100, 2) . "%");
        Log::info("  Skala        : {$skala['prioritas']}");
        Log::info("  Status       : {$skala['status']}");
        Log::info("  Interpretasi : {$skala['interpretasi']}");
        Log::info("─────────────────────────────────────────────────────────────");

        return [
            'cpcl'             => $cpcl,
            'fuzzifikasi'      => array_values($fuzzifikasi),
            'rules'            => $rules,
            'sum_alpha_z'      => round($totalAlphaZ, 4),
            'sum_alpha'        => round($totalAlpha,  4),
            'alpha'            => round($maxAlpha,    4),
            'z'                => round($z_final,     4),
            'skor_akhir'       => round($z_final * 100, 2),
            'status_kelayakan' => $skala['status'],
            'skala_prioritas'  => $skala['prioritas'],
            'interpretasi'     => $skala['interpretasi'],
        ];
    }

    // =========================================================================
    // PRIVATE: Hitung derajat keanggotaan μ satu sub-kriteria
    // =========================================================================

    /**
     * Menghitung μ sesuai naskah:
     *
     * Tipe 'diskrit' (C2, C5):
     *   Nilai μ sudah ditentukan tetap di naskah (nilai_konsekuen dipakai sebagai μ).
     *   C2 → Milik sendiri=0.70, Sewa/Garap=0.40, Tidak memiliki=0.20
     *   C5 → Sangat lengkap=0.80, Lengkap=0.50, Tidak lengkap=0.30
     *   Pencocokan dengan input string (case-insensitive).
     *
     * Tipe kontinu:
     *   'bahu_kiri'  → fungsi menurun (Sempit, Rendah, Pemula/Baru)
     *   'bahu_kanan' → fungsi menaik  (Luas, Tinggi, Sangat Lama)
     *   'segitiga'/'trapesium' → fungsi naik-turun (Sedang, Lama)
     */
    private static function hitungMu(object $sub, string $input): float
    {
        // ── Diskrit ──────────────────────────────────────────────────────────
        if ($sub->tipe_kurva === 'diskrit') {
            $cocok = self::labelCocok($sub->nama_sub_kriteria, $input);
            $mu    = $cocok ? (float) ($sub->nilai_konsekuen ?? 1.0) : 0.0;
            Log::debug("    [hitungMu:diskrit] sub='{$sub->nama_sub_kriteria}' | input='{$input}'"
                . " | cocok=" . ($cocok ? 'YA' : 'TIDAK') . " | μ={$mu}");
            return $mu;
        }

        // ── Kontinu: bersihkan input menjadi float ───────────────────────────
        $clean = preg_replace('/[^0-9.]/', '', $input);
        if ($clean === '' || !is_numeric($clean)) {
            Log::debug("    [hitungMu:kontinu] sub='{$sub->nama_sub_kriteria}' | input='{$input}'"
                . " → tidak bisa di-parse sebagai angka → μ=0");
            return 0.0;
        }

        $x = (float) $clean;
        $a = (float) ($sub->batas_bawah    ?? 0);
        $b = (float) ($sub->batas_tengah_1 ?? 0);
        $c = (float) ($sub->batas_tengah_2 ?? 0);
        $d = (float) ($sub->batas_atas     ?? 0);

        $mu = match ($sub->tipe_kurva) {

            // Bahu kiri: μ=1 saat x≤a, turun linear dari a ke b, μ=0 saat x≥b
            // Naskah: Sempit (a=1.5, b=3.5)
            'bahu_kiri' => ($x <= $a) ? 1.0
                         : (($x >= $b) ? 0.0
                         : ($b - $x) / ($b - $a)),

            // Bahu kanan: μ=0 saat x≤a, naik linear dari a ke b, μ=1 saat x≥b
            // Naskah: Luas (a=5, b=7)
            'bahu_kanan' => ($x <= $a) ? 0.0
                          : (($x >= $b) ? 1.0
                          : ($x - $a) / ($b - $a)),

            // Segitiga / Trapesium: naik dari a ke b, puncak b–c, turun dari c ke d
            // Naskah: Sedang (a=2, b=4, c=4, d=6), Lama (a=2, b=6, c=6, d=10)
            'segitiga', 'trapesium'
                => ($x <= $a || $x >= $d) ? 0.0
                 : (($x >= $b && $x <= $c) ? 1.0
                 : (($x < $b) ? ($x - $a) / ($b - $a)
                              : ($d - $x) / ($d - $c))),

            default => 0.0,
        };

        Log::debug("    [hitungMu:{$sub->tipe_kurva}] sub='{$sub->nama_sub_kriteria}'"
            . " | x={$x} | a={$a} b={$b} c={$c} d={$d} | μ=" . round($mu, 4));

        return $mu;
    }

    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================

    /**
     * Cari μ dari lookup berdasarkan label rule (partial match, case-insensitive).
     * Contoh: label rule "sangat lama" cocok dengan key "sangat lama" di lookup.
     */
    private static function cariMuDariLookup(array $lookup, string $labelRule): float
    {
        $labelLower = strtolower(trim($labelRule));

        // Exact match dulu
        if (isset($lookup[$labelLower])) {
            return $lookup[$labelLower];
        }

        // Partial match: cari key yang mengandung label rule atau sebaliknya
        foreach ($lookup as $key => $mu) {
            if (str_contains($key, $labelLower) || str_contains($labelLower, $key)) {
                return $mu;
            }
        }

        return 0.0;
    }

    /**
     * Cocokkan label sub-kriteria dengan input (untuk tipe diskrit).
     * Mendukung variasi penulisan: "sewa/garap", "sewa garap", "sewa", "garap".
     */
    private static function labelCocok(string $labelSub, string $input): bool
    {
        $sub   = strtolower(trim($labelSub));
        $inp   = strtolower(trim($input));

        if ($sub === $inp) return true;
        if (str_contains($inp, $sub) || str_contains($sub, $inp)) return true;

        // Alias khusus untuk C2
        $aliasMap = [
            'sewa'           => ['sewa', 'garap', 'sewa/garap', 'sewa garap'],
            'sewa/garap'     => ['sewa', 'garap', 'sewa/garap', 'sewa garap'],
            'sewa garap'     => ['sewa', 'garap', 'sewa/garap', 'sewa garap'],
            'tidak memiliki' => ['tidak memiliki', 'tidak punya', 'tidak punya lahan'],
            'milik'          => ['milik', 'milik sendiri', 'milik sendiri bersertifikat'],
        ];

        foreach ($aliasMap as $canonical => $variants) {
            if (in_array($sub, $variants) && in_array($inp, $variants)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Skala Prioritas sesuai Tabel 3.26 naskah:
     * 0.81–1.00 → Prioritas I   (Sangat Diprioritaskan)
     * 0.61–0.80 → Prioritas II  (Diprioritaskan)
     * 0.41–0.60 → Prioritas III (Dipertimbangkan)
     * ≤ 0.40    → Prioritas IV  (Tidak Diprioritaskan)
     */
    private static function getSkalaPrioritas(float $z): array
    {
        return match (true) {
            $z >= 0.81 => [
                'prioritas'    => 'Prioritas I',
                'status'       => 'Sangat Diprioritaskan',
                'interpretasi' => 'Sangat Diprioritaskan',
            ],
            $z >= 0.61 => [
                'prioritas'    => 'Prioritas II',
                'status'       => 'Diprioritaskan',
                'interpretasi' => 'Diprioritaskan',
            ],
            $z >= 0.41 => [
                'prioritas'    => 'Prioritas III',
                'status'       => 'Dipertimbangkan',
                'interpretasi' => 'Dipertimbangkan',
            ],
            default => [
                'prioritas'    => 'Prioritas IV',
                'status'       => 'Tidak Diprioritaskan',
                'interpretasi' => 'Tidak Diprioritaskan',
            ],
        };
    }

    // =========================================================================
    // PUBLIC: Hitung semua CPCL terverifikasi + ranking
    // =========================================================================
    public static function hitungSemuaDanRanking(?string $periode = null, ?string $bidang = null): Collection
    {
        $query = Cpcl::where('status', 'terverifikasi');
        if ($periode) $query->whereYear('created_at', $periode);
        if ($bidang)  $query->where('bidang', $bidang);

        $cpclList  = $query->get();
        $total     = $cpclList->count();
        $hasilList = [];

        Log::info("[RANKING] Memproses {$total} CPCL terverifikasi"
            . ($periode ? " | periode={$periode}" : "")
            . ($bidang  ? " | bidang={$bidang}"   : ""));

        foreach ($cpclList as $idx => $cpcl) {
            $no = $idx + 1;
            Log::info("[RANKING] ({$no}/{$total}) Menghitung CPCL ID: {$cpcl->id} – {$cpcl->nama_kelompok}");
            try {
                $hasil       = self::hitung($cpcl->id);
                $hasilList[] = array_merge(['cpcl_id' => $cpcl->id], $hasil);
                Log::info("[RANKING] ({$no}/{$total}) Selesai → Z={$hasil['z']} | Skor={$hasil['skor_akhir']}%");
            } catch (\Exception $e) {
                Log::error("[RANKING] ({$no}/{$total}) SKIP CPCL ID {$cpcl->id}: " . $e->getMessage());
            }
        }

        // Urutkan descending berdasarkan skor akhir (Z × 100)
        $ranked = collect($hasilList)->sortByDesc('skor_akhir')->values();

        Log::info("[RANKING] Urutan final:");
        foreach ($ranked as $rank => $item) {
            $nama = $item['cpcl']->nama_kelompok ?? $item['cpcl_id'];
            Log::info("  Peringkat " . ($rank + 1) . " → {$nama} | Z={$item['z']} | {$item['skala_prioritas']}");
        }

        Log::info("[RANKING] Menyimpan semua hasil ke DB …");
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
        Log::info("[RANKING] Semua hasil tersimpan.");

        return $ranked;
    }

    // =========================================================================
    // PUBLIC: Validasi kelengkapan data sebelum dihitung
    // =========================================================================
    public static function cekSinkronisasiData(int $cpcl_id): array
    {
        $cpcl         = Cpcl::findOrFail($cpcl_id);
        $kriteriaList = Kriteria::with('subKriteria')->get();
        $errors       = [];

        Log::debug("[VALIDASI] Memeriksa " . $kriteriaList->count() . " kriteria untuk CPCL ID: {$cpcl_id}");

        foreach ($kriteriaList as $kriteria) {
            $field = $kriteria->mapping_field;

            if ($kriteria->jenis_kriteria === 'kontinu') {
                $nilai = $cpcl->{$field} ?? null;
                $clean = preg_replace('/[^0-9.]/', '', (string) $nilai);
                if (is_null($nilai) || $nilai === '') {
                    $errors[] = "Kriteria {$kriteria->kode_kriteria}: nilai kosong.";
                    Log::debug("  [VALIDASI] {$kriteria->kode_kriteria} (kontinu) → ✘ KOSONG | field={$field}");
                } elseif (!is_numeric($clean)) {
                    $errors[] = "Kriteria {$kriteria->kode_kriteria}: bukan angka.";
                    Log::debug("  [VALIDASI] {$kriteria->kode_kriteria} (kontinu) → ✘ BUKAN ANGKA | nilai='{$nilai}'");
                } else {
                    Log::debug("  [VALIDASI] {$kriteria->kode_kriteria} (kontinu) → ✔ OK | nilai={$nilai}");
                }
            } else {
                $nilai = DB::table('cpcl_penilaian')
                    ->where('cpcl_id', $cpcl_id)
                    ->where('kriteria_id', $kriteria->id)
                    ->value('nilai');

                if (is_null($nilai) || $nilai === '') {
                    $errors[] = "Kriteria {$kriteria->kode_kriteria}: belum diisi.";
                    Log::debug("  [VALIDASI] {$kriteria->kode_kriteria} (diskrit) → ✘ BELUM DIISI");
                } else {
                    Log::debug("  [VALIDASI] {$kriteria->kode_kriteria} (diskrit) → ✔ OK | nilai='{$nilai}'");
                }
            }
        }

        $valid = empty($errors);
        Log::debug("[VALIDASI] Hasil: " . ($valid ? "VALID ✔" : "TIDAK VALID ✘ (" . count($errors) . " error)"));

        return [
            'is_valid' => $valid,
            'messages' => $errors,
        ];
    }
}

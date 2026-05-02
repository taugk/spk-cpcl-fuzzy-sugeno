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
 *    Setiap rule memiliki flag 'aktif': 1 = diproses, 0 = dilewati.
 *    Operator AND → MIN atas μ kelima kriteria.
 *    Hanya rule dengan flag aktif=1 yang digunakan untuk inferensi.
 *    Rule dengan flag aktif=0 TIDAK diikutsertakan dalam perhitungan apapun.
 *
 *    Penanganan kasus di luar rule:
 *    Jika tidak ada rule aktif yang cocok (Σα = 0), sistem menggunakan
 *    FALLBACK berdasarkan rata-rata μ tertinggi tiap kriteria.
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
    // Setiap rule: [C1, C2, C3, C4, C5, k_output, aktif]
    //
    // FLAG 'aktif':
    //   1 = Rule AKTIF  → ikut proses inferensi
    //   0 = Rule NONAKTIF → dilewati (skip), tidak dihitung
    //
    // Untuk menonaktifkan rule tertentu, ubah nilai 'aktif' dari 1 ke 0.
    // =========================================================================
    private const RULES = [
    // =========================================================================
    // RULE BASE NASKAH (R1–R16) - SEMUA SUDAH LOWERCASE
    // =========================================================================
    'R1'  => ['c1' => 'sempit',  'c2' => 'bagi hasil',    'c3' => 'baru',        'c4' => 'rendah', 'c5' => 'tidak lengkap',  'k' => 0.25, 'aktif' => 1],
    'R2'  => ['c1' => 'sempit',  'c2' => 'sewa',          'c3' => 'baru',        'c4' => 'rendah', 'c5' => 'tidak lengkap',  'k' => 0.25, 'aktif' => 1],
    'R3'  => ['c1' => 'sempit',  'c2' => 'milik sendiri', 'c3' => 'lama',        'c4' => 'sedang', 'c5' => 'lengkap',        'k' => 0.50, 'aktif' => 1],
    'R4'  => ['c1' => 'sedang',  'c2' => 'sewa',          'c3' => 'lama',        'c4' => 'sedang', 'c5' => 'lengkap',        'k' => 0.25, 'aktif' => 1],
    'R5'  => ['c1' => 'sedang',  'c2' => 'milik sendiri', 'c3' => 'lama',        'c4' => 'tinggi', 'c5' => 'sangat lengkap', 'k' => 0.75, 'aktif' => 1],
    'R6'  => ['c1' => 'luas',    'c2' => 'milik sendiri', 'c3' => 'sangat lama', 'c4' => 'tinggi', 'c5' => 'sangat lengkap', 'k' => 0.75, 'aktif' => 1],
    'R7'  => ['c1' => 'luas',    'c2' => 'sewa',          'c3' => 'lama',        'c4' => 'tinggi', 'c5' => 'lengkap',        'k' => 0.75, 'aktif' => 1],
    'R8'  => ['c1' => 'luas',    'c2' => 'bagi hasil',    'c3' => 'baru',        'c4' => 'rendah', 'c5' => 'tidak lengkap',  'k' => 0.25, 'aktif' => 1],
    'R9'  => ['c1' => 'sedang',  'c2' => 'bagi hasil',    'c3' => 'baru',        'c4' => 'rendah', 'c5' => 'tidak lengkap',  'k' => 0.25, 'aktif' => 1],
    'R10' => ['c1' => 'sedang',  'c2' => 'milik sendiri', 'c3' => 'lama',        'c4' => 'tinggi', 'c5' => 'lengkap',        'k' => 0.75, 'aktif' => 1],
    'R11' => ['c1' => 'sedang',  'c2' => 'milik sendiri', 'c3' => 'baru',        'c4' => 'sedang', 'c5' => 'lengkap',        'k' => 0.50, 'aktif' => 1],
    'R12' => ['c1' => 'luas',    'c2' => 'sewa',          'c3' => 'baru',        'c4' => 'sedang', 'c5' => 'lengkap',        'k' => 0.50, 'aktif' => 1],
    'R13' => ['c1' => 'sempit',  'c2' => 'milik sendiri', 'c3' => 'baru',        'c4' => 'sedang', 'c5' => 'lengkap',        'k' => 0.50, 'aktif' => 1],
    'R14' => ['c1' => 'luas',    'c2' => 'milik sendiri', 'c3' => 'lama',        'c4' => 'sedang', 'c5' => 'sangat lengkap', 'k' => 0.75, 'aktif' => 1],
    'R15' => ['c1' => 'sedang',  'c2' => 'sewa',          'c3' => 'lama',        'c4' => 'rendah', 'c5' => 'tidak lengkap',  'k' => 0.25, 'aktif' => 1],
    'R16' => ['c1' => 'luas',    'c2' => 'milik sendiri', 'c3' => 'lama',        'c4' => 'tinggi', 'c5' => 'sangat lengkap', 'k' => 1.00, 'aktif' => 1],

    // =========================================================================
    // RULE TAMBAHAN UNTUK C5 = 'SANGAT LENGKAP' (Prioritas Tertinggi)
    // =========================================================================
    
    // C5 = Sangat Lengkap + C1 = sempit
    'R17' => ['c1' => 'sempit',  'c2' => 'milik sendiri', 'c3' => 'baru',   'c4' => 'sedang', 'c5' => 'sangat lengkap', 'k' => 0.50, 'aktif' => 1],
    'R18' => ['c1' => 'sempit',  'c2' => 'milik sendiri', 'c3' => 'baru',   'c4' => 'tinggi', 'c5' => 'sangat lengkap', 'k' => 0.75, 'aktif' => 1],
    'R19' => ['c1' => 'sempit',  'c2' => 'milik sendiri', 'c3' => 'sedang', 'c4' => 'sedang', 'c5' => 'sangat lengkap', 'k' => 0.75, 'aktif' => 1],
    'R20' => ['c1' => 'sempit',  'c2' => 'milik sendiri', 'c3' => 'sedang', 'c4' => 'tinggi', 'c5' => 'sangat lengkap', 'k' => 0.75, 'aktif' => 1],
    'R21' => ['c1' => 'sempit',  'c2' => 'milik sendiri', 'c3' => 'lama',   'c4' => 'sedang', 'c5' => 'sangat lengkap', 'k' => 0.75, 'aktif' => 1],
    'R22' => ['c1' => 'sempit',  'c2' => 'milik sendiri', 'c3' => 'lama',   'c4' => 'tinggi', 'c5' => 'sangat lengkap', 'k' => 0.75, 'aktif' => 1],
    'R23' => ['c1' => 'sempit',  'c2' => 'milik sendiri', 'c3' => 'sangat lama', 'c4' => 'sedang', 'c5' => 'sangat lengkap', 'k' => 0.75, 'aktif' => 1],
    'R24' => ['c1' => 'sempit',  'c2' => 'milik sendiri', 'c3' => 'sangat lama', 'c4' => 'tinggi', 'c5' => 'sangat lengkap', 'k' => 1.00, 'aktif' => 1],
    
    // C5 = Sangat Lengkap + C1 = sedang
    'R25' => ['c1' => 'sedang',  'c2' => 'milik sendiri', 'c3' => 'baru',   'c4' => 'sedang', 'c5' => 'sangat lengkap', 'k' => 0.50, 'aktif' => 1],
    'R26' => ['c1' => 'sedang',  'c2' => 'milik sendiri', 'c3' => 'baru',   'c4' => 'tinggi', 'c5' => 'sangat lengkap', 'k' => 0.75, 'aktif' => 1],
    'R27' => ['c1' => 'sedang',  'c2' => 'milik sendiri', 'c3' => 'sedang', 'c4' => 'sedang', 'c5' => 'sangat lengkap', 'k' => 0.75, 'aktif' => 1],
    'R28' => ['c1' => 'sedang',  'c2' => 'milik sendiri', 'c3' => 'sedang', 'c4' => 'tinggi', 'c5' => 'sangat lengkap', 'k' => 0.75, 'aktif' => 1],
    'R29' => ['c1' => 'sedang',  'c2' => 'milik sendiri', 'c3' => 'lama',   'c4' => 'sedang', 'c5' => 'sangat lengkap', 'k' => 0.75, 'aktif' => 1],
    'R30' => ['c1' => 'sedang',  'c2' => 'milik sendiri', 'c3' => 'lama',   'c4' => 'tinggi', 'c5' => 'sangat lengkap', 'k' => 0.75, 'aktif' => 1],
    'R31' => ['c1' => 'sedang',  'c2' => 'milik sendiri', 'c3' => 'sangat lama', 'c4' => 'sedang', 'c5' => 'sangat lengkap', 'k' => 0.75, 'aktif' => 1],
    'R32' => ['c1' => 'sedang',  'c2' => 'milik sendiri', 'c3' => 'sangat lama', 'c4' => 'tinggi', 'c5' => 'sangat lengkap', 'k' => 1.00, 'aktif' => 1],
    
    // C5 = Sangat Lengkap + C1 = luas
    'R33' => ['c1' => 'luas',    'c2' => 'milik sendiri', 'c3' => 'baru',   'c4' => 'sedang', 'c5' => 'sangat lengkap', 'k' => 0.50, 'aktif' => 1],
    'R34' => ['c1' => 'luas',    'c2' => 'milik sendiri', 'c3' => 'baru',   'c4' => 'tinggi', 'c5' => 'sangat lengkap', 'k' => 0.75, 'aktif' => 1],
    'R35' => ['c1' => 'luas',    'c2' => 'milik sendiri', 'c3' => 'sedang', 'c4' => 'sedang', 'c5' => 'sangat lengkap', 'k' => 0.75, 'aktif' => 1],
    'R36' => ['c1' => 'luas',    'c2' => 'milik sendiri', 'c3' => 'sedang', 'c4' => 'tinggi', 'c5' => 'sangat lengkap', 'k' => 0.75, 'aktif' => 1],
    'R37' => ['c1' => 'luas',    'c2' => 'milik sendiri', 'c3' => 'lama',   'c4' => 'sedang', 'c5' => 'sangat lengkap', 'k' => 0.75, 'aktif' => 1],
    'R38' => ['c1' => 'luas',    'c2' => 'milik sendiri', 'c3' => 'lama',   'c4' => 'tinggi', 'c5' => 'sangat lengkap', 'k' => 1.00, 'aktif' => 1],
    'R39' => ['c1' => 'luas',    'c2' => 'milik sendiri', 'c3' => 'sangat lama', 'c4' => 'sedang', 'c5' => 'sangat lengkap', 'k' => 0.75, 'aktif' => 1],
    // R6 sudah ada untuk: luas + milik sendiri + sangat lama + tinggi + sangat lengkap (k=0.75)
    
    // C5 = Sangat Lengkap + C2 = sewa
    'R40' => ['c1' => 'sempit',  'c2' => 'sewa', 'c3' => 'sedang', 'c4' => 'tinggi', 'c5' => 'sangat lengkap', 'k' => 0.75, 'aktif' => 1],
    'R41' => ['c1' => 'sempit',  'c2' => 'sewa', 'c3' => 'lama',   'c4' => 'tinggi', 'c5' => 'sangat lengkap', 'k' => 0.75, 'aktif' => 1],
    'R42' => ['c1' => 'sedang',  'c2' => 'sewa', 'c3' => 'lama',   'c4' => 'tinggi', 'c5' => 'sangat lengkap', 'k' => 0.75, 'aktif' => 1],
    'R43' => ['c1' => 'luas',    'c2' => 'sewa', 'c3' => 'lama',   'c4' => 'tinggi', 'c5' => 'sangat lengkap', 'k' => 0.75, 'aktif' => 1],
    
    // C5 = Sangat Lengkap + C2 = bagi hasil
    'R44' => ['c1' => 'sempit',  'c2' => 'bagi hasil', 'c3' => 'lama',   'c4' => 'tinggi', 'c5' => 'sangat lengkap', 'k' => 0.50, 'aktif' => 1],
    'R45' => ['c1' => 'sedang',  'c2' => 'bagi hasil', 'c3' => 'lama',   'c4' => 'tinggi', 'c5' => 'sangat lengkap', 'k' => 0.50, 'aktif' => 1],

    // =========================================================================
    // RULE TAMBAHAN UNTUK C5 = 'LENGKAP'
    // =========================================================================
    
    // C5 = Lengkap + C1 = sempit
    'R46' => ['c1' => 'sempit',  'c2' => 'milik sendiri', 'c3' => 'baru',   'c4' => 'tinggi', 'c5' => 'lengkap', 'k' => 0.75, 'aktif' => 1],
    'R47' => ['c1' => 'sempit',  'c2' => 'milik sendiri', 'c3' => 'sedang', 'c4' => 'tinggi', 'c5' => 'lengkap', 'k' => 0.75, 'aktif' => 1],
    'R48' => ['c1' => 'sempit',  'c2' => 'milik sendiri', 'c3' => 'sangat lama', 'c4' => 'tinggi', 'c5' => 'lengkap', 'k' => 0.75, 'aktif' => 1],
    'R49' => ['c1' => 'sempit',  'c2' => 'milik sendiri', 'c3' => 'sedang', 'c4' => 'sedang', 'c5' => 'lengkap', 'k' => 0.50, 'aktif' => 1],
    'R50' => ['c1' => 'sempit',  'c2' => 'milik sendiri', 'c3' => 'lama',   'c4' => 'sedang', 'c5' => 'lengkap', 'k' => 0.50, 'aktif' => 1],
    
    // C5 = Lengkap + C1 = sedang
    'R51' => ['c1' => 'sedang',  'c2' => 'milik sendiri', 'c3' => 'baru',   'c4' => 'tinggi', 'c5' => 'lengkap', 'k' => 0.75, 'aktif' => 1],
    'R52' => ['c1' => 'sedang',  'c2' => 'milik sendiri', 'c3' => 'sedang', 'c4' => 'tinggi', 'c5' => 'lengkap', 'k' => 0.75, 'aktif' => 1],
    'R53' => ['c1' => 'sedang',  'c2' => 'milik sendiri', 'c3' => 'sangat lama', 'c4' => 'tinggi', 'c5' => 'lengkap', 'k' => 0.75, 'aktif' => 1],
    'R54' => ['c1' => 'sedang',  'c2' => 'milik sendiri', 'c3' => 'lama',   'c4' => 'sedang', 'c5' => 'lengkap', 'k' => 0.50, 'aktif' => 1],
    
    // C5 = Lengkap + C1 = luas
    'R55' => ['c1' => 'luas',    'c2' => 'milik sendiri', 'c3' => 'baru',   'c4' => 'tinggi', 'c5' => 'lengkap', 'k' => 0.75, 'aktif' => 1],
    'R56' => ['c1' => 'luas',    'c2' => 'milik sendiri', 'c3' => 'sedang', 'c4' => 'tinggi', 'c5' => 'lengkap', 'k' => 0.75, 'aktif' => 1],
    'R57' => ['c1' => 'luas',    'c2' => 'milik sendiri', 'c3' => 'sangat lama', 'c4' => 'tinggi', 'c5' => 'lengkap', 'k' => 0.75, 'aktif' => 1],
    
    // C5 = Lengkap + C2 = sewa
    'R58' => ['c1' => 'sempit',  'c2' => 'sewa', 'c3' => 'sedang', 'c4' => 'tinggi', 'c5' => 'lengkap', 'k' => 0.50, 'aktif' => 1],
    'R59' => ['c1' => 'sedang',  'c2' => 'sewa', 'c3' => 'lama',   'c4' => 'tinggi', 'c5' => 'lengkap', 'k' => 0.50, 'aktif' => 1],
    'R60' => ['c1' => 'luas',    'c2' => 'sewa', 'c3' => 'lama',   'c4' => 'tinggi', 'c5' => 'lengkap', 'k' => 0.75, 'aktif' => 1],
    
    // C5 = Lengkap + C2 = bagi hasil
    'R61' => ['c1' => 'sempit',  'c2' => 'bagi hasil', 'c3' => 'lama', 'c4' => 'tinggi', 'c5' => 'lengkap', 'k' => 0.50, 'aktif' => 1],
    'R62' => ['c1' => 'sedang',  'c2' => 'bagi hasil', 'c3' => 'lama', 'c4' => 'tinggi', 'c5' => 'lengkap', 'k' => 0.50, 'aktif' => 1],

    // =========================================================================
    // RULE TAMBAHAN UNTUK C5 = 'TIDAK LENGKAP'
    // =========================================================================
    
    // C5 = Tidak Lengkap + C1 = sempit
    'R63' => ['c1' => 'sempit',  'c2' => 'milik sendiri', 'c3' => 'sedang', 'c4' => 'tinggi', 'c5' => 'tidak lengkap', 'k' => 0.50, 'aktif' => 1],
    'R64' => ['c1' => 'sempit',  'c2' => 'milik sendiri', 'c3' => 'lama',   'c4' => 'tinggi', 'c5' => 'tidak lengkap', 'k' => 0.50, 'aktif' => 1],
    'R65' => ['c1' => 'sempit',  'c2' => 'milik sendiri', 'c3' => 'baru',   'c4' => 'tinggi', 'c5' => 'tidak lengkap', 'k' => 0.50, 'aktif' => 1],
    'R66' => ['c1' => 'sempit',  'c2' => 'milik sendiri', 'c3' => 'sedang', 'c4' => 'sedang', 'c5' => 'tidak lengkap', 'k' => 0.25, 'aktif' => 1],
    'R67' => ['c1' => 'sempit',  'c2' => 'milik sendiri', 'c3' => 'lama',   'c4' => 'sedang', 'c5' => 'tidak lengkap', 'k' => 0.25, 'aktif' => 1],
    
    // C5 = Tidak Lengkap + C1 = sedang
    'R68' => ['c1' => 'sedang',  'c2' => 'milik sendiri', 'c3' => 'lama',   'c4' => 'tinggi', 'c5' => 'tidak lengkap', 'k' => 0.50, 'aktif' => 1],
    'R69' => ['c1' => 'sedang',  'c2' => 'milik sendiri', 'c3' => 'sedang', 'c4' => 'tinggi', 'c5' => 'tidak lengkap', 'k' => 0.50, 'aktif' => 1],
    
    // C5 = Tidak Lengkap + C1 = luas
    'R70' => ['c1' => 'luas',    'c2' => 'milik sendiri', 'c3' => 'sedang', 'c4' => 'tinggi', 'c5' => 'tidak lengkap', 'k' => 0.50, 'aktif' => 1],
    'R71' => ['c1' => 'luas',    'c2' => 'milik sendiri', 'c3' => 'lama',   'c4' => 'tinggi', 'c5' => 'tidak lengkap', 'k' => 0.50, 'aktif' => 1],
    
    // C5 = Tidak Lengkap + C2 = sewa
    'R72' => ['c1' => 'sempit',  'c2' => 'sewa', 'c3' => 'sedang', 'c4' => 'tinggi', 'c5' => 'tidak lengkap', 'k' => 0.25, 'aktif' => 1],
    'R73' => ['c1' => 'sedang',  'c2' => 'sewa', 'c3' => 'lama',   'c4' => 'tinggi', 'c5' => 'tidak lengkap', 'k' => 0.25, 'aktif' => 1],
    
    // C5 = Tidak Lengkap + C2 = bagi hasil (sudah ada di R1,R2,R8,R9,R15)
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
            Log::info("[DB] Tersimpan → Z={$hasil['z']} | Skor={$hasil['skor_akhir']}% | {$hasil['skala_prioritas']} ({$hasil['status_kelayakan']})"
                . ($hasil['is_fallback'] ? " [FALLBACK]" : ""));

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
        // =====================================================================
        Log::info("── [LANGKAH 1] FUZZIFIKASI ──────────────────────────────────");

        $fuzzifikasi = [];

        foreach ($kriteriaList as $kriteria) {
            $field = $kriteria->mapping_field;
            $kode  = strtolower($kriteria->kode_kriteria);

            if ($kriteria->jenis_kriteria === 'kontinu') {
                $inputVal = $cpcl->{$field} ?? '0';
                Log::debug("  [{$kriteria->kode_kriteria}] Jenis: kontinu | Field: {$field} | Nilai raw: {$inputVal}");
            } else {
                // Khusus diskrit: HANYA dari cpcl_penilaian, tidak fallback ke field CPCL
                $inputVal = DB::table('cpcl_penilaian')
                    ->where('cpcl_id', $cpcl_id)
                    ->where('kriteria_id', $kriteria->id)
                    ->value('nilai') ?? '-';
                Log::debug("  [{$kriteria->kode_kriteria}] Jenis: diskrit  | Field: cpcl_penilaian | Nilai raw: {$inputVal}");
            }

            $inputString   = (string) $inputVal;
            $himpunanAktif = [];
            $himpunanSkip  = [];

            foreach ($kriteria->subKriteria as $sub) {
                $mu = self::hitungMu($sub, $inputString);

                $entry = [
                    'nama'   => $sub->nama_sub_kriteria,
                    'mu'     => round($mu, 4),
                    'tipe'   => $sub->tipe_kurva,
                    'params' => [
                        'tipe' => $sub->tipe_kurva,
                        'a'    => (float) ($sub->batas_bawah    ?? 0),
                        'b'    => (float) ($sub->batas_tengah_1 ?? 0),
                        'c'    => (float) ($sub->batas_tengah_2 ?? 0),
                        'd'    => (float) ($sub->batas_atas     ?? 0),
                    ],
                ];

                if ($mu > 0) {
                    $himpunanAktif[] = $entry;
                    Log::debug("    ✔ Himpunan [{$sub->nama_sub_kriteria}] μ = " . round($mu, 4)
                        . " | tipe_kurva: {$sub->tipe_kurva}");
                } else {
                    $himpunanSkip[] = $entry;
                }
            }

            $semuaHimpunan = array_merge($himpunanAktif, $himpunanSkip);
            $skipNama      = array_column($himpunanSkip, 'nama');

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
                'himpunan'       => $himpunanAktif,
                'semua_himpunan' => $semuaHimpunan,
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
        //
        // KETENTUAN:
        //   HANYA rule dengan FLAG 'aktif' = 1 yang digunakan untuk evaluasi
        //   Rule dengan FLAG 'aktif' = 0 TIDAK diikutsertakan dalam perhitungan apapun
        //   
        //   Untuk rule aktif (=1):
        //     - Jika anteceden cocok (semua μ > 0) → hitung α dan kontribusi ke Z
        //     - Jika anteceden tidak cocok (ada μ = 0) → rule tidak berkontribusi
        // =====================================================================
        Log::info("── [LANGKAH 2] INFERENSI RULE BASE ─────────────────────────");
        Log::info("  ✨ HANYA rule dengan FLAG AKTIF=1 yang akan dievaluasi ✨");

        $rules         = [];      // hasil inferensi untuk dikembalikan ke view
        $totalAlphaZ   = 0.0;
        $totalAlpha    = 0.0;
        $maxAlpha      = 0.0;

        // Counter untuk pelaporan
        $countAktif    = 0;       // jumlah rule dengan flag aktif=1
        $countAktifCocok    = 0;  // aktif=1 DAN α > 0
        $countAktifTakCocok = 0;  // aktif=1 tetapi α = 0
        $countNonaktif      = 0;  // aktif=0, langsung skip

        $totalRules = count(self::RULES);

        foreach (self::RULES as $ruleId => $rule) {

            // ── CEK FLAG AKTIF ────────────────────────────────────────────────
            // HANYA rule dengan flag aktif = 1 yang diproses
            if (($rule['aktif'] ?? 1) !== 1) {
                Log::debug("  [{$ruleId}] FLAG=0 → NONAKTIF, TIDAK DIEVALUASI (langsung dilewati).");
                $countNonaktif++;

                // Catat di output agar view bisa menampilkan status rule
                $rules[] = [
                    'rule_id'     => $ruleId,
                    'flag_aktif'  => 0,
                    'anteceden'   => [],
                    'alpha'       => 0.0,
                    'k'           => $rule['k'],
                    'alpha_x_k'   => 0.0,
                    'status'      => 'nonaktif',
                    'keterangan'  => 'Rule TIDAK AKTIF (flag=0) → tidak diikutsertakan dalam evaluasi',
                ];
                continue; // LANGSUNG SKIP, tidak diproses lebih lanjut
            }

            // ── Rule AKTIF (flag=1): evaluasi anteceden ───────────────────────
            $countAktif++;
            $muPerKriteria  = [];
            $matchGagal     = false;
            $debugAnteceden = [];

            foreach (['c1', 'c2', 'c3', 'c4', 'c5'] as $kode) {
                $labelRule = $rule[$kode];
                $mu        = self::cariMuDariLookup($muLookup[$kode] ?? [], $labelRule);

                $debugAnteceden[] = "{$kode}='{$labelRule}'(μ=" . round($mu, 4) . ")";

                if ($mu <= 0) {
                    $matchGagal = true;
                }
                $muPerKriteria[$kode] = $mu;
            }

            if ($matchGagal) {
                // Rule aktif tapi anteceden tidak cocok → α = 0, tidak berkontribusi
                Log::debug("  [{$ruleId}] FLAG=1 (AKTIF) | ✘ TIDAK COCOK – " . implode(', ', $debugAnteceden));
                $countAktifTakCocok++;

                $rules[] = [
                    'rule_id'    => $ruleId,
                    'flag_aktif' => 1,
                    'anteceden'  => array_map(
                        fn($kode) => [
                            'kriteria' => $fuzzifikasi[$kode]['nama'] ?? $kode,
                            'himpunan' => $rule[$kode],
                            'mu'       => round($muPerKriteria[$kode], 4),
                        ],
                        ['c1', 'c2', 'c3', 'c4', 'c5']
                    ),
                    'alpha'      => 0.0,
                    'k'          => $rule['k'],
                    'alpha_x_k'  => 0.0,
                    'status'     => 'tidak_cocok',
                    'keterangan' => 'Rule AKTIF tetapi kondisi tidak terpenuhi (α=0) → tidak berkontribusi',
                ];
                continue;
            }

            // ── Rule AKTIF DAN COCOK → hitung firing strength ─────────────────
            $alpha   = min($muPerKriteria);          // Operator AND = MIN
            $k       = $rule['k'];
            $alphaXk = round($alpha * $k, 4);

            Log::info("  [{$ruleId}] FLAG=1 (AKTIF) | ✔ COCOK – " . implode(', ', $debugAnteceden));
            Log::info("           α = min(" . implode(', ', array_map(fn($v) => round($v, 4), $muPerKriteria)) . ") = " . round($alpha, 4));
            Log::info("           k = {$k}  |  α×k = {$alphaXk}");

            $rules[] = [
                'rule_id'    => $ruleId,
                'flag_aktif' => 1,
                'anteceden'  => array_map(
                    fn($kode) => [
                        'kriteria' => $fuzzifikasi[$kode]['nama'] ?? $kode,
                        'himpunan' => $rule[$kode],
                        'mu'       => round($muPerKriteria[$kode], 4),
                    ],
                    ['c1', 'c2', 'c3', 'c4', 'c5']
                ),
                'alpha'      => round($alpha, 4),
                'k'          => $k,
                'alpha_x_k'  => $alphaXk,
                'status'     => 'cocok',
                'keterangan' => 'Rule AKTIF dan kondisi terpenuhi → berkontribusi ke Z',
            ];

            $totalAlphaZ += ($alpha * $k);
            $totalAlpha  += $alpha;
            $countAktifCocok++;

            if ($alpha > $maxAlpha) {
                $maxAlpha = $alpha;
            }
        }

        Log::info(sprintf(
            "  Ringkasan rule: %d total | %d AKTIF (dari %d aktif, %d cocok, %d tidak cocok) | %d NONAKTIF (tidak dievaluasi)",
            $totalRules, $countAktif, $countAktif, $countAktifCocok, $countAktifTakCocok, $countNonaktif
        ));
        Log::info("  Σ(α×k) = " . round($totalAlphaZ, 4) . "  |  Σα = " . round($totalAlpha, 4));

        // =====================================================================
        // LANGKAH 3 – DEFUZZIFIKASI (Weighted Average)
        // Z = Σ(αᵢ × kᵢ) / Σ(αᵢ)
        //
        // ── PENANGANAN KASUS TIDAK ADA RULE AKTIF YANG COCOK ───────────────────
        //
        // Jika Σα = 0 (tidak ada satu pun rule aktif yang terpenuhi), sistem
        // tidak bisa menggunakan rumus weighted average karena pembagi = 0.
        //
        // Strategi FALLBACK yang digunakan:
        //   Ambil μ tertinggi dari setiap kriteria, rata-ratakan sebagai Z.
        //   Ini merupakan estimasi "seberapa jauh" input dari rule manapun,
        //   bukan inferensi penuh — sehingga hasilnya ditandai is_fallback=true
        //   agar operator tahu output ini perlu review manual.
        // =====================================================================
        Log::info("── [LANGKAH 3] DEFUZZIFIKASI (Weighted Average) ────────────");

        $isFallback  = false;
        $fallbackNote = null;

        if ($totalAlpha <= 0) {
            Log::warning("  ⚠ Σα = 0 — tidak ada rule AKTIF yang cocok.");
            Log::warning("  ⚠ Kombinasi input tidak ter-cover oleh rule aktif manapun.");
            Log::warning("  ⚠ Menggunakan FALLBACK: rata-rata μ maksimum per kriteria.");

            // Kumpulkan μ tertinggi tiap kriteria — SEMUA 5 kriteria (c1–c5) wajib dihitung
            $semuaKode        = ['c1', 'c2', 'c3', 'c4', 'c5'];
            $muMaxPerKriteria = [];
            foreach ($semuaKode as $kode) {
                $himpunanMap              = $muLookup[$kode] ?? [];
                $muMaxPerKriteria[$kode]  = !empty($himpunanMap) ? max($himpunanMap) : 0.0;
                $sumber = empty($himpunanMap) ? 'TIDAK ADA DATA → 0' : round($muMaxPerKriteria[$kode], 4);
                Log::debug("    [FALLBACK] {$kode} → μ_max = {$sumber}");
            }

            // Z fallback = rata-rata μ max dari 5 kriteria (pembagi selalu 5)
            $z_final = array_sum($muMaxPerKriteria) / count($semuaKode);

            $isFallback   = true;
            $fallbackNote = sprintf(
                'Tidak ada rule AKTIF yang cocok dengan input. '
                . 'Z dihitung dari rata-rata μ maksimum per kriteria: '
                . implode(', ', array_map(
                    fn($k, $v) => strtoupper($k) . '=' . round($v, 4),
                    array_keys($muMaxPerKriteria),
                    $muMaxPerKriteria
                ))
                . '. Nilai ini bersifat estimasi dan perlu review manual.'
            );

            Log::warning("  ⚠ Z (fallback) = " . round($z_final, 4));

        } else {
            $z_final = $totalAlphaZ / $totalAlpha;
            Log::info("  Z = " . round($totalAlphaZ, 4) . " / " . round($totalAlpha, 4) . " = " . round($z_final, 4));
        }

        $skala = self::getSkalaPrioritas($z_final);

        Log::info("  Skor Akhir   : " . round($z_final * 100, 2) . "%");
        Log::info("  Skala        : {$skala['prioritas']}" . ($isFallback ? " [ESTIMASI/FALLBACK]" : ""));
        Log::info("  Status       : {$skala['status']}");
        Log::info("  Interpretasi : {$skala['interpretasi']}");
        Log::info("─────────────────────────────────────────────────────────────");

        return [
            'cpcl'             => $cpcl,
            'fuzzifikasi'      => array_values($fuzzifikasi),
            'rules'            => $rules,

            // Statistik rule
            'rule_stats' => [
                'total'              => $totalRules,
                'aktif'              => $countAktif,
                'aktif_cocok'        => $countAktifCocok,
                'aktif_tidak_cocok'  => $countAktifTakCocok,
                'nonaktif'           => $countNonaktif,
            ],

            'sum_alpha_z'      => round($totalAlphaZ, 4),
            'sum_alpha'        => round($totalAlpha,  4),
            'alpha'            => round($maxAlpha,    4),
            'z'                => round($z_final,     4),
            'skor_akhir'       => round($z_final * 100, 2),
            'status_kelayakan' => $skala['status'],
            'skala_prioritas'  => $skala['prioritas'],
            'interpretasi'     => $skala['interpretasi'],

            // Flag & catatan kasus di luar rule
            'is_fallback'      => $isFallback,
            'fallback_note'    => $fallbackNote,
        ];
    }

    // =========================================================================
    // PRIVATE: Hitung derajat keanggotaan μ satu sub-kriteria
    // =========================================================================
    private static function hitungMu(object $sub, string $input): float
    {
        // ── Diskrit ──────────────────────────────────────────────────────────
        if ($sub->tipe_kurva === 'diskrit') {
            $cocok = self::labelCocok($sub->nama_sub_kriteria, $input);

            if (!$cocok) {
                Log::debug("    [hitungMu:diskrit] sub='{$sub->nama_sub_kriteria}' | input='{$input}' | cocok=TIDAK | μ=0");
                return 0.0;
            }

            // Nilai μ WAJIB ada di kolom nilai_konsekuen di tabel sub_kriteria.
            if (is_null($sub->nilai_konsekuen)) {
                Log::warning("    [hitungMu:diskrit] sub='{$sub->nama_sub_kriteria}' | input='{$input}'"
                    . " | cocok=YA tapi nilai_konsekuen NULL di DB → μ=0 (periksa tabel sub_kriteria!)");
                return 0.0;
            }

            $mu = (float) $sub->nilai_konsekuen;
            Log::debug("    [hitungMu:diskrit] sub='{$sub->nama_sub_kriteria}' | input='{$input}'"
                . " | cocok=YA | nilai_konsekuen={$mu} | μ={$mu}");
            return $mu;
        }

        // ── Kontinu: parse nilai input menjadi float ─────────────────────────
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

        // Validasi parameter range — log warning jika ada yang tidak masuk akal
        $rangeError = match ($sub->tipe_kurva) {
            'bahu_kiri'         => ($c <= 0 || $d <= 0 || $d <= $c) ? "bahu_kiri butuh c>0 dan d>c" : null,
            'bahu_kanan'        => ($a < 0 || $b <= 0 || $b <= $a)  ? "bahu_kanan butuh a≥0 dan b>a" : null,
            'trapesium','segitiga' => ($a >= $b || $b > $c || $c >= $d) ? "trapesium/segitiga butuh a<b≤c<d" : null,
            default             => null,
        };

        if ($rangeError) {
            Log::warning("    [hitungMu:{$sub->tipe_kurva}] sub='{$sub->nama_sub_kriteria}'"
                . " | PARAMETER RANGE TIDAK VALID: {$rangeError}"
                . " | a={$a} b={$b} c={$c} d={$d} → μ=0");
            return 0.0;
        }

        // ── Hitung μ berdasarkan posisi x dalam range ─────────────────────────
        $mu = match ($sub->tipe_kurva) {
            'bahu_kiri' => ($x <= $c) ? 1.0
                         : (($x >= $d) ? 0.0
                         : ($d - $x) / ($d - $c)),
            'bahu_kanan' => ($x <= $a) ? 0.0
                          : (($x >= $b) ? 1.0
                          : ($x - $a) / ($b - $a)),
            'segitiga', 'trapesium'
                => ($x <= $a || $x >= $d) ? 0.0
                 : (($x >= $b && $x <= $c) ? 1.0
                 : (($x < $b) ? ($x - $a) / ($b - $a)
                              : ($d - $x) / ($d - $c))),
            default => 0.0,
        };

        Log::debug("    [hitungMu:{$sub->tipe_kurva}] sub='{$sub->nama_sub_kriteria}'"
            . " | x={$x} | range=[a={$a}, b={$b}, c={$c}, d={$d}] | μ=" . round($mu, 4));

        return $mu;
    }

    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================

    private static function cariMuDariLookup(array $lookup, string $labelRule): float
    {
        $labelLower = strtolower(trim($labelRule));

        if (isset($lookup[$labelLower])) {
            return $lookup[$labelLower];
        }

        foreach ($lookup as $key => $mu) {
            if (str_contains($key, $labelLower) || str_contains($labelLower, $key)) {
                return $mu;
            }
        }

        return 0.0;
    }

    private static function labelCocok(string $labelSub, string $input): bool
{
    $sub = strtolower(trim($labelSub));
    $inp = strtolower(trim($input));

    // Exact Match
    if ($sub === $inp) {
        return true;
    }

    // Mapping Alias untuk variasi penulisan
    $aliasMap = [
        // Kepemilikan Lahan (C2)
        'sewa'           => ['sewa', 'garap', 'sewa/garap', 'sewa garap'],
        'milik sendiri'  => ['milik sendiri', 'milik sendiri', 'milik sendiri bersertifikat', 'milik'],
        'bagi hasil'     => ['bagi hasil', 'tidak punya', 'tidak punya lahan', 'bagihasil'],
        
        // Kelengkapan Dokumen (C5)
        'lengkap'        => ['lengkap', 'dokumen lengkap'],
        'tidak lengkap'  => ['tidak lengkap', 'dokumen tidak lengkap', 'tidaklengkap'],
        'sangat lengkap' => ['sangat lengkap', 'dokumen sangat lengkap', 'sangatlengkap'],
        
        // Luas Lahan (C1)
        'sempit'         => ['sempit'],
        'sedang'         => ['sedang'],
        'luas'           => ['luas'],
        
        // Lama Berdiri (C3)
        'baru'           => ['baru'],
        'sedang'         => ['sedang'],
        'lama'           => ['lama'],
        'sangat lama'    => ['sangat lama', 'sangatlama'],
        
        // Produktivitas (C4)
        'rendah'         => ['rendah'],
        'sedang'         => ['sedang'],
        'tinggi'         => ['tinggi'],
    ];

    foreach ($aliasMap as $canonical => $variants) {
        if (in_array($sub, $variants) && in_array($inp, $variants)) {
            return true;
        }
    }

    return false;
}

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
                $validasi = self::cekSinkronisasiData($cpcl->id);
                if (!$validasi['is_valid']) {
                    Log::warning("[RANKING] ({$no}/{$total}) SKIP – data tidak lengkap: "
                        . implode('; ', $validasi['messages']));
                    continue;
                }

                $hasil       = self::hitung($cpcl->id);
                $hasilList[] = array_merge(['cpcl_id' => $cpcl->id], $hasil);

                $flagFallback = $hasil['is_fallback'] ? " [FALLBACK/ESTIMASI]" : "";
                Log::info("[RANKING] ({$no}/{$total}) Selesai → Z={$hasil['z']} | Skor={$hasil['skor_akhir']}%{$flagFallback}");
            } catch (\Exception $e) {
                Log::error("[RANKING] ({$no}/{$total}) SKIP CPCL ID {$cpcl->id}: " . $e->getMessage());
            }
        }

        $ranked = collect($hasilList)->sortByDesc('skor_akhir')->values();

        Log::info("[RANKING] Urutan final:");
        foreach ($ranked as $rank => $item) {
            $nama = $item['cpcl']->nama_kelompok ?? $item['cpcl_id'];
            $flag = $item['is_fallback'] ? " [FALLBACK]" : "";
            Log::info("  Peringkat " . ($rank + 1) . " → {$nama} | Z={$item['z']} | {$item['skala_prioritas']}{$flag}");
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

    // =========================================================================
    // PUBLIC UTILITY: Mendapatkan ringkasan status rule (untuk debug/admin)
    // =========================================================================

    /**
     * Mengembalikan daftar semua rule beserta flag aktif/nonaktif-nya.
     * Berguna untuk halaman admin yang ingin menampilkan konfigurasi rule.
     */
    public static function getRuleList(): array
    {
        $list = [];
        foreach (self::RULES as $ruleId => $rule) {
            $list[] = [
                'rule_id'  => $ruleId,
                'aktif'    => $rule['aktif'] ?? 1,
                'c1'       => $rule['c1'],
                'c2'       => $rule['c2'],
                'c3'       => $rule['c3'],
                'c4'       => $rule['c4'],
                'c5'       => $rule['c5'],
                'k'        => $rule['k'],
                'label_k'  => self::labelKonsekuen($rule['k']),
            ];
        }
        return $list;
    }

    /** Kembalikan label teks dari nilai konsekuen k */
    private static function labelKonsekuen(float $k): string
    {
        return match (true) {
            $k >= 1.00 => 'Sangat Layak',
            $k >= 0.75 => 'Layak',
            $k >= 0.50 => 'Dipertimbangkan',
            default    => 'Tidak Layak',
        };
    }
}
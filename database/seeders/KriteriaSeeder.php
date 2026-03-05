<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class KriteriaSeeder extends Seeder
{
    /**
     * ✅ SEEDER v2.0.4 - FULLY FIXED
     * 
     * Perbaikan:
     * 1. Kurva tidak overlap (sharp boundaries)
     * 2. Nilai konsekuen lengkap untuk setiap himpunan
     * 3. Menggunakan Sugeno Orde Nol (nilai k untuk output)
     * 4. Boundaries yang jelas tanpa transisi bertumpuk
     */
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        DB::table('sub_kriteria')->truncate();
        DB::table('kriteria')->truncate();
        Schema::enableForeignKeyConstraints();

        // ============================================================
        // C1: LUAS LAHAN (Normalized 0-1 scale)
        // ============================================================
        $c1 = DB::table('kriteria')->insertGetId([
            'kode_kriteria' => 'C1',
            'nama_kriteria' => 'Luas Lahan',
            'mapping_field' => 'luas_lahan',
            'jenis_kriteria' => 'kontinu',
            'created_at' => now(), 'updated_at' => now()
        ]);

        DB::table('sub_kriteria')->insert([
            // ✅ FIXED: Sharp boundary at 0.4
            // Sempit: 0.0-0.4 (hanya satu yang aktif per input)
            [
                'kriteria_id' => $c1,
                'nama_sub_kriteria' => 'Sempit',
                'tipe_kurva' => 'bahu_kiri',
                'batas_bawah' => null,
                'batas_tengah_1' => null,
                'batas_tengah_2' => 0.3,
                'batas_atas' => 0.4,
                'nilai_konsekuen' => 0.4,  // ✅ Konsekuen lengkap
                'created_at' => now(),
                'updated_at' => now()
            ],
            // ✅ FIXED: Sharp boundary at 0.4 dan 0.7
            // Sedang: 0.4-0.7 (trapesium dengan peak di 0.5-0.6)
            [
                'kriteria_id' => $c1,
                'nama_sub_kriteria' => 'Sedang',
                'tipe_kurva' => 'trapesium',
                'batas_bawah' => 0.4,
                'batas_tengah_1' => 0.5,
                'batas_tengah_2' => 0.6,
                'batas_atas' => 0.7,
                'nilai_konsekuen' => 0.7,  // ✅ Konsekuen lengkap
                'created_at' => now(),
                'updated_at' => now()
            ],
            // ✅ FIXED: Sharp boundary at 0.7
            // Luas: 0.7+ (bahu_kanan)
            [
                'kriteria_id' => $c1,
                'nama_sub_kriteria' => 'Luas',
                'tipe_kurva' => 'bahu_kanan',
                'batas_bawah' => 0.7,
                'batas_tengah_1' => 0.85,
                'batas_tengah_2' => null,
                'batas_atas' => null,
                'nilai_konsekuen' => 1.0,  // ✅ Konsekuen lengkap
                'created_at' => now(),
                'updated_at' => now()
            ],
        ]);

        // ============================================================
        // C2: KEPEMILIKAN LAHAN (Discrete - String matching)
        // ============================================================
        $c2 = DB::table('kriteria')->insertGetId([
            'kode_kriteria' => 'C2',
            'nama_kriteria' => 'Kepemilikan Lahan',
            'mapping_field' => 'status_lahan',
            'jenis_kriteria' => 'diskrit',
            'created_at' => now(), 'updated_at' => now()
        ]);

        DB::table('sub_kriteria')->insert([
            // ✅ DISKRIT: String matching (no overlap possible)
            // Milik Sendiri → k = 1.0 (terbaik)
            [
                'kriteria_id' => $c2,
                'nama_sub_kriteria' => 'Milik Sendiri',
                'tipe_kurva' => 'diskrit',
                'batas_bawah' => null,
                'batas_tengah_1' => null,
                'batas_tengah_2' => null,
                'batas_atas' => null,
                'nilai_konsekuen' => 1.0,  // ✅ Terbaik
                'created_at' => now(),
                'updated_at' => now()
            ],
            // Sewa → k = 0.6 (cukup baik)
            [
                'kriteria_id' => $c2,
                'nama_sub_kriteria' => 'Sewa',
                'tipe_kurva' => 'diskrit',
                'batas_bawah' => null,
                'batas_tengah_1' => null,
                'batas_tengah_2' => null,
                'batas_atas' => null,
                'nilai_konsekuen' => 0.6,  // ✅ Cukup baik
                'created_at' => now(),
                'updated_at' => now()
            ],
            // Bagi Hasil → k = 0.4 (kurang baik)
            [
                'kriteria_id' => $c2,
                'nama_sub_kriteria' => 'Bagi Hasil',
                'tipe_kurva' => 'diskrit',
                'batas_bawah' => null,
                'batas_tengah_1' => null,
                'batas_tengah_2' => null,
                'batas_atas' => null,
                'nilai_konsekuen' => 0.4,  // ✅ Kurang baik
                'created_at' => now(),
                'updated_at' => now()
            ],
        ]);

        // ============================================================
        // C3: PENGALAMAN KELOMPOK TANI (Tahun - continuous)
        // ============================================================
        $c3 = DB::table('kriteria')->insertGetId([
            'kode_kriteria' => 'C3',
            'nama_kriteria' => 'Pengalaman Kelompok Tani',
            'mapping_field' => 'lama_berdiri',
            'jenis_kriteria' => 'kontinu',
            'created_at' => now(), 'updated_at' => now()
        ]);

        DB::table('sub_kriteria')->insert([
            // ✅ FIXED: Sharp boundary at 2.0 (NO OVERLAP)
            // Baru/Pemula: 0-2 tahun (hanya satu aktif)
            [
                'kriteria_id' => $c3,
                'nama_sub_kriteria' => 'Baru / Pemula',
                'tipe_kurva' => 'bahu_kiri',
                'batas_bawah' => null,
                'batas_tengah_1' => null,
                'batas_tengah_2' => 1.0,   // Peak at 1 year
                'batas_atas' => 2.0,       // Sharp boundary at 2
                'nilai_konsekuen' => 0.4,  // ✅ Konsekuen lengkap
                'created_at' => now(),
                'updated_at' => now()
            ],
            // ✅ FIXED: Sharp boundary at 2.0 dan 5.0 (NO OVERLAP)
            // Lama: 2-5 tahun (peak at 3-4)
            [
                'kriteria_id' => $c3,
                'nama_sub_kriteria' => 'Lama',
                'tipe_kurva' => 'trapesium',
                'batas_bawah' => 2.0,      // Start at 2 (sharp from Baru)
                'batas_tengah_1' => 3.0,   // Rise complete
                'batas_tengah_2' => 4.0,   // Plateau start
                'batas_atas' => 5.0,       // Fall complete, sharp from Sangat Lama
                'nilai_konsekuen' => 0.7,  // ✅ Konsekuen lengkap
                'created_at' => now(),
                'updated_at' => now()
            ],
            // ✅ FIXED: Sharp boundary at 5.0 (NO OVERLAP)
            // Sangat Lama: 5+ tahun
            [
                'kriteria_id' => $c3,
                'nama_sub_kriteria' => 'Sangat Lama',
                'tipe_kurva' => 'bahu_kanan',
                'batas_bawah' => 5.0,      // Start at 5 (sharp from Lama)
                'batas_tengah_1' => 8.0,   // Peak effectiveness
                'batas_tengah_2' => null,
                'batas_atas' => null,
                'nilai_konsekuen' => 1.0,  // ✅ Konsekuen lengkap
                'created_at' => now(),
                'updated_at' => now()
            ],
        ]);

        // ============================================================
        // C4: PRODUKTIVITAS KELOMPOK TANI (Ton - continuous)
        // ============================================================
        $c4 = DB::table('kriteria')->insertGetId([
            'kode_kriteria' => 'C4',
            'nama_kriteria' => 'Produktivitas Kelompok Tani',
            'mapping_field' => 'hasil_panen',
            'jenis_kriteria' => 'kontinu',
            'created_at' => now(), 'updated_at' => now()
        ]);

        DB::table('sub_kriteria')->insert([
            // ✅ FIXED: Sharp boundary at 5.0 (NO OVERLAP)
            // Rendah: <5 ton (bahu_kiri)
            [
                'kriteria_id' => $c4,
                'nama_sub_kriteria' => 'Rendah',
                'tipe_kurva' => 'bahu_kiri',
                'batas_bawah' => null,
                'batas_tengah_1' => null,
                'batas_tengah_2' => 4.0,   // Peak at 4 ton
                'batas_atas' => 5.0,       // Sharp boundary at 5
                'nilai_konsekuen' => 0.4,  // ✅ Konsekuen lengkap
                'created_at' => now(),
                'updated_at' => now()
            ],
            // ✅ FIXED: Sharp boundary at 5.0 dan 7.0 (NO OVERLAP)
            // Sedang: 5-7 ton (peak at 5.5-6.5)
            [
                'kriteria_id' => $c4,
                'nama_sub_kriteria' => 'Sedang',
                'tipe_kurva' => 'trapesium',
                'batas_bawah' => 5.0,      // Start at 5 (sharp from Rendah)
                'batas_tengah_1' => 5.5,   // Rise complete
                'batas_tengah_2' => 6.5,   // Plateau area
                'batas_atas' => 7.0,       // Fall complete, sharp from Tinggi
                'nilai_konsekuen' => 0.7,  // ✅ Konsekuen lengkap
                'created_at' => now(),
                'updated_at' => now()
            ],
            // ✅ FIXED: Sharp boundary at 7.0 (NO OVERLAP)
            // Tinggi: 7+ ton (bahu_kanan)
            [
                'kriteria_id' => $c4,
                'nama_sub_kriteria' => 'Tinggi',
                'tipe_kurva' => 'bahu_kanan',
                'batas_bawah' => 7.0,      // Start at 7 (sharp from Sedang)
                'batas_tengah_1' => 9.0,   // Peak effectiveness
                'batas_tengah_2' => null,
                'batas_atas' => null,
                'nilai_konsekuen' => 1.0,  // ✅ Konsekuen lengkap
                'created_at' => now(),
                'updated_at' => now()
            ],
        ]);

        // ============================================================
        // C5: KELENGKAPAN DOKUMEN (Discrete - String matching)
        // ============================================================
        $c5 = DB::table('kriteria')->insertGetId([
            'kode_kriteria' => 'C5',
            'nama_kriteria' => 'Kelengkapan Dokumen',
            'mapping_field' => 'status',
            'jenis_kriteria' => 'diskrit',
            'created_at' => now(), 'updated_at' => now()
        ]);

        DB::table('sub_kriteria')->insert([
            // ✅ DISKRIT: String matching (no overlap possible)
            // Sangat Lengkap → k = 1.0 (terbaik)
            [
                'kriteria_id' => $c5,
                'nama_sub_kriteria' => 'Sangat Lengkap',
                'tipe_kurva' => 'diskrit',
                'batas_bawah' => null,
                'batas_tengah_1' => null,
                'batas_tengah_2' => null,
                'batas_atas' => null,
                'nilai_konsekuen' => 1.0,  // ✅ Terbaik
                'created_at' => now(),
                'updated_at' => now()
            ],
            // Lengkap → k = 0.7 (baik)
            [
                'kriteria_id' => $c5,
                'nama_sub_kriteria' => 'Lengkap',
                'tipe_kurva' => 'diskrit',
                'batas_bawah' => null,
                'batas_tengah_1' => null,
                'batas_tengah_2' => null,
                'batas_atas' => null,
                'nilai_konsekuen' => 0.7,  // ✅ Baik
                'created_at' => now(),
                'updated_at' => now()
            ],
            // Tidak Lengkap → k = 0.2 (kurang baik)
            [
                'kriteria_id' => $c5,
                'nama_sub_kriteria' => 'Tidak Lengkap',
                'tipe_kurva' => 'diskrit',
                'batas_bawah' => null,
                'batas_tengah_1' => null,
                'batas_tengah_2' => null,
                'batas_atas' => null,
                'nilai_konsekuen' => 0.2,  // ✅ Kurang baik
                'created_at' => now(),
                'updated_at' => now()
            ],
        ]);
    }
}
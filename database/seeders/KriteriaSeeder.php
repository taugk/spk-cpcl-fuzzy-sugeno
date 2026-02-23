<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class KriteriaSeeder extends Seeder
{
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        DB::table('sub_kriteria')->truncate();
        DB::table('kriteria')->truncate();
        Schema::enableForeignKeyConstraints();

        // --- C1: LUAS LAHAN ---
        $c1 = DB::table('kriteria')->insertGetId([
            'kode_kriteria' => 'C1',
            'nama_kriteria' => 'Luas Lahan',
            'mapping_field' => 'luas_lahan',
            'jenis_kriteria' => 'kontinu',
            'created_at' => now(), 'updated_at' => now()
        ]);

        DB::table('sub_kriteria')->insert([
            ['kriteria_id' => $c1, 'nama_sub_kriteria' => 'Sempit', 'tipe_kurva' => 'bahu_kiri', 'batas_bawah' => null, 'batas_tengah_1' => null, 'batas_tengah_2' => 0.25, 'batas_atas' => 0.5, 'nilai_diskrit' => null, 'created_at' => now(), 'updated_at' => now()],
            ['kriteria_id' => $c1, 'nama_sub_kriteria' => 'Sedang', 'tipe_kurva' => 'trapesium', 'batas_bawah' => 0.25, 'batas_tengah_1' => 0.5, 'batas_tengah_2' => 0.5, 'batas_atas' => 1.0, 'nilai_diskrit' => null, 'created_at' => now(), 'updated_at' => now()],
            ['kriteria_id' => $c1, 'nama_sub_kriteria' => 'Luas', 'tipe_kurva' => 'bahu_kanan', 'batas_bawah' => 0.5, 'batas_tengah_1' => 1.0, 'batas_tengah_2' => null, 'batas_atas' => null, 'nilai_diskrit' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // --- C2: KEPEMILIKAN LAHAN ---
        $c2 = DB::table('kriteria')->insertGetId([
            'kode_kriteria' => 'C2',
            'nama_kriteria' => 'Kepemilikan Lahan',
            'mapping_field' => 'status_lahan',
            'jenis_kriteria' => 'diskrit',
            'created_at' => now(), 'updated_at' => now()
        ]);

        DB::table('sub_kriteria')->insert([
            ['kriteria_id' => $c2, 'nama_sub_kriteria' => 'Milik Sendiri', 'tipe_kurva' => 'diskrit', 'batas_bawah' => null, 'batas_tengah_1' => null, 'batas_tengah_2' => null, 'batas_atas' => null, 'nilai_diskrit' => 1.0, 'created_at' => now(), 'updated_at' => now()],
            ['kriteria_id' => $c2, 'nama_sub_kriteria' => 'Sewa', 'tipe_kurva' => 'diskrit', 'batas_bawah' => null, 'batas_tengah_1' => null, 'batas_tengah_2' => null, 'batas_atas' => null, 'nilai_diskrit' => 0.6, 'created_at' => now(), 'updated_at' => now()],
            ['kriteria_id' => $c2, 'nama_sub_kriteria' => 'Bagi Hasil', 'tipe_kurva' => 'diskrit', 'batas_bawah' => null, 'batas_tengah_1' => null, 'batas_tengah_2' => null, 'batas_atas' => null, 'nilai_diskrit' => 0.4, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // --- C3: PENGALAMAN ---
        $c3 = DB::table('kriteria')->insertGetId([
            'kode_kriteria' => 'C3',
            'nama_kriteria' => 'Pengalaman Kelompok Tani',
            'mapping_field' => 'lama_berdiri',
            'jenis_kriteria' => 'kontinu',
            'created_at' => now(), 'updated_at' => now()
        ]);

        DB::table('sub_kriteria')->insert([
            ['kriteria_id' => $c3, 'nama_sub_kriteria' => 'Baru / Pemula', 'tipe_kurva' => 'bahu_kiri', 'batas_bawah' => null, 'batas_tengah_1' => null, 'batas_tengah_2' => 1.0, 'batas_atas' => 3.0, 'nilai_diskrit' => null, 'created_at' => now(), 'updated_at' => now()],
            ['kriteria_id' => $c3, 'nama_sub_kriteria' => 'Lama', 'tipe_kurva' => 'trapesium', 'batas_bawah' => 1.0, 'batas_tengah_1' => 3.0, 'batas_tengah_2' => 5.0, 'batas_atas' => 10.0, 'nilai_diskrit' => null, 'created_at' => now(), 'updated_at' => now()],
            ['kriteria_id' => $c3, 'nama_sub_kriteria' => 'Sangat Lama', 'tipe_kurva' => 'bahu_kanan', 'batas_bawah' => 5.0, 'batas_tengah_1' => 10.0, 'batas_tengah_2' => null, 'batas_atas' => null, 'nilai_diskrit' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // --- C4: PRODUKTIVITAS ---
        $c4 = DB::table('kriteria')->insertGetId([
            'kode_kriteria' => 'C4',
            'nama_kriteria' => 'Produktivitas Kelompok Tani',
            'mapping_field' => 'hasil_panen',
            'jenis_kriteria' => 'kontinu',
            'created_at' => now(), 'updated_at' => now()
        ]);

        DB::table('sub_kriteria')->insert([
            ['kriteria_id' => $c4, 'nama_sub_kriteria' => 'Rendah', 'tipe_kurva' => 'bahu_kiri', 'batas_bawah' => null, 'batas_tengah_1' => null, 'batas_tengah_2' => 4.0, 'batas_atas' => 6.0, 'nilai_diskrit' => null, 'created_at' => now(), 'updated_at' => now()],
            ['kriteria_id' => $c4, 'nama_sub_kriteria' => 'Sedang', 'tipe_kurva' => 'trapesium', 'batas_bawah' => 4.0, 'batas_tengah_1' => 6.0, 'batas_tengah_2' => 6.0, 'batas_atas' => 8.0, 'nilai_diskrit' => null, 'created_at' => now(), 'updated_at' => now()],
            ['kriteria_id' => $c4, 'nama_sub_kriteria' => 'Tinggi', 'tipe_kurva' => 'bahu_kanan', 'batas_bawah' => 6.0, 'batas_tengah_1' => 8.0, 'batas_tengah_2' => null, 'batas_atas' => null, 'nilai_diskrit' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // --- C5: DOKUMEN ---
        $c5 = DB::table('kriteria')->insertGetId([
            'kode_kriteria' => 'C5',
            'nama_kriteria' => 'Kelengkapan Dokumen',
            'mapping_field' => 'status',
            'jenis_kriteria' => 'diskrit',
            'created_at' => now(), 'updated_at' => now()
        ]);

        DB::table('sub_kriteria')->insert([
            ['kriteria_id' => $c5, 'nama_sub_kriteria' => 'Sangat Lengkap', 'tipe_kurva' => 'diskrit', 'batas_bawah' => null, 'batas_tengah_1' => null, 'batas_tengah_2' => null, 'batas_atas' => null, 'nilai_diskrit' => 1.0, 'created_at' => now(), 'updated_at' => now()],
            ['kriteria_id' => $c5, 'nama_sub_kriteria' => 'Lengkap', 'tipe_kurva' => 'diskrit', 'batas_bawah' => null, 'batas_tengah_1' => null, 'batas_tengah_2' => null, 'batas_atas' => null, 'nilai_diskrit' => 0.7, 'created_at' => now(), 'updated_at' => now()],
            ['kriteria_id' => $c5, 'nama_sub_kriteria' => 'Tidak Lengkap', 'tipe_kurva' => 'diskrit', 'batas_bawah' => null, 'batas_tengah_1' => null, 'batas_tengah_2' => null, 'batas_atas' => null, 'nilai_diskrit' => 0.2, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
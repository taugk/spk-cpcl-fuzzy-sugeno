<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class AlamatWilayahSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Ambil Data Kabupaten (Kuningan memiliki kode 32.08)
        // Kita cari spesifik Kabupaten Kuningan dari daftar regencies Jawa Barat (32)
        $responseKab = Http::get('https://wilayah.id/api/regencies/32.json');
        $kabupatenKuningan = collect($responseKab->json()['data'])->firstWhere('code', '32.08');

        if (!$kabupatenKuningan) {
            $this->command->error("Kabupaten Kuningan tidak ditemukan.");
            return;
        }

        $this->command->info("Mengambil data untuk: " . $kabupatenKuningan['name']);

        // 2. Ambil Data Kecamatan di Kuningan (32.08)
        $responseKec = Http::get('https://wilayah.id/api/districts/32.08.json');
        $listKecamatan = $responseKec->json()['data'];

        foreach ($listKecamatan as $kec) {
            $this->command->info("Menarik data Desa untuk Kecamatan: " . $kec['name']);

            // 3. Ambil Data Desa/Kelurahan berdasarkan DISTRICT_CODE
            $responseDesa = Http::get("https://wilayah.id/api/villages/{$kec['code']}.json");
            $listDesa = $responseDesa->json()['data'];

            foreach ($listDesa as $desa) {
                DB::table('alamat')->updateOrInsert(
                    [
                        'kd_kab'  => $kabupatenKuningan['code'],
                        'kd_kec'  => $kec['code'],
                        'kd_desa' => $desa['code'],
                    ],
                    [
                        'kabupaten'  => $kabupatenKuningan['name'],
                        'kecamatan'  => $kec['name'],
                        'desa'       => $desa['name'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }

        $this->command->info("Selesai! Data alamat Kuningan telah masuk.");
    }
}
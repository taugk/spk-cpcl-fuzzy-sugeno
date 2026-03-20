<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class CpclSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('id_ID');

        // Ambil ID alamat yang ada di Kecamatan Cigugur
        // Sesuaikan query ini dengan struktur tabel alamat Anda
        $alamatIds = DB::table('alamat')
            ->where('kecamatan', 'LIKE', '%Cigugur%')
            ->pluck('id')
            ->toArray();

        // Fallback jika tabel alamat kosong/tidak ditemukan, gunakan null atau ID manual
        if (empty($alamatIds)) {
            $alamatIds = [null]; 
        }

        $bidang = ['PANGAN', 'HARTIBUN'];
        $rencana = ['Pengembangan Benih',
                                                'Penyediaan Pupuk',
                                                'Pengadaan Alsintan',
                                                'Rehabilitasi Jaringan Irigasi',
                                                'Peningkatan Produksi'];
        $statusLahan = ['milik', 'sewa', 'garapan'];
        

        for ($i = 1; $i <= 30; $i++) {
            DB::table('cpcl')->insert([
                // BAGIAN 1: INFORMASI KELOMPOK
                'nama_kelompok' => 'Kelompok Tani ' . $faker->company,
                'nama_ketua'    => $faker->name,
                'nik_ketua'     => $faker->numerify('3208##########'),
                'bidang'        => $faker->randomElement($bidang),
                'rencana_usaha' => $faker->randomElement($rencana),
                'lokasi'        => 'Blok ' . $faker->streetName . ', Kec. Cigugur, Kuningan',

                // BAGIAN 2: DATA OPERASIONAL
                'luas_lahan'    => $faker->randomFloat(2, 0.5, 5.0), // 0.5 - 5.0 ha
                'lama_berdiri'  => $faker->numberBetween(1, 15),
                'hasil_panen'   => $faker->randomFloat(2, 2, 8),
                'status_lahan'  => $faker->randomElement($statusLahan),

                // DATA SPASIAL (Koordinat area Cigugur sekitar -6.97, 108.46)
                'latitude'      => $faker->latitude(-6.980000, -6.960000),
                'longitude'     => $faker->longitude(108.450000, 108.470000),

                // BAGIAN 3: LAMPIRAN (Dummy path)
                'file_proposal' => 'proposals/sample_prop_' . $i . '.pdf',
                'file_ktp'      => 'ktp/sample_ktp_' . $i . '.jpg',
                'file_sk'       => $faker->boolean(70) ? 'sk/sample_sk_' . $i . '.pdf' : null,
                'foto_lahan'    => 'lahan/sample_lahan_' . $i . '.jpg',

                // STATUS
                'status'        => 'baru',
                'catatan_verifikator' => $faker->boolean(30) ? $faker->sentence : null,

                // FOREIGN KEY ALAMAT
                'alamat_id'     => $faker->randomElement($alamatIds),

                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        }
    }
}
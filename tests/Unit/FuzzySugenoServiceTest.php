<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\FuzzySugenoService;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;

/**
 * =============================================================================
 * WhiteBox Test Suite — FuzzySugenoService (Final Version)
 * =============================================================================
 * Berdasarkan Schema Migration CPCL & Kriteria 2026
 * * Strategi:
 * - Structural Coverage (Statement, Branch, Path).
 * - Reflection untuk isolasi logic private method.
 * - Database Integration dengan RefreshDatabase.
 * =============================================================================
 */
class FuzzySugenoServiceTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    /**
     * Memanggil method private/protected dari FuzzySugenoService
     */
    private function invokePrivate(string $method, array $args = []): mixed
    {
        $ref = new ReflectionMethod(FuzzySugenoService::class, $method);
        $ref->setAccessible(true);
        return $ref->invoke(null, ...$args);
    }

    /**
     * Membuat objek sub-kriteria (mocking model)
     */
    private function makeSub(array $attrs): object
    {
        return (object) array_merge([
            'nama_sub_kriteria' => 'Sedang',
            'tipe_kurva'        => 'trapesium',
            'batas_bawah'       => 0,
            'batas_tengah_1'    => 0,
            'batas_tengah_2'    => 0,
            'batas_atas'        => 0,
            'nilai_konsekuen'   => 0.7,
        ], $attrs);
    }

    /**
     * Seeding data CPCL & Kriteria sesuai dengan Schema Migration terbaru
     */
    private function seedCpcl(array $cpclOverride = [], array $kriteriaRows = []): int
    {
        // 1. Insert ke tabel cpcl
        $cpclId = DB::table('cpcl')->insertGetId(array_merge([
            'nama_kelompok'      => 'Kelompok Tani Uji',
            'nama_ketua'         => 'Budi',
            'nik_ketua'          => '1234567890123456',
            'bidang'             => 'Pertanian',
            'rencana_usaha'      => 'Padi',
            'lokasi'             => 'Desa Uji',
            'luas_lahan'         => 2.50,         // decimal(8,2)
            'lama_berdiri'       => 5,            // integer
            'hasil_panen'        => 3.00,         // decimal(8,2)
            'status_lahan'       => 'milik sendiri',
            'latitude'           => -6.91474400,  // decimal(10,8)
            'longitude'          => 107.60981000, // decimal(11,8)
            'file_proposal'      => 'proposal.pdf',
            'file_ktp'           => 'ktp.jpg',
            'file_sk'            => 'sk.pdf',
            'foto_lahan'         => 'lahan.jpg',
            'status'             => 'terverifikasi',
            'catatan_verifikator'=> null,
            'created_at'         => now(),
            'updated_at'         => now(),
            'deleted_at'         => null,         // SoftDeletes
        ], $cpclOverride));

        // 2. Insert Kriteria & Sub Kriteria
        foreach ($kriteriaRows as $k) {
            $kId = DB::table('kriteria')->insertGetId([
                'kode_kriteria'  => $k['kode'],
                'nama_kriteria'  => $k['nama'],
                'mapping_field'  => $k['field'],
                'jenis_kriteria' => $k['jenis'], // enum: kontinu/diskrit
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            foreach ($k['sub'] as $s) {
                DB::table('sub_kriteria')->insert([
                    'kriteria_id'       => $kId,
                    'nama_sub_kriteria' => $s['nama'],
                    'tipe_kurva'        => $s['tipe'],
                    'batas_bawah'       => $s['a'] ?? 0,
                    'batas_tengah_1'    => $s['b'] ?? 0,
                    'batas_tengah_2'    => $s['c'] ?? 0,
                    'batas_atas'        => $s['d'] ?? 0,
                    'nilai_konsekuen'   => $s['k'] ?? 0.5,
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ]);
            }

            // Simpan nilai verifikasi admin jika jenis kriteria DISKRIT
            if (isset($k['penilaian'])) {
                DB::table('cpcl_penilaian')->insert([
                    'cpcl_id'     => $cpclId,
                    'kriteria_id' => $kId,
                    'nilai'       => $k['penilaian'],
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }
        }

        return $cpclId;
    }

    // =========================================================================
    // 1. UNIT TEST: hitungMu (Membership Function Logic)
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function hitungMu_diskrit_case_insensitive_match(): void
    {
        $sub = $this->makeSub(['tipe_kurva' => 'diskrit', 'nama_sub_kriteria' => 'Milik Sendiri']);
        $this->assertEquals(1.0, $this->invokePrivate('hitungMu', [$sub, 'MILIK SENDIRI']));
        $this->assertEquals(0.0, $this->invokePrivate('hitungMu', [$sub, 'Sewa']));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function hitungMu_trapesium_logic_check(): void
    {
        // a=2, b=4, c=6, d=8
        $sub = $this->makeSub(['tipe_kurva'=>'trapesium','batas_bawah'=>2,'batas_tengah_1'=>4,'batas_tengah_2'=>6,'batas_atas'=>8]);
        
        $this->assertEquals(0.0, $this->invokePrivate('hitungMu', [$sub, 1]));   // Luar kiri
        $this->assertEquals(0.5, $this->invokePrivate('hitungMu', [$sub, 3]));   // Rising (3-2)/(4-2)
        $this->assertEquals(1.0, $this->invokePrivate('hitungMu', [$sub, 5]));   // Flat top
        $this->assertEquals(0.5, $this->invokePrivate('hitungMu', [$sub, 7]));   // Falling (8-7)/(8-6)
        $this->assertEquals(0.0, $this->invokePrivate('hitungMu', [$sub, 9]));   // Luar kanan
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function hitungMu_bahu_kiri_logic_check(): void
    {
        // c=5, d=10. mu=1 jika x<=5, mu=0 jika x>=10
        $sub = $this->makeSub(['tipe_kurva' => 'bahu_kiri', 'batas_tengah_2' => 5, 'batas_atas' => 10]);
        $this->assertEquals(1.0, $this->invokePrivate('hitungMu', [$sub, 4]));
        $this->assertEquals(0.6, $this->invokePrivate('hitungMu', [$sub, 7]), '', 0.001); // (10-7)/(10-5)
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function hitungMu_bahu_kanan_logic_check(): void
    {
        // a=2, b=8. mu=0 jika x<=2, mu=1 jika x>=8
        $sub = $this->makeSub(['tipe_kurva' => 'bahu_kanan', 'batas_bawah' => 2, 'batas_tengah_1' => 8]);
        $this->assertEquals(0.0, $this->invokePrivate('hitungMu', [$sub, 1]));
        $this->assertEquals(0.5, $this->invokePrivate('hitungMu', [$sub, 5]), '', 0.001); // (5-2)/(8-2)
        $this->assertEquals(1.0, $this->invokePrivate('hitungMu', [$sub, 10]));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function hitungMu_sanitizes_numeric_string_input(): void
    {
        $sub = $this->makeSub(['tipe_kurva' => 'bahu_kanan', 'batas_bawah' => 2, 'batas_tengah_1' => 8]);
        $this->assertEquals(0.5, $this->invokePrivate('hitungMu', [$sub, '5 Hektar']), '', 0.001);
    }

    // =========================================================================
    // 2. UNIT TEST: getFallbackK (Rule Consequent Fallback)
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function getFallbackK_keyword_matching_priority(): void
    {
        $this->assertEquals(1.0, $this->invokePrivate('getFallbackK', ['Sangat Tinggi']));
        $this->assertEquals(1.0, $this->invokePrivate('getFallbackK', ['Cukup Baik'])); // 'baik' has priority
        $this->assertEquals(0.7, $this->invokePrivate('getFallbackK', ['Sedang']));
        $this->assertEquals(0.4, $this->invokePrivate('getFallbackK', ['Rendah']));
    }

    // =========================================================================
    // 3. UNIT TEST: getSkalaPrioritas
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function getSkalaPrioritas_ranges_and_boundaries(): void
    {
        $this->assertEquals('Prioritas I',   $this->invokePrivate('getSkalaPrioritas', [0.85])['prioritas']);
        $this->assertEquals('Prioritas II',  $this->invokePrivate('getSkalaPrioritas', [0.70])['prioritas']);
        $this->assertEquals('Prioritas III', $this->invokePrivate('getSkalaPrioritas', [0.50])['prioritas']);
        $this->assertEquals('Prioritas IV',  $this->invokePrivate('getSkalaPrioritas', [0.20])['prioritas']);
    }

    // =========================================================================
    // 4. UNIT TEST: kartesian (Rule Combinations)
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function kartesian_generates_correct_number_of_combinations(): void
    {
        $input = [
            'C1' => [['mu' => 0.5], ['mu' => 0.8]],
            'C2' => [['mu' => 0.3], ['mu' => 0.9]],
            'C3' => [['mu' => 1.0]]
        ];
        // 2 x 2 x 1 = 4 combinations
        $this->assertCount(4, $this->invokePrivate('kartesian', [$input]));
    }

    // =========================================================================
    // 5. INTEGRATION TEST: hitung & hitungDanSimpan
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function hitung_integration_with_database_schema(): void
    {
        $cpclId = $this->seedCpcl(
            ['luas_lahan' => 5.0, 'status_lahan' => 'sewa'],
            [
                [
                    'kode' => 'C1', 'nama' => 'Luas', 'field' => 'luas_lahan', 'jenis' => 'kontinu',
                    'sub'  => [['nama' => 'Luas', 'tipe' => 'bahu_kanan', 'a' => 2, 'b' => 8, 'k' => 1.0]],
                ],
                [
                    'kode' => 'D1', 'nama' => 'Status', 'field' => 'status_lahan', 'jenis' => 'diskrit',
                    'penilaian' => 'sewa',
                    'sub'  => [['nama' => 'sewa', 'tipe' => 'diskrit', 'k' => 0.6]],
                ]
            ]
        );

        $hasil = FuzzySugenoService::hitung($cpclId);

        $this->assertArrayHasKey('z', $hasil);
        $this->assertArrayHasKey('skor_akhir', $hasil);
        $this->assertEqualsWithDelta($hasil['z'] * 100, $hasil['skor_akhir'], 0.01);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function hitungDanSimpan_upserts_correctly(): void
    {
        $cpclId = $this->seedCpcl(['luas_lahan' => 3.0]);
        
        FuzzySugenoService::hitungDanSimpan($cpclId);
        FuzzySugenoService::hitungDanSimpan($cpclId); // Double call

        $this->assertDatabaseCount('hasil_fuzzy', 1);
        $this->assertDatabaseHas('hasil_fuzzy', ['cpcl_id' => $cpclId]);
    }

    // =========================================================================
    // 6. INTEGRATION TEST: hitungSemuaDanRanking
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function hitungSemuaDanRanking_filters_status_and_softdeletes(): void
    {
        // 1. Valid & Terverifikasi
        $this->seedCpcl(['nama_kelompok' => 'A', 'status' => 'terverifikasi']);
        // 2. Masih Baru (Harus di-exclude)
        $this->seedCpcl(['nama_kelompok' => 'B', 'status' => 'baru']);
        // 3. Terverifikasi tapi Soft Deleted (Harus di-exclude)
        $this->seedCpcl(['nama_kelompok' => 'C', 'status' => 'terverifikasi', 'deleted_at' => now()]);

        $ranked = FuzzySugenoService::hitungSemuaDanRanking();

        $this->assertCount(1, $ranked);
        $this->assertEquals('Kelompok Tani Uji', DB::table('cpcl')->find($ranked[0]['cpcl_id'])->nama_kelompok);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function hitungSemuaDanRanking_sorts_descending(): void
    {
        // CPCL 1: Skor Tinggi
        $id1 = $this->seedCpcl(['luas_lahan' => 10], [[
            'kode' => 'C1', 'nama' => 'L', 'field' => 'luas_lahan', 'jenis' => 'kontinu',
            'sub' => [['nama' => 'B', 'tipe' => 'bahu_kanan', 'a' => 1, 'b' => 5, 'k' => 1.0]]
        ]]);

        // CPCL 2: Skor Rendah
        $id2 = $this->seedCpcl(['luas_lahan' => 1], [[
            'kode' => 'C1', 'nama' => 'L', 'field' => 'luas_lahan', 'jenis' => 'kontinu',
            'sub' => [['nama' => 'B', 'tipe' => 'bahu_kanan', 'a' => 1, 'b' => 5, 'k' => 0.1]]
        ]]);

        $ranked = FuzzySugenoService::hitungSemuaDanRanking();

        $this->assertEquals($id1, $ranked[0]['cpcl_id']);
        $this->assertEquals($id2, $ranked[1]['cpcl_id']);
    }

    // =========================================================================
    // 7. INTEGRATION TEST: cekSinkronisasiData
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function cekSinkronisasiData_detects_empty_and_invalid_types(): void
    {
        // Skenario 1: Kontinu tapi bukan angka (String 'abc')
        $cpclId = $this->seedCpcl(['luas_lahan' => 'abc'], [[
            'kode' => 'C1', 'nama' => 'Luas', 'field' => 'luas_lahan', 'jenis' => 'kontinu', 'sub' => []
        ]]);
        $res = FuzzySugenoService::cekSinkronisasiData($cpclId);
        $this->assertFalse($res['is_valid']);
        $this->assertStringContainsString('bukan angka valid', $res['messages'][0]);

        // Skenario 2: Diskrit tapi admin belum memberi penilaian (null)
        $cpclId2 = $this->seedCpcl([], [[
            'kode' => 'D1', 'nama' => 'Status', 'field' => 'status_lahan', 'jenis' => 'diskrit', 'sub' => []
            // penilaian tidak di-set
        ]]);
        $res2 = FuzzySugenoService::cekSinkronisasiData($cpclId2);
        $this->assertFalse($res2['is_valid']);
        $this->assertStringContainsString('Belum diverifikasi admin', $res2['messages'][0]);
    }
}
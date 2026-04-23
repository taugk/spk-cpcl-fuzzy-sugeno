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
    public static function hitungDanSimpan(int $cpcl_id): array
    {
        Log::info("=== [START] Hitung & Simpan Fuzzy | CPCL ID: {$cpcl_id} ===");

        try {
            $validasi = self::cekSinkronisasiData($cpcl_id);
            if (!$validasi['is_valid']) {
                throw new \Exception("Data tidak valid: " . implode(', ', $validasi['messages']));
            }

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

        } catch (\Exception $e) {
            Log::error("=== [ERROR] CPCL ID {$cpcl_id}: " . $e->getMessage());
            throw $e;
        }
    }

    public static function hitung(int $cpcl_id): array
    {
        $cpcl         = Cpcl::findOrFail($cpcl_id);
        $kriteriaList = Kriteria::with('subKriteria')->orderBy('id')->get();

        $fuzzifikasi = [];

        foreach ($kriteriaList as $kriteria) {

            $field = $kriteria->mapping_field;

            if ($kriteria->jenis_kriteria === 'kontinu') {
                $inputVal = $cpcl->{$field} ?? '0';
            } else {
                $inputVal = DB::table('cpcl_penilaian')
                    ->where('cpcl_id', $cpcl_id)
                    ->where('kriteria_id', $kriteria->id)
                    ->value('nilai') ?? $cpcl->{$field} ?? '-';
            }

            $inputString = (string) $inputVal;
            $himpunanAktif = [];

            foreach ($kriteria->subKriteria as $sub) {

                $mu = self::hitungMu($sub, $inputString);

                $z = (float) ($sub->nilai_konsekuen ?? 0);
                if ($z <= 0) {
                    $z = self::getFallbackK($sub->nama_sub_kriteria);
                }

                if ($mu > 0) {
                    $himpunanAktif[] = [
                        'nama'   => $sub->nama_sub_kriteria,
                        'mu'     => round($mu, 4),
                        'z'      => $z,
                        'tipe'   => $sub->tipe_kurva,
                        'params' => [
                            'tipe' => $sub->tipe_kurva,
                            'a'    => $sub->batas_bawah,
                            'b'    => $sub->batas_tengah_1,
                            'c'    => $sub->batas_tengah_2,
                            'd'    => $sub->batas_atas,
                        ]
                    ];
                }
            }

            $fuzzifikasi[] = [
                'kode'     => $kriteria->kode_kriteria,
                'nama'     => $kriteria->nama_kriteria,
                'input'    => $inputString,
                'himpunan' => $himpunanAktif,
            ];
        }

        // =========================
        // 🔥 RULE BASE SUGENO
        // =========================

        $rules = [];

        $listHimpunan = array_map(fn($f) => $f['himpunan'], $fuzzifikasi);
        $kombinasiRule = self::kombinasi($listHimpunan);

        $totalAlphaZ = 0.0;
        $totalAlpha  = 0.0;
        $maxAlpha    = 0.0;

        foreach ($kombinasiRule as $index => $ruleItems) {

            if (empty($ruleItems)) continue;

            $alpha = min(array_column($ruleItems, 'mu'));

            if ($alpha <= 0) continue;

            // 🔥 Z RULE (default: rata-rata)
            $z = array_sum(array_column($ruleItems, 'z')) / count($ruleItems);

           $rules[] = [
                'rule_id' => $index + 1,
                'rule'    => 'R' . ($index + 1),

                'anteceden' => array_map(function($r, $idx) use ($fuzzifikasi) {
                    return [
                        'kriteria' => $fuzzifikasi[$idx]['nama'],
                        'himpunan' => $r['nama'],
                        'mu'       => $r['mu']
                    ];
                }, $ruleItems, array_keys($ruleItems)),

                'alpha'     => round($alpha, 4),
                'z_rule'    => round($z, 4),
                'alpha_x_z' => round($alpha * $z, 4),
            ];

            $totalAlphaZ += ($alpha * $z);
            $totalAlpha  += $alpha;

            if ($alpha > $maxAlpha) {
                $maxAlpha = $alpha;
            }
        }

        $z_final = $totalAlpha > 0 ? ($totalAlphaZ / $totalAlpha) : 0.0;

        $skala = self::getSkalaPrioritas($z_final);

        return [
            'cpcl'             => $cpcl,
            'fuzzifikasi'      => $fuzzifikasi,
            'rules'            => $rules,
            'sum_alpha_z'      => round($totalAlphaZ, 4),
            'sum_alpha'        => round($totalAlpha, 4),
            'alpha'            => round($maxAlpha, 4),
            'z'                => round($z_final, 4),
            'skor_akhir'       => round($z_final * 100, 2),
            'status_kelayakan' => $skala['status'],
            'skala_prioritas'  => $skala['prioritas'],
            'interpretasi'     => $skala['interpretasi'],
        ];
    }

    // =========================
    // 🔥 GENERATE KOMBINASI RULE
    // =========================
    private static function kombinasi(array $arrays, array $prefix = []): array
    {
        if (empty($arrays)) return [$prefix];

        $result = [];
        $first = array_shift($arrays);

        foreach ($first as $item) {
            $newPrefix = array_merge($prefix, [$item]);
            $result = array_merge($result, self::kombinasi($arrays, $newPrefix));
        }

        return $result;
    }

    /**
 * Proses hitung semua CPCL terverifikasi dan simpan ranking.
 * Ditambahkan parameter $bidang untuk filter admin bidang.
 */
public static function hitungSemuaDanRanking(?string $periode = null, ?string $bidang = null): Collection
{
    // 1. Inisialisasi query
    $query = Cpcl::where('status', 'terverifikasi');

    // 2. Filter berdasarkan periode jika ada
    if ($periode) {
        $query->whereYear('created_at', $periode);
    }

    // 3. 🔥 FILTER BERDASARKAN BIDANG (Tambahan Baru)
    // Pastikan kolom 'bidang' ada di tabel cpcl Anda
    if ($bidang) {
        $query->where('bidang', $bidang);
    }

    $cpclList = $query->get();
    $hasilList = [];

    // 4. Proses perhitungan satu per satu
    foreach ($cpclList as $cpcl) {
        try {
            $hasil = self::hitung($cpcl->id);

            $hasilList[] = array_merge([
                'cpcl_id' => $cpcl->id
            ], $hasil);

        } catch (\Exception $e) {
            Log::error("Skip ID {$cpcl->id}: " . $e->getMessage());
        }
    }

    // 5. Urutkan berdasarkan skor tertinggi (Ranking)
    $ranked = collect($hasilList)
        ->sortByDesc('skor_akhir')
        ->values();

    // 6. Simpan ke database menggunakan transaksi
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

    public static function cekSinkronisasiData(int $cpcl_id): array
    {
        $cpcl = Cpcl::findOrFail($cpcl_id);
        $kriteriaList = Kriteria::with('subKriteria')->get();

        $errors = [];

        foreach ($kriteriaList as $kriteria) {

            $field = $kriteria->mapping_field;

            if ($kriteria->jenis_kriteria === 'kontinu') {
                $nilai = $cpcl->{$field} ?? null;
                $clean = preg_replace('/[^0-9.]/', '', (string)$nilai);

                if (is_null($nilai) || $nilai === '') {
                    $errors[] = "Cek {$kriteria->kode_kriteria}: Data kosong.";
                } elseif (!is_numeric($clean)) {
                    $errors[] = "Cek {$kriteria->kode_kriteria}: Bukan angka.";
                }

            } else {
                $nilai = DB::table('cpcl_penilaian')
                    ->where('cpcl_id', $cpcl_id)
                    ->where('kriteria_id', $kriteria->id)
                    ->value('nilai');

                if (is_null($nilai) || $nilai === '') {
                    $errors[] = "Cek {$kriteria->kode_kriteria}: Belum diisi.";
                }
            }
        }

        return ['is_valid' => empty($errors), 'messages' => $errors];
    }

    private static function hitungMu(object $sub, string $input): float
    {
        if ($sub->tipe_kurva === 'diskrit') {
            return (strtolower(trim($input)) === strtolower(trim($sub->nama_sub_kriteria))) ? 1.0 : 0.0;
        }

        $clean = preg_replace('/[^0-9.]/', '', $input);
        if ($clean === '' || !is_numeric($clean)) return 0.0;

        $x = (float) $clean;
        $a = (float) $sub->batas_bawah;
        $b = (float) $sub->batas_tengah_1;
        $c = (float) $sub->batas_tengah_2;
        $d = (float) $sub->batas_atas;

        return match ($sub->tipe_kurva) {
            'bahu_kiri'  => ($x <= $c) ? 1.0 : (($x >= $d) ? 0.0 : ($d - $x) / ($d - $c)),
            'bahu_kanan' => ($x <= $a) ? 0.0 : (($x >= $b) ? 1.0 : ($x - $a) / ($b - $a)),
            'trapesium'  => ($x <= $a || $x >= $d) ? 0.0 :
                (($x >= $b && $x <= $c) ? 1.0 :
                    (($x < $b) ? ($x - $a) / ($b - $a) : ($d - $x) / ($d - $c))),
            default => 0.0,
        };
    }

    private static function getFallbackK(string $namaSub): float
    {
        $n = strtolower($namaSub);

        return match (true) {
            str_contains($n, 'sangat') || str_contains($n, 'tinggi') || str_contains($n, 'baik') => 1.0,
            str_contains($n, 'sedang') || str_contains($n, 'cukup') => 0.7,
            default => 0.4,
        };
    }

   private static function getSkalaPrioritas(float $z): array
{
    return match (true) {
        $z >= 0.70 => [
            'prioritas' => 'Prioritas I',
            'status' => 'Sangat Layak',
            'interpretasi' => 'Sangat Diprioritaskan'
        ],

        $z >= 0.50 => [
            'prioritas' => 'Prioritas II',
            'status' => 'Layak',
            'interpretasi' => 'Diprioritaskan'
        ],

        $z >= 0.40 => [
            'prioritas' => 'Prioritas III',
            'status' => 'Dipertimbangkan',
            'interpretasi' => 'Perlu Pertimbangan'
        ],

        default => [
            'prioritas' => 'Prioritas IV',
            'status' => 'Ditolak',
            'interpretasi' => 'Belum Memenuhi Secara Optimal'
        ],
    };
}
}
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
                // 🔥 MODIFIKASI: Mu diskrit mengambil nilai konsekuen
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
        // 🔥 RULE BASE AKTIF
        // =========================

        $rules = [];
        $listHimpunan = array_map(fn($f) => $f['himpunan'], $fuzzifikasi);
        $kombinasiRule = self::kombinasi($listHimpunan);

        $totalAlphaZ = 0.0;
        $totalAlpha  = 0.0;
        $maxAlpha    = 0.0;

        foreach ($kombinasiRule as $index => $ruleItems) {
            if (empty($ruleItems)) continue;

            // Operator AND (MIN)
            $alpha = min(array_column($ruleItems, 'mu'));

            if ($alpha <= 0) continue;

            // Identifikasi Rule Naskah
            $z_naskah = self::getNaskahRuleZ($ruleItems, $fuzzifikasi);
            $namaRule = self::getNamaRuleNaskah($ruleItems, $fuzzifikasi) ?? 'R' . ($index + 1);

            $z = $z_naskah ?? (array_sum(array_column($ruleItems, 'z')) / count($ruleItems));

            $rules[] = [
                'rule_id'   => $index + 1,
                'rule'      => $namaRule, 
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

    private static function getNamaRuleNaskah(array $ruleItems, array $fuzzifikasi): ?string
    {
        $c = [];
        foreach ($ruleItems as $idx => $r) {
            $kode = strtoupper($fuzzifikasi[$idx]['kode']);
            $c[$kode] = strtolower($r['nama']);
        }

        $c1 = $c['C1'] ?? ''; $c2 = $c['C2'] ?? ''; $c3 = $c['C3'] ?? '';
        $c4 = $c['C4'] ?? ''; $c5 = $c['C5'] ?? '';

        // Deteksi label sesuai Tabel Naskah (R1-R16)
        if (str_contains($c1, 'sempit') && str_contains($c2, 'tidak') && str_contains($c3, 'baru') && str_contains($c4, 'rendah') && str_contains($c5, 'tidak')) return "R1";
        if (str_contains($c1, 'sempit') && str_contains($c2, 'sewa') && str_contains($c3, 'baru') && str_contains($c4, 'rendah') && str_contains($c5, 'tidak')) return "R2";
        if (str_contains($c1, 'sempit') && str_contains($c2, 'milik') && str_contains($c3, 'lama') && str_contains($c4, 'sedang') && str_contains($c5, 'lengkap')) return "R3";
        if (str_contains($c1, 'sedang') && str_contains($c2, 'sewa') && str_contains($c3, 'lama') && str_contains($c4, 'sedang') && str_contains($c5, 'lengkap')) return "R4";
        if (str_contains($c1, 'sedang') && str_contains($c2, 'milik') && str_contains($c3, 'lama') && str_contains($c4, 'tinggi') && str_contains($c5, 'sangat')) return "R5";
        if (str_contains($c1, 'luas')   && str_contains($c2, 'milik') && str_contains($c3, 'sangat') && str_contains($c4, 'tinggi') && str_contains($c5, 'sangat')) return "R6";
        if (str_contains($c1, 'luas')   && str_contains($c2, 'sewa') && str_contains($c3, 'lama') && str_contains($c4, 'tinggi') && str_contains($c5, 'lengkap')) return "R7";
        if (str_contains($c1, 'luas')   && str_contains($c2, 'tidak') && str_contains($c3, 'baru') && str_contains($c4, 'rendah') && str_contains($c5, 'tidak')) return "R8";
        if (str_contains($c1, 'sedang') && str_contains($c2, 'tidak') && str_contains($c3, 'baru') && str_contains($c4, 'rendah') && str_contains($c5, 'tidak')) return "R9";
        if (str_contains($c1, 'sedang') && str_contains($c2, 'milik') && str_contains($c3, 'lama') && str_contains($c4, 'tinggi') && str_contains($c5, 'lengkap')) return "R10";
        if (str_contains($c1, 'sedang') && str_contains($c2, 'milik') && str_contains($c3, 'baru') && str_contains($c4, 'sedang') && str_contains($c5, 'lengkap')) return "R11";
        if (str_contains($c1, 'luas')   && str_contains($c2, 'sewa') && str_contains($c3, 'baru') && str_contains($c4, 'sedang') && str_contains($c5, 'lengkap')) return "R12";
        if (str_contains($c1, 'sempit') && str_contains($c2, 'milik') && str_contains($c3, 'baru') && str_contains($c4, 'sedang') && str_contains($c5, 'lengkap')) return "R13";
        if (str_contains($c1, 'luas')   && str_contains($c2, 'milik') && str_contains($c3, 'lama') && str_contains($c4, 'sedang') && str_contains($c5, 'sangat')) return "R14";
        if (str_contains($c1, 'sedang') && str_contains($c2, 'sewa') && str_contains($c3, 'lama') && str_contains($c4, 'rendah') && str_contains($c5, 'tidak')) return "R15";
        if (str_contains($c1, 'luas')   && str_contains($c2, 'milik') && str_contains($c3, 'lama') && str_contains($c4, 'tinggi') && str_contains($c5, 'sangat')) return "R16";

        return null;
    }

    private static function getNaskahRuleZ(array $ruleItems, array $fuzzifikasi): ?float
    {
        // Logika ini sama dengan getNaskahRuleZ sebelumnya untuk mendapatkan nilai output Z
        $nama = self::getNamaRuleNaskah($ruleItems, $fuzzifikasi);
        return match($nama) {
            "R1", "R2", "R4", "R8", "R9", "R15" => 0.25,
            "R3", "R11", "R12", "R13"           => 0.50,
            "R5", "R6", "R7", "R10", "R14"      => 0.75,
            "R16"                               => 1.00,
            default                             => null
        };
    }

    private static function hitungMu(object $sub, string $input): float
    {
        if ($sub->tipe_kurva === 'diskrit') {
            if (strtolower(trim($input)) === strtolower(trim($sub->nama_sub_kriteria))) {
                return (float) ($sub->nilai_konsekuen ?? 1.0);
            }
            return 0.0;
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

    public static function hitungSemuaDanRanking(?string $periode = null, ?string $bidang = null): Collection
    {
        $query = Cpcl::where('status', 'terverifikasi');
        if ($periode) $query->whereYear('created_at', $periode);
        if ($bidang)  $query->where('bidang', $bidang);

        $cpclList = $query->get();
        $hasilList = [];

        foreach ($cpclList as $cpcl) {
            try {
                $hasil = self::hitung($cpcl->id);
                $hasilList[] = array_merge(['cpcl_id' => $cpcl->id], $hasil);
            } catch (\Exception $e) {
                Log::error("Skip ID {$cpcl->id}: " . $e->getMessage());
            }
        }

        $ranked = collect($hasilList)->sortByDesc('skor_akhir')->values();

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
                if (is_null($nilai) || $nilai === '') $errors[] = "Cek {$kriteria->kode_kriteria}: Kosong.";
                elseif (!is_numeric($clean)) $errors[] = "Cek {$kriteria->kode_kriteria}: Bukan angka.";
            } else {
                $nilai = DB::table('cpcl_penilaian')->where('cpcl_id', $cpcl_id)->where('kriteria_id', $kriteria->id)->value('nilai');
                if (is_null($nilai) || $nilai === '') $errors[] = "Cek {$kriteria->kode_kriteria}: Belum diisi.";
            }
        }
        return ['is_valid' => empty($errors), 'messages' => $errors];
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
            $z >= 0.81 => ['prioritas' => 'Prioritas I', 'status' => 'Sangat Layak', 'interpretasi' => 'Sangat Diprioritaskan'],
            $z >= 0.70 => ['prioritas' => 'Prioritas II', 'status' => 'Diprioritaskan', 'interpretasi' => 'Diprioritaskan'],
            $z >= 0.55 => ['prioritas' => 'Prioritas III', 'status' => 'Dipertimbangkan', 'interpretasi' => 'Dipertimbangkan'],
            default    => ['prioritas' => 'Prioritas IV', 'status' => 'Tidak Diprioritaskan', 'interpretasi' => 'Tidak Diprioritaskan'],
        };
    }
}
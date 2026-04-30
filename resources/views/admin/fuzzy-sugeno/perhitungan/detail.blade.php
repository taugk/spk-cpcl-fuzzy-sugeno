@extends('admin.layouts.app')

@section('title', 'Laporan Analisis Fuzzy Sugeno')

@section('content')
<div class="content-wrapper">
    <div class="container-xxl container-p-y">

        {{-- ═══════════════════════════════════════════════════════════════
             HEADER LAPORAN
        ════════════════════════════════════════════════════════════════ --}}
        <div class="card mb-4 border-0 shadow-sm no-print">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="fw-bold mb-1">
                        <i class="bx bx-file-find text-success me-2"></i>
                        Laporan Analisis Kelayakan Fuzzy Sugeno
                    </h4>
                    <p class="text-muted mb-0">
                        Subjek:
                        <span class="badge bg-label-dark fs-6">{{ $hasil['cpcl']->nama_kelompok }}</span>
                        <span class="badge bg-label-info ms-2">{{ $hasil['cpcl']->bidang ?? '-' }}</span>
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.perhitungan.index') }}" class="btn btn-outline-secondary">
                        <i class="bx bx-arrow-back me-1"></i> Kembali
                    </a>
                    <button onclick="window.print()" class="btn btn-success">
                        <i class="bx bx-printer me-1"></i> Cetak Laporan
                    </button>
                </div>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════════════════
             STEP 1 — FUZZIFIKASI
             Field yang dipakai dari service:
               $k['kode'], $k['nama'], $k['input'], $k['jenis']
               $k['himpunan']       → himpunan dengan μ > 0 (untuk tabel analisis)
               $k['semua_himpunan'] → semua himpunan       (untuk chart kurva)
               $s['nama'], $s['mu'], $s['tipe'], $s['params']
        ════════════════════════════════════════════════════════════════ --}}
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white border-bottom py-3">
                <h5 class="fw-bold mb-0">
                    <span class="badge bg-success me-2">Step 1</span>
                    Fuzzifikasi — Visualisasi Kurva & Derajat Keanggotaan (\(\mu\))
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0">
                        <thead class="table-light text-center small fw-bold text-uppercase">
                            <tr>
                                <th style="width:32%">Kriteria & Visualisasi Kurva</th>
                                <th>Analisis Himpunan Aktif (\(\mu > 0\))</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($hasil['fuzzifikasi'] as $k)
                            <tr>
                                {{-- Kolom kiri: info kriteria + chart --}}
                                <td class="p-3 bg-light align-top">
                                    <div class="text-center mb-2">
                                        <span class="fw-bold text-dark d-block">
                                            {{ $k['kode'] }} — {{ $k['nama'] }}
                                        </span>
                                        <small class="text-muted">
                                            Input: <strong>{{ $k['input'] }}</strong>
                                            <span class="badge bg-label-secondary ms-1">
                                                {{ $k['jenis'] === 'kontinu' ? 'Kontinu' : 'Diskrit' }}
                                            </span>
                                        </small>
                                    </div>
                                    <div style="height:180px; width:100%;">
                                        <canvas id="chart-{{ $k['kode'] }}"></canvas>
                                    </div>
                                </td>

                                {{-- Kolom kanan: tabel himpunan aktif --}}
                                <td class="p-3 align-top">
                                    <div class="row g-2">
                                        @forelse($k['himpunan'] as $s)
                                        <div class="col-md-6">
                                            <div class="p-3 rounded border border-success bg-label-success shadow-sm">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <small class="fw-bold d-block text-uppercase">
                                                            {{ $s['nama'] }}
                                                        </small>
                                                        <span class="small text-muted">
                                                            Tipe: {{ ucfirst(str_replace('_', ' ', $s['tipe'])) }}
                                                        </span>
                                                    </div>
                                                    <div class="text-end">
                                                        <span class="badge bg-success fs-6">
                                                            \(\mu\) = {{ number_format($s['mu'], 4) }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        @empty
                                        <div class="col-12 text-center py-3">
                                            <span class="text-danger fst-italic">
                                                Tidak ada himpunan fuzzy yang aktif (\(\mu = 0\))
                                            </span>
                                        </div>
                                        @endforelse
                                    </div>

                                    {{-- Himpunan tidak aktif (abu-abu, lipat) --}}
                                    @php
                                        $tidakAktif = array_filter($k['semua_himpunan'], fn($s) => $s['mu'] == 0);
                                    @endphp
                                    @if(!empty($tidakAktif))
                                    <div class="mt-2">
                                        <small class="text-muted">
                                            <i class="bx bx-minus-circle me-1"></i>
                                            Tidak aktif:
                                            @foreach($tidakAktif as $na)
                                                <span class="badge bg-label-secondary">{{ $na['nama'] }}</span>
                                            @endforeach
                                        </small>
                                    </div>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════════════════
             STEP 2 — RULE BASE & FIRING STRENGTH
             Field dari service:
               $rule['rule_id']    → kode rule (R1, R2, …, R16)
               $rule['anteceden']  → [{kriteria, himpunan, mu}, …]
               $rule['alpha']      → firing strength (MIN)
               $rule['k']          → nilai konsekuen naskah
               $rule['alpha_x_k']  → α × k
             Agregat:
               $hasil['sum_alpha']   → Σα
               $hasil['sum_alpha_z'] → Σ(α×k)   ← tetap pakai key lama agar kompatibel
        ════════════════════════════════════════════════════════════════ --}}
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white border-bottom py-3">
                <h5 class="fw-bold mb-0">
                    <span class="badge bg-warning text-dark me-2">Step 2</span>
                    Rule Base & Firing Strength
                </h5>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <thead class="table-light text-center small fw-bold">
                        <tr>
                            <th style="width:6%">Rule</th>
                            <th class="text-start">Anteceden (IF … AND …)</th>
                            <th>\(\alpha\) = MIN</th>
                            <th>\(k\) (Konsekuen)</th>
                            <th>\(\alpha \times k\)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($hasil['rules'] as $rule)
                        <tr class="text-center">
                            <td class="fw-bold text-success">{{ $rule['rule_id'] }}</td>
                            <td class="text-start">
                                @foreach($rule['anteceden'] as $i => $ant)
                                    @if($i > 0)
                                        <span class="badge bg-label-dark small mx-1">AND</span>
                                    @endif
                                    <span class="badge bg-label-success">
                                        {{ $ant['kriteria'] }} = {{ $ant['himpunan'] }}
                                        <span class="opacity-75">(μ={{ number_format($ant['mu'], 4) }})</span>
                                    </span>
                                @endforeach
                            </td>
                            <td>
                                <span class="badge bg-success fs-6">
                                    {{ number_format($rule['alpha'], 4) }}
                                </span>
                            </td>
                            <td>{{ number_format($rule['k'], 2) }}</td>
                            <td class="fw-bold text-dark">
                                {{ number_format($rule['alpha_x_k'], 4) }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-danger fst-italic">
                                <i class="bx bx-error-circle me-1"></i>
                                Tidak ada rule yang aktif untuk kombinasi input ini.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                    @if(count($hasil['rules']) > 0)
                    <tfoot class="table-light fw-bold text-center">
                        <tr>
                            <td colspan="2" class="text-end text-uppercase pe-3">
                                Total (\(\Sigma\)):
                            </td>
                            <td class="text-success">{{ number_format($hasil['sum_alpha'], 4) }}</td>
                            <td>—</td>
                            <td class="text-primary">{{ number_format($hasil['sum_alpha_z'], 4) }}</td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════════════════
             STEP 3 — ANALISIS INFERENSI (Fungsi Implikasi MIN)
             Menampilkan detail perhitungan α tiap rule secara visual.
        ════════════════════════════════════════════════════════════════ --}}
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white border-bottom py-3">
                <h5 class="fw-bold mb-0">
                    <span class="badge bg-info me-2">Step 3</span>
                    Analisis Inferensi — Aplikasi Fungsi Implikasi MIN
                </h5>
            </div>
            <div class="card-body">
                @if(count($hasil['rules']) > 0)
                <div class="row g-3">
                    @foreach($hasil['rules'] as $rule)
                    <div class="col-md-6 col-lg-4">
                        <div class="border rounded p-3 bg-light h-100">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="fw-bold text-success fs-6">{{ $rule['rule_id'] }}</span>
                                <span class="badge bg-white text-dark border shadow-sm">
                                    \(\alpha = {{ number_format($rule['alpha'], 4) }}\)
                                </span>
                            </div>
                            <div class="small">
                                <div class="mb-1 text-muted fst-italic">Operator AND → MIN:</div>
                                <div class="p-2 bg-white border rounded text-dark text-center mb-2 font-monospace">
                                    \(\alpha_{ {{ $rule['rule_id'] }} } =\min(\)
                                    @php
                                        $muValues = array_map(
                                            fn($ant) => number_format($ant['mu'], 4),
                                            $rule['anteceden']
                                        );
                                    @endphp
                                    {{ implode(',\ ', $muValues) }}
                                    \()= {{ number_format($rule['alpha'], 4) }}\)
                                </div>
                                <div class="mt-2 d-flex justify-content-between align-items-center border-top pt-2">
                                    <small class="text-muted">Konsekuen \((k)\):</small>
                                    <span class="fw-bold">{{ number_format($rule['k'], 2) }}</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">\(\alpha \times k\):</small>
                                    <span class="fw-bold text-success">
                                        {{ number_format($rule['alpha_x_k'], 4) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="alert alert-warning mb-0">
                    <i class="bx bx-error me-1"></i>
                    Tidak ada rule yang terpicu. Periksa kembali data input CPCL.
                </div>
                @endif
            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════════════════
             STEP 4 — DEFUZZIFIKASI & HASIL AKHIR
             Field dari service:
               $hasil['sum_alpha_z'] → Σ(α×k)
               $hasil['sum_alpha']   → Σα
               $hasil['z']           → nilai Z akhir
               $hasil['skor_akhir']  → Z × 100 (%)
               $hasil['skala_prioritas']  → "Prioritas I" … "Prioritas IV"
               $hasil['status_kelayakan'] → "Sangat Layak" / "Layak" / …
               $hasil['interpretasi']     → keterangan
             Per rule:
               $rule['rule_id'], $rule['alpha'], $rule['k'], $rule['alpha_x_k']
        ════════════════════════════════════════════════════════════════ --}}
        <div class="card shadow-sm border-0 mb-4 border-top border-success border-3">
            <div class="card-header bg-white border-bottom py-3">
                <h5 class="fw-bold mb-0">
                    <span class="badge bg-success me-2">Step 4</span>
                    Defuzzifikasi (Weighted Average) & Hasil Akhir
                </h5>
            </div>
            <div class="card-body py-4">

                {{-- Rumus + Hasil Utama --}}
                <div class="row align-items-center mb-5">
                    <div class="col-md-5 text-center border-end">
                        <h6 class="text-uppercase fw-bold text-muted small mb-3">
                            Metode Rata-Rata Terbobot (Weighted Average)
                        </h6>
                        <div class="py-2">
                            \[z^* = \frac{\displaystyle\sum_{i}(\alpha_i \cdot k_i)}{\displaystyle\sum_{i} \alpha_i}\]
                            <div class="mt-3 fs-5">
                                \(z^* = \dfrac{ {{ number_format($hasil['sum_alpha_z'], 4) }} }
                                              { {{ number_format($hasil['sum_alpha'], 4) }} }\)
                            </div>
                            <h3 class="fw-bold text-dark mt-3 mb-0">
                                \(z^* = {{ number_format($hasil['z'], 4) }}\)
                            </h3>
                        </div>
                    </div>

                    <div class="col-md-7 ps-md-5 mt-4 mt-md-0">
                        @php
                            $isLayak = str_contains($hasil['status_kelayakan'], 'Layak');
                            $cardClass = match($hasil['skala_prioritas']) {
                                'Prioritas I'  => 'bg-label-success border-success',
                                'Prioritas II' => 'bg-label-info border-info',
                                'Prioritas III'=> 'bg-label-warning border-warning',
                                default        => 'bg-label-danger border-danger',
                            };
                            $textClass = match($hasil['skala_prioritas']) {
                                'Prioritas I'  => 'text-success',
                                'Prioritas II' => 'text-info',
                                'Prioritas III'=> 'text-warning',
                                default        => 'text-danger',
                            };
                        @endphp
                        <div class="p-4 rounded-3 border {{ $cardClass }}">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h5 class="fw-bold mb-1 {{ $textClass }}">
                                        {{ $hasil['skala_prioritas'] }}
                                    </h5>
                                    <p class="mb-1 text-dark fst-italic">
                                        {{ $hasil['interpretasi'] }}
                                    </p>
                                    <span class="badge fs-6 {{ $isLayak ? 'bg-success' : 'bg-danger' }}">
                                        {{ $hasil['status_kelayakan'] }}
                                    </span>
                                </div>
                                <div class="text-end ms-3">
                                    <div class="display-5 fw-bold mb-0 {{ $textClass }}">
                                        {{ number_format($hasil['skor_akhir'], 2) }}%
                                    </div>
                                    <small class="fw-bold text-uppercase text-muted">
                                        Skor Kelayakan Akhir
                                    </small>
                                </div>
                            </div>
                        </div>

                        {{-- Skala prioritas mini --}}
                        <div class="mt-3 small">
                            <div class="d-flex gap-1 flex-wrap">
                                @foreach([
                                    ['Prioritas I',   '0.81–1.00', 'bg-success',  'Sangat Diprioritaskan'],
                                    ['Prioritas II',  '0.61–0.80', 'bg-info',     'Diprioritaskan'],
                                    ['Prioritas III', '0.41–0.60', 'bg-warning',  'Dipertimbangkan'],
                                    ['Prioritas IV',  '≤ 0.40',    'bg-danger',   'Tidak Diprioritaskan'],
                                ] as [$p, $r, $cls, $desc])
                                <span class="badge {{ $cls }} {{ $hasil['skala_prioritas'] === $p ? 'opacity-100 border border-dark' : 'opacity-50' }}"
                                      title="{{ $r }} — {{ $desc }}">
                                    {{ $p }} ({{ $r }})
                                </span>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Tabel Rincian Kontribusi Rule --}}
                <div class="mt-4 pt-4 border-top">
                    <h6 class="fw-bold text-uppercase text-muted mb-3">
                        <i class="bx bx-calculator me-1"></i>
                        Rincian Nilai Kontribusi Rule
                    </h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped border">
                            <thead class="table-dark">
                                <tr class="text-center">
                                    <th>Kode Rule</th>
                                    <th>\(\alpha\) (Firing Strength)</th>
                                    <th>\(k\) (Konsekuen)</th>
                                    <th>\(\alpha \cdot k\) (Bobot)</th>
                                    <th>Kontribusi (%)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($hasil['rules'] as $rule)
                                <tr class="text-center">
                                    <td class="fw-bold text-success">{{ $rule['rule_id'] }}</td>
                                    <td>{{ number_format($rule['alpha'], 4) }}</td>
                                    <td>{{ number_format($rule['k'], 2) }}</td>
                                    <td>{{ number_format($rule['alpha_x_k'], 4) }}</td>
                                    <td>
                                        @if($hasil['sum_alpha_z'] > 0)
                                            {{ number_format(($rule['alpha_x_k'] / $hasil['sum_alpha_z']) * 100, 1) }}%
                                        @else
                                            0%
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light fw-bold text-center">
                                <tr>
                                    <td>TOTAL</td>
                                    <td class="text-success">{{ number_format($hasil['sum_alpha'], 4) }}</td>
                                    <td>—</td>
                                    <td class="text-primary">{{ number_format($hasil['sum_alpha_z'], 4) }}</td>
                                    <td>100%</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="alert bg-label-secondary mt-3 mb-0 small">
                        <strong>Keterangan:</strong>
                        Nilai \(z^* = {{ number_format($hasil['z'], 4) }}\) dikonversi ke skala persentase menjadi
                        <strong>{{ number_format($hasil['skor_akhir'], 2) }}%</strong>
                        sebagai dasar penentuan tingkat kelayakan sesuai Tabel Skala Prioritas.
                    </div>
                </div>

            </div>
        </div>

    </div>{{-- /container --}}
</div>{{-- /content-wrapper --}}

<style>
    .bg-label-success  { background: #e8fadf !important; color: #2e7d32 !important; }
    .bg-label-info     { background: #d7f5fc !important; color: #03c3ec !important; }
    .bg-label-warning  { background: #fff8e1 !important; color: #e65100 !important; }
    .bg-label-danger   { background: #ffe5e5 !important; color: #c62828 !important; }
    .bg-label-secondary{ background: #f0f2f4 !important; color: #8592a3 !important; }
    .bg-label-dark     { background: #e4e6ea !important; color: #384551 !important; }
    .font-monospace    { font-family: 'SFMono-Regular', Menlo, monospace; font-size: 0.82rem; }
    @media print {
        .no-print, .btn, .layout-navbar, .layout-menu { display: none !important; }
        .content-wrapper { margin: 0 !important; padding: 0 !important; }
        .card { border: 1px solid #ddd !important; box-shadow: none !important; page-break-inside: avoid; }
        canvas { max-height: 150px !important; }
    }
</style>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-annotation@2.1.0"></script>

{{-- MathJax --}}
<script>
    window.MathJax = {
        tex: {
            inlineMath:  [['$', '$'], ['\\(', '\\)']],
            displayMath: [['$$', '$$'], ['\\[', '\\]']],
            processEscapes: true
        }
    };
</script>
<script id="MathJax-script" async
    src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js">
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {

    // ─────────────────────────────────────────────────────────────────────────
    // Warna kurva per indeks himpunan
    // ─────────────────────────────────────────────────────────────────────────
    const COLORS = ['#2e7d32', '#43a047', '#7cb342', '#aed581', '#c5e1a5'];

    // ─────────────────────────────────────────────────────────────────────────
    // Helper: hasilkan titik-titik (x, y) untuk satu himpunan fuzzy
    // Sesuai tipe kurva service: bahu_kiri | bahu_kanan | segitiga | trapesium | diskrit
    // ─────────────────────────────────────────────────────────────────────────
    function buildPoints(p, maxX) {
        const { tipe, a, b, c, d } = p;
        switch (tipe) {
            case 'bahu_kiri':
                // μ=1 saat x≤a, turun ke 0 di b  → sesuai naskah: Sempit (a=1.5, b=3.5)
                return [{ x: 0, y: 1 }, { x: a, y: 1 }, { x: b, y: 0 }, { x: maxX, y: 0 }];
            case 'bahu_kanan':
                // μ=0 saat x≤a, naik ke 1 di b → sesuai naskah: Luas (a=5, b=7)
                return [{ x: 0, y: 0 }, { x: a, y: 0 }, { x: b, y: 1 }, { x: maxX, y: 1 }];
            case 'segitiga':
            case 'trapesium':
                // naik a→b, puncak b–c, turun c→d
                return [{ x: a, y: 0 }, { x: b, y: 1 }, { x: c, y: 1 }, { x: d, y: 0 }];
            default:
                return [];
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Render chart tiap kriteria dari data Blade
    // Menggunakan $k['semua_himpunan'] agar kurva tampil lengkap
    //   (termasuk himpunan yang tidak aktif pada input saat ini)
    // ─────────────────────────────────────────────────────────────────────────
    @foreach($hasil['fuzzifikasi'] as $k)
    (function () {
        const ctx = document.getElementById('chart-{{ $k['kode'] }}');
        if (!ctx) return;

        // Semua himpunan: aktif + tidak aktif
        const semuaHimpunan = @json($k['semua_himpunan']);
        const inputVal      = @json($k['input']);
        const jenis         = @json($k['jenis']);

        // ── DISKRIT: bar chart ────────────────────────────────────────────
        if (jenis === 'diskrit') {
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: semuaHimpunan.map(h => h.nama),
                    datasets: [{
                        data: semuaHimpunan.map(h => h.mu),
                        backgroundColor: semuaHimpunan.map((h, i) =>
                            h.mu > 0 ? COLORS[i % COLORS.length] : '#e0e0e0'
                        ),
                        borderRadius: 4,
                        barThickness: 32,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { beginAtZero: true, max: 1.1, ticks: { stepSize: 0.2 } },
                        x: { grid: { display: false } }
                    },
                    plugins: { legend: { display: false } }
                }
            });
            return;
        }

        // ── KONTINU: line chart ───────────────────────────────────────────
        // Hitung maxX dari semua batas parameter
        let maxX = 0;
        semuaHimpunan.forEach(h => {
            if (h.params) {
                maxX = Math.max(maxX, h.params.a, h.params.b, h.params.c, h.params.d);
            }
        });
        maxX = maxX > 0 ? maxX * 1.15 : 100;

        // Nilai input crisp (untuk garis vertikal anotasi)
        const xInput = parseFloat(String(inputVal).replace(/[^0-9.]/g, '')) || null;

        const datasets = semuaHimpunan
            .filter(h => h.tipe !== 'diskrit' && h.params)
            .map((h, i) => ({
                label: h.nama,
                data: buildPoints(h.params, maxX),
                borderColor: h.mu > 0 ? COLORS[i % COLORS.length] : '#bdbdbd',
                backgroundColor: 'transparent',
                borderWidth: h.mu > 0 ? 2.5 : 1.2,
                borderDash: h.mu > 0 ? [] : [4, 3],
                pointRadius: 0,
                tension: 0,
            }));

        // Anotasi garis vertikal pada nilai input
        const annotations = {};
        if (xInput !== null && !isNaN(xInput)) {
            annotations['inputLine'] = {
                type: 'line',
                xMin: xInput,
                xMax: xInput,
                borderColor: '#e53935',
                borderWidth: 2,
                borderDash: [5, 3],
                label: {
                    display: true,
                    content: `x = ${xInput}`,
                    position: 'start',
                    color: '#e53935',
                    font: { size: 10, weight: 'bold' },
                    backgroundColor: 'rgba(255,255,255,0.8)',
                    padding: 3,
                }
            };
        }

        new Chart(ctx, {
            type: 'line',
            data: { datasets },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: { type: 'linear', min: 0, max: maxX, display: true,
                         title: { display: true, text: 'Nilai', font: { size: 10 } } },
                    y: { min: 0, max: 1.2, display: true,
                         title: { display: true, text: 'μ', font: { size: 10 } },
                         ticks: { stepSize: 0.2 } }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom',
                        labels: { boxWidth: 12, font: { size: 10 }, padding: 8 }
                    },
                    annotation: { annotations }
                }
            }
        });
    })();
    @endforeach

});
</script>
@endpush

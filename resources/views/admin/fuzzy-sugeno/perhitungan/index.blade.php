@extends('admin.layouts.app')

@section('title', 'Laporan Analisis Fuzzy Sugeno')

@section('content')
<div class="content-wrapper">
    <div class="container-xxl container-p-y">

        {{-- HEADER --}}
        <div class="card mb-4 border-0 shadow-sm no-print">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="fw-bold mb-1">
                        <i class="bx bx-file-find text-primary me-2"></i>Hasil Analisis Kelayakan
                    </h4>
                    <p class="text-muted mb-0">
                        Subjek: <span class="badge bg-label-primary fs-6">{{ $hasil['cpcl']->nama_kelompok }}</span>
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.cpcl.index') }}" class="btn btn-outline-secondary">
                        <i class="bx bx-arrow-back me-1"></i> Kembali
                    </a>
                    <button onclick="window.print()" class="btn btn-primary">
                        <i class="bx bx-printer me-1"></i> Cetak Laporan
                    </button>
                </div>
            </div>
        </div>

        {{-- MATHJAX --}}
        <script src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-svg.js"></script>
        <script>
            window.MathJax = {
                tex: {
                    inlineMath: [['$', '$'], ['\\(', '\\)']],
                    displayMath: [['$$', '$$'], ['\\[', '\\]']]
                },
                svg: { fontCache: 'global' }
            };
        </script>

        {{-- ================================================================ --}}
        {{-- STEP 1: FUZZIFIKASI                                              --}}
        {{-- ================================================================ --}}
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white border-bottom py-3">
                <h5 class="fw-bold mb-0">
                    <span class="badge bg-primary me-2">Step 1</span> Fuzzifikasi — Derajat Keanggotaan (μ)
                </h5>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <thead class="table-light text-center">
                        <tr class="text-uppercase small fw-bold">
                            <th style="width: 18%">Kriteria & Input</th>
                            <th>Himpunan Fuzzy & Fungsi Keanggotaan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($hasil['fuzzifikasi'] as $k)
                        <tr>
                            {{-- Kolom kiri: info kriteria --}}
                            <td class="text-center bg-light">
                                <span class="fw-bold d-block text-primary fs-6">{{ $k['kode'] }}</span>
                                <small class="text-dark d-block mb-2">{{ $k['nama'] }}</small>

                                {{-- Jenis + sumber data --}}
                                <div class="mb-2">
                                    @if($k['jenis'] === 'kontinu')
                                        <span class="badge bg-label-info">Kontinu</span>
                                        <div class="mt-1" style="font-size:0.7rem; color:#6c757d;">
                                            <i class="bx bx-data"></i> dari kolom: <code>{{ $k['mapping_field'] }}</code>
                                        </div>
                                    @else
                                        <span class="badge bg-label-warning">Diskrit</span>
                                        <div class="mt-1" style="font-size:0.7rem; color:#6c757d;">
                                            <i class="bx bx-edit"></i> input penilaian
                                        </div>
                                    @endif
                                </div>

                                {{-- Nilai input --}}
                                <div class="px-2 py-2 rounded bg-white border small">
                                    @if($k['jenis'] === 'kontinu')
                                        <span class="text-muted d-block" style="font-size:0.7rem;">Nilai riil:</span>
                                        <span class="fw-bold text-primary fs-6">{{ $k['x'] ?? '-' }}</span>
                                    @else
                                        <span class="text-muted d-block" style="font-size:0.7rem;">Pilihan:</span>
                                        <span class="fw-bold text-primary">{{ $k['input'] ?: '-' }}</span>
                                    @endif
                                </div>
                            </td>

                            {{-- Kolom kanan: tiap himpunan (key: 'himpunan') --}}
                            <td class="p-3">
                                <div class="row g-3">
                                    @foreach($k['himpunan'] as $s)
                                    @php
                                        $isAktif = $s['mu'] > 0;
                                    @endphp
                                    <div class="col-12 col-md-4">
                                        <div class="p-3 rounded border h-100
                                            {{ $isAktif ? 'border-success border-2 bg-label-success' : 'border-light bg-light' }}">

                                            {{-- Header himpunan --}}
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="fw-bold small text-uppercase">{{ $s['nama'] }}</span>
                                                <span class="badge {{ $isAktif ? 'bg-success' : 'bg-secondary' }} fs-6">
                                                    μ = {{ number_format($s['mu'], 4) }}
                                                </span>
                                            </div>

                                            {{-- Formula fungsi keanggotaan --}}
                                            <div class="math-container small text-dark" style="overflow-x:auto; font-size:0.78rem;">
                                                @if($s['tipe'] === 'bahu_kiri')
                                                    $$ \mu(x) = \begin{cases}
                                                        1, & x \le {{ $s['c'] }} \\
                                                        \dfrac{ {{ $s['d'] }} - x }{ {{ $s['d'] }} - {{ $s['c'] }} }, & {{ $s['c'] }} < x < {{ $s['d'] }} \\
                                                        0, & x \ge {{ $s['d'] }}
                                                    \end{cases} $$
                                                @elseif($s['tipe'] === 'trapesium')
                                                    $$ \mu(x) = \begin{cases}
                                                        \dfrac{x - {{ $s['a'] }}}{ {{ $s['b'] }} - {{ $s['a'] }} }, & {{ $s['a'] }} < x < {{ $s['b'] }} \\
                                                        1, & {{ $s['b'] }} \le x \le {{ $s['c'] }} \\
                                                        \dfrac{ {{ $s['d'] }} - x }{ {{ $s['d'] }} - {{ $s['c'] }} }, & {{ $s['c'] }} < x < {{ $s['d'] }}
                                                    \end{cases} $$
                                                @elseif($s['tipe'] === 'bahu_kanan')
                                                    $$ \mu(x) = \begin{cases}
                                                        0, & x \le {{ $s['a'] }} \\
                                                        \dfrac{x - {{ $s['a'] }}}{ {{ $s['b'] }} - {{ $s['a'] }} }, & {{ $s['a'] }} < x < {{ $s['b'] }} \\
                                                        1, & x \ge {{ $s['b'] }}
                                                    \end{cases} $$
                                                @else
                                                    <p class="mb-0 text-muted fst-italic small">
                                                        Diskrit: μ = 1 jika input = "<strong>{{ $s['nama'] }}</strong>", μ = 0 jika tidak.
                                                    </p>
                                                @endif
                                            </div>

                                            {{-- Konsekuen --}}
                                            <div class="mt-2 pt-2 border-top text-muted" style="font-size:0.75rem;">
                                                <i class="bx bx-tag-alt me-1"></i>
                                                Konsekuen: <strong class="text-dark">k = {{ number_format($s['k'], 2) }}</strong>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ================================================================ --}}
        {{-- STEP 2 & 3: RULE BASE + FIRING STRENGTH                         --}}
        {{-- ================================================================ --}}
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white border-bottom py-3">
                <h5 class="fw-bold mb-0">
                    <span class="badge bg-warning text-dark me-2">Step 2 & 3</span>
                    Rule Base & Firing Strength (α = MIN)
                </h5>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <thead class="table-light text-center">
                        <tr class="text-uppercase small fw-bold">
                            <th style="width: 8%">Rule</th>
                            <th>Anteceden (IF ... AND ...)</th>
                            <th style="width: 12%">α = MIN(μ)</th>
                            <th style="width: 12%">z Rule (avg k)</th>
                            <th style="width: 14%">α × z</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($hasil['rules'] as $rule)
                        <tr>
                            <td class="text-center fw-bold text-primary">{{ $rule['rule_id'] }}</td>

                            {{-- Anteceden --}}
                            <td>
                                <div class="d-flex flex-wrap gap-2">
                                    @foreach($rule['anteceden'] as $i => $ant)
                                        @if($i > 0)
                                            <span class="badge bg-label-dark align-self-center">AND</span>
                                        @endif
                                        <span class="badge bg-label-primary py-2 px-3">
                                            <span class="text-muted small">{{ $ant['kriteria'] }}</span>
                                            = {{ $ant['himpunan'] }}
                                            <span class="ms-1 text-primary">(μ={{ number_format($ant['mu'], 4) }}, k={{ number_format($ant['k'], 2) }})</span>
                                        </span>
                                    @endforeach
                                </div>
                            </td>

                            {{-- Alpha --}}
                            <td class="text-center">
                                <span class="badge bg-success fs-6 py-2 px-3">
                                    {{ number_format($rule['alpha'], 4) }}
                                </span>
                                <div class="text-muted" style="font-size:0.7rem;">
                                    MIN({{ implode(', ', array_map(fn($a) => number_format($a['mu'], 4), $rule['anteceden'])) }})
                                </div>
                            </td>

                            {{-- z Rule --}}
                            <td class="text-center fw-bold">{{ number_format($rule['z_rule'], 4) }}</td>

                            {{-- alpha × z --}}
                            <td class="text-center fw-bold text-primary">{{ number_format($rule['alpha_x_z'], 4) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                <i class="bx bx-error-circle fs-4 d-block mb-2"></i>
                                Tidak ada rule yang terbentuk. Periksa nilai input dan konfigurasi sub kriteria.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                    @if(count($hasil['rules']) > 0)
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td colspan="2" class="text-end">Jumlah (Σ):</td>
                            <td class="text-center text-success">{{ number_format($hasil['sum_alpha'], 4) }}</td>
                            <td></td>
                            <td class="text-center text-primary">{{ number_format($hasil['sum_alpha_z'], 4) }}</td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>

        {{-- ================================================================ --}}
        {{-- STEP 4: DEFUZZIFIKASI + HASIL AKHIR                             --}}
        {{-- ================================================================ --}}
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white border-bottom py-3">
                <h5 class="fw-bold mb-0">
                    <span class="badge bg-danger me-2">Step 4</span>
                    Defuzzifikasi — Weighted Average Sugeno
                </h5>
            </div>
            <div class="card-body">
                <div class="row g-4 align-items-center">

                    {{-- Formula --}}
                    <div class="col-12 col-md-5">
                        <div class="p-4 bg-light rounded border">
                            <p class="text-muted small mb-2 fw-bold text-uppercase">Formula Weighted Average:</p>
                            <div class="text-center">
                                $$ z^* = \frac{\sum_{i=1}^{n} \alpha_i \cdot z_i}{\sum_{i=1}^{n} \alpha_i} $$
                            </div>
                            <hr>
                            <div class="text-center">
                                $$ z^* = \frac{ {{ number_format($hasil['sum_alpha_z'], 4) }} }{ {{ number_format($hasil['sum_alpha'], 4) }} } = {{ number_format($hasil['z'], 4) }} $$
                            </div>
                        </div>
                    </div>

                    {{-- Hasil --}}
                    <div class="col-12 col-md-7">
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="p-3 border rounded text-center bg-label-primary">
                                    <small class="text-muted d-block mb-1">Σ (α × z)</small>
                                    <h4 class="fw-bold text-primary mb-0">{{ number_format($hasil['sum_alpha_z'], 4) }}</h4>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3 border rounded text-center bg-label-info">
                                    <small class="text-muted d-block mb-1">Σ α</small>
                                    <h4 class="fw-bold text-info mb-0">{{ number_format($hasil['sum_alpha'], 4) }}</h4>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3 border rounded text-center bg-label-success">
                                    <small class="text-muted d-block mb-1">Nilai z* (crisp)</small>
                                    <h4 class="fw-bold text-success mb-0">{{ number_format($hasil['z'], 4) }}</h4>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3 border rounded text-center bg-label-warning">
                                    <small class="text-muted d-block mb-1">Skor Akhir</small>
                                    <h4 class="fw-bold text-warning mb-0">{{ $hasil['skor_akhir'] }}%</h4>
                                </div>
                            </div>

                            {{-- Keputusan --}}
                            <div class="col-12">
                                <div class="p-3 rounded border border-2
                                    {{ in_array($hasil['status_kelayakan'], ['Layak']) ? 'border-success bg-label-success' : 'border-danger bg-label-danger' }}">
                                    <div class="row align-items-center">
                                        <div class="col">
                                            <small class="text-muted d-block">Skala Prioritas</small>
                                            <h5 class="fw-bold mb-0">{{ $hasil['skala_prioritas'] }}</h5>
                                            <small class="fst-italic">{{ $hasil['interpretasi'] }}</small>
                                        </div>
                                        <div class="col-auto">
                                            <span class="badge fs-5 py-2 px-4
                                                {{ $hasil['status_kelayakan'] === 'Layak' ? 'bg-success' : 'bg-danger' }}">
                                                {{ strtoupper($hasil['status_kelayakan']) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- REFERENSI SKALA --}}
        <div class="card mt-2 border-0 shadow-sm no-print">
            <div class="card-body">
                <h6 class="fw-bold mb-3"><i class="bx bx-info-circle me-1"></i> Referensi Skala Prioritas (z*)</h6>
                <div class="row text-center g-3">
                    <div class="col-6 col-md-3">
                        <div class="p-2 border rounded border-success">
                            <small class="text-muted d-block">z* > 0.80</small>
                            <span class="fw-bold text-success">Prioritas I</span>
                            <small class="text-muted d-block">Sangat Diprioritaskan</small>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-2 border rounded border-primary">
                            <small class="text-muted d-block">0.60 < z* ≤ 0.80</small>
                            <span class="fw-bold text-primary">Prioritas II</span>
                            <small class="text-muted d-block">Diprioritaskan</small>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-2 border rounded border-warning">
                            <small class="text-muted d-block">0.40 < z* ≤ 0.60</small>
                            <span class="fw-bold text-warning">Prioritas III</span>
                            <small class="text-muted d-block">Dipertimbangkan</small>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-2 border rounded border-danger">
                            <small class="text-muted d-block">z* ≤ 0.40</small>
                            <span class="fw-bold text-danger">Prioritas IV</span>
                            <small class="text-muted d-block">Tidak Diprioritaskan</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<style>
    .bg-light-alt { background-color: #f8f9fa; }
    .italic { font-style: italic; }

    @media print {
        .no-print, .btn, .layout-navbar, .layout-menu { display: none !important; }
        .content-wrapper { margin: 0 !important; padding: 0 !important; }
        .card { border: 1px solid #eee !important; box-shadow: none !important; page-break-inside: avoid; }
        .table { width: 100% !important; border-collapse: collapse !important; }
        body { background: white !important; }
    }
</style>
@endsection
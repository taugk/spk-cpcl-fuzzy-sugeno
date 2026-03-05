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
                        <i class="bx bx-file-find text-primary me-2"></i>Laporan Analisis Kelayakan Fuzzy Sugeno
                    </h4>
                    <p class="text-muted mb-0">
                        Subjek: <span class="badge bg-label-primary fs-6">{{ $hasil['cpcl']->nama_kelompok }}</span>
                        <span class="badge bg-label-info ms-2">{{ $hasil['cpcl']->bidang ?? '-' }}</span>
                    </p>
                </div>
                <div class="d-flex gap-2">
                    @if(url()->previous() && url()->previous() !== url()->current())
                        <a href="{{ url()->previous() }}" class="btn btn-outline-secondary">
                            <i class="bx bx-arrow-back me-1"></i> Kembali
                        </a>
                    @else
                        <a href="{{ route('admin.perhitungan.index') }}" class="btn btn-outline-secondary">
                            <i class="bx bx-arrow-back me-1"></i> Kembali ke Ranking
                        </a>
                    @endif
                    <button onclick="window.print()" class="btn btn-primary">
                        <i class="bx bx-printer me-1"></i> Cetak Laporan
                    </button>
                </div>
            </div>
        </div>

        {{-- MATHJAX untuk formula --}}
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
                    <span class="badge bg-primary me-2">Step 1</span> 
                    Fuzzifikasi — Derajat Keanggotaan (μ)
                </h5>
                <small class="text-muted d-block mt-2">
                    Konversi nilai input krisp menjadi derajat keanggotaan fuzzy untuk setiap himpunan fuzzy
                </small>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <thead class="table-light text-center">
                        <tr class="text-uppercase small fw-bold">
                            <th style="width: 18%">Kriteria & Input</th>
                            <th>Himpunan Fuzzy & Derajat Keanggotaan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($hasil['fuzzifikasi'] as $k)
                        <tr>
                            {{-- Kolom kiri: info kriteria --}}
                            <td class="text-center bg-light">
                                <span class="fw-bold d-block text-primary fs-6">{{ $k['kode'] }}</span>
                                <small class="text-dark d-block mb-2">{{ $k['nama'] }}</small>

                                {{-- Nilai input --}}
                                <div class="px-2 py-2 rounded bg-white border small">
                                    <span class="text-muted d-block" style="font-size:0.7rem;">Nilai Input:</span>
                                    
                                    {{-- ✅ FIX v2.0: Tampilkan input dengan fallback handling --}}
                                    @if($k['input'] === '0' || empty($k['input']))
                                        <span class="fw-bold text-danger fs-6" title="Data kosong/tidak diisi">
                                            0 (Tidak Terisi)
                                        </span>
                                    @else
                                        <span class="fw-bold text-primary fs-6">{{ $k['input'] }}</span>
                                    @endif
                                </div>
                            </td>

                            {{-- Kolom kanan: tiap himpunan aktif --}}
                            <td class="p-3">
                                <div class="row g-3">
                                    @forelse($k['himpunan'] as $s)
                                    @php
                                        // ✅ FIX v2.0: Hanya himpunan aktif (μ > 0) yang ditampilkan
                                        $isAktif = $s['mu'] > 0;
                                    @endphp
                                    <div class="col-12 col-md-4">
                                        <div class="p-3 rounded border h-100 {{ $isAktif ? 'border-success border-2 bg-label-success' : 'border-light bg-light' }}">

                                            {{-- Header himpunan --}}
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="fw-bold small text-uppercase">{{ $s['nama'] }}</span>
                                                <span class="badge {{ $isAktif ? 'bg-success' : 'bg-secondary' }} fs-6">
                                                    μ = {{ number_format($s['mu'], 4) }}
                                                </span>
                                            </div>

                                            {{-- Status aktif/tidak aktif --}}
                                            <div class="small {{ $isAktif ? 'text-success' : 'text-muted' }} mb-2">
                                                @if($isAktif)
                                                    <i class="bx bx-check-circle me-1"></i> <strong>Aktif</strong>
                                                @else
                                                    <i class="bx bx-x-circle me-1"></i> <strong>Tidak Aktif</strong>
                                                @endif
                                            </div>

                                            {{-- Konsekuen (nilai k untuk Sugeno Orde Nol) --}}
                                            <div class="mt-2 pt-2 border-top text-muted" style="font-size:0.75rem;">
                                                <i class="bx bx-tag-alt me-1"></i>
                                                Konsekuen: <strong class="text-dark">k = {{ number_format($s['k'], 2) }}</strong>
                                            </div>
                                        </div>
                                    </div>
                                    @empty
                                    <div class="col-12">
                                        <p class="text-muted fst-italic small mb-0">
                                            <i class="bx bx-error-circle me-1"></i>
                                            Tidak ada himpunan fuzzy aktif (semua μ = 0)
                                        </p>
                                    </div>
                                    @endforelse
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="2" class="text-center text-muted py-4">
                                <i class="bx bx-error-circle fs-4 d-block mb-2"></i>
                                Tidak ada data fuzzifikasi. Periksa konfigurasi kriteria.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-light text-muted small">
                <i class="bx bx-info-circle me-1"></i>
                <strong>Catatan:</strong> Hanya himpunan dengan derajat keanggotaan (μ) > 0 yang ditampilkan 
                dan digunakan dalam evaluasi rule berikutnya (filter v2.0).
            </div>
        </div>

        {{-- ================================================================ --}}
        {{-- STEP 2 & 3: RULE BASE + FIRING STRENGTH                         --}}
        {{-- ================================================================ --}}
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white border-bottom py-3">
                <h5 class="fw-bold mb-0">
                    <span class="badge bg-warning text-dark me-2">Step 2 & 3</span>
                    Rule Base & Firing Strength
                </h5>
                <small class="text-muted d-block mt-2">
                    Evaluasi semua kombinasi rule (cartesian product) dari himpunan fuzzy aktif.
                    α = MIN(μ) adalah nilai firing strength (t-norm Mamdani).
                </small>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <thead class="table-light text-center">
                        <tr class="text-uppercase small fw-bold">
                            <th style="width: 8%">Rule</th>
                            <th>Anteceden (IF ... AND ...)</th>
                            <th style="width: 12%">
                                α = MIN(μ)
                                <br><small class="fw-normal">(Fire Strength)</small>
                            </th>
                            <th style="width: 12%">
                                z Rule (avg k)
                                <br><small class="fw-normal">(Sugeno Order 0)</small>
                            </th>
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
                                            <span class="badge bg-label-dark align-self-center px-2">AND</span>
                                        @endif
                                        <span class="badge bg-label-primary py-2 px-3">
                                            <span class="text-muted small">{{ $ant['kriteria'] }}</span>
                                            = <strong>{{ $ant['himpunan'] }}</strong>
                                        </span>
                                    @endforeach
                                </div>
                            </td>

                            {{-- Alpha (Fire Strength) --}}
                            <td class="text-center">
                                <span class="badge bg-success fs-6 py-2 px-3">
                                    {{ number_format($rule['alpha'], 4) }}
                                </span>
                            </td>

                            {{-- z Rule (Sugeno Orde Nol - rata-rata konsekuen) --}}
                            <td class="text-center fw-bold">
                                {{ number_format($rule['z_rule'], 4) }}
                            </td>

                            {{-- alpha × z --}}
                            <td class="text-center fw-bold text-primary">
                                {{ number_format($rule['alpha_x_z'], 4) }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                <i class="bx bx-error-circle fs-4 d-block mb-2"></i>
                                <strong>Tidak ada rule yang terbentuk.</strong> 
                                Ini mungkin karena semua himpunan fuzzy tidak aktif (μ = 0).
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                    @if(count($hasil['rules']) > 0)
                    <tfoot class="table-light fw-bold">
                        <tr class="text-center">
                            <td colspan="2" class="text-end">Jumlah (Σ):</td>
                            <td class="text-success">{{ number_format($hasil['sum_alpha'], 4) }}</td>
                            <td></td>
                            <td class="text-primary">{{ number_format($hasil['sum_alpha_z'], 4) }}</td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
            <div class="card-footer bg-light text-muted small">
                <i class="bx bx-info-circle me-1"></i>
                <strong>Catatan:</strong> Hanya rule dengan α > 0 yang berkontribusi pada hasil akhir.
                Jumlah rule biasanya jauh lebih sedikit dibanding sebelumnya karena filter himpunan aktif v2.0.
            </div>
        </div>

        {{-- ================================================================ --}}
        {{-- STEP 4: DEFUZZIFIKASI + HASIL AKHIR                             --}}
        {{-- ================================================================ --}}
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white border-bottom py-3">
                <h5 class="fw-bold mb-0">
                    <span class="badge bg-danger me-2">Step 4</span>
                    Defuzzifikasi — Weighted Average Sugeno Orde Nol
                </h5>
                <small class="text-muted d-block mt-2">
                    Menghitung nilai output crisp (z*) menggunakan formula weighted average.
                </small>
            </div>
            <div class="card-body">
                <div class="row g-4 align-items-center">

                    {{-- Formula --}}
                    <div class="col-12 col-md-5">
                        <div class="p-4 bg-light rounded border">
                            <p class="text-muted small mb-3 fw-bold text-uppercase">
                                <i class="bx bx-math me-1"></i> Formula Weighted Average (Sugeno Order 0):
                            </p>
                            <div class="text-center mb-3">
                                $$ z^* = \frac{\sum_{i=1}^{n} \alpha_i \cdot z_i}{\sum_{i=1}^{n} \alpha_i} $$
                            </div>
                            <hr>
                            <p class="text-muted small mb-2">Substitusi nilai:</p>
                            <div class="text-center">
                                $$ z^* = \frac{ {{ number_format($hasil['sum_alpha_z'], 4) }} }{ {{ number_format($hasil['sum_alpha'], 4) }} } $$
                            </div>
                            <hr>
                            <p class="text-muted small mb-2">Hasil:</p>
                            <div class="text-center">
                                <h5 class="fw-bold text-success">
                                    $$ z^* = {{ number_format($hasil['z'], 4) }} $$
                                </h5>
                            </div>
                        </div>
                    </div>

                    {{-- Hasil Akhir --}}
                    <div class="col-12 col-md-7">
                        <div class="row g-3">
                            {{-- Σ (α × z) --}}
                            <div class="col-6">
                                <div class="p-3 border rounded text-center bg-label-primary">
                                    <small class="text-muted d-block mb-1 fw-semibold">Σ (α × z)</small>
                                    <h4 class="fw-bold text-primary mb-0">
                                        {{ number_format($hasil['sum_alpha_z'], 4) }}
                                    </h4>
                                </div>
                            </div>

                            {{-- Σ α --}}
                            <div class="col-6">
                                <div class="p-3 border rounded text-center bg-label-info">
                                    <small class="text-muted d-block mb-1 fw-semibold">Σ α</small>
                                    <h4 class="fw-bold text-info mb-0">
                                        {{ number_format($hasil['sum_alpha'], 4) }}
                                    </h4>
                                </div>
                            </div>

                            {{-- Nilai z* (Output Crisp) --}}
                            <div class="col-6">
                                <div class="p-3 border rounded text-center bg-label-success">
                                    <small class="text-muted d-block mb-1 fw-semibold">Nilai z* (Crisp Output)</small>
                                    <h4 class="fw-bold text-success mb-0">
                                        {{ number_format($hasil['z'], 4) }}
                                    </h4>
                                    <small class="text-muted" style="font-size:0.7rem;">Range: 0 - 1</small>
                                </div>
                            </div>

                            {{-- Skor Akhir (%) --}}
                            <div class="col-6">
                                <div class="p-3 border rounded text-center bg-label-warning">
                                    <small class="text-muted d-block mb-1 fw-semibold">Skor Akhir (Scaling)</small>
                                    <h4 class="fw-bold text-warning mb-0">
                                        {{ number_format($hasil['skor_akhir'], 2) }}%
                                    </h4>
                                    <small class="text-muted" style="font-size:0.7rem;">z* × 100</small>
                                </div>
                            </div>

                            {{-- Keputusan Akhir --}}
                            <div class="col-12">
                                <div class="p-3 rounded border border-2
                                    {{ in_array($hasil['status_kelayakan'], ['Layak']) ? 'border-success bg-label-success' : 'border-danger bg-label-danger' }}">
                                    <div class="row align-items-center">
                                        <div class="col">
                                            <small class="text-muted d-block fw-semibold mb-1">KEPUTUSAN AKHIR</small>
                                            <h5 class="fw-bold mb-1">{{ $hasil['skala_prioritas'] }}</h5>
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
            <div class="card-footer bg-light text-muted small">
                <i class="bx bx-info-circle me-1"></i>
                <strong>Catatan:</strong> 
                Z* adalah nilai output Fuzzy Sugeno yang dinormalisasi dalam range [0, 1]. 
                Skor akhir diperoleh dengan scaling: Skor = Z* × 100%.
            </div>
        </div>

        {{-- TABEL REFERENSI SKALA PRIORITAS --}}
        <div class="card border-0 shadow-sm no-print mb-4">
            <div class="card-body">
                <h6 class="fw-bold mb-3">
                    <i class="bx bx-info-circle me-1"></i> 
                    Referensi Skala Prioritas — Pendekatan Bahu (Semakin Tinggi Semakin Layak)
                </h6>
                <div class="row text-center g-3">
                    <div class="col-6 col-md-3">
                        <div class="p-3 border rounded border-2 border-success">
                            <div class="fw-bold text-success mb-2">z* > 0.80</div>
                            <span class="badge bg-success mb-2 d-inline-block">Prioritas I</span>
                            <small class="text-muted d-block">Sangat Diprioritaskan</small>
                            <small class="text-success fw-bold d-block mt-1">✅ Layak</small>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-3 border rounded border-2 border-primary">
                            <div class="fw-bold text-primary mb-2">0.60 < z* ≤ 0.80</div>
                            <span class="badge bg-primary mb-2 d-inline-block">Prioritas II</span>
                            <small class="text-muted d-block">Diprioritaskan</small>
                            <small class="text-success fw-bold d-block mt-1">✅ Layak</small>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-3 border rounded border-2 border-warning">
                            <div class="fw-bold text-warning mb-2">0.40 < z* ≤ 0.60</div>
                            <span class="badge bg-warning text-dark mb-2 d-inline-block">Prioritas III</span>
                            <small class="text-muted d-block">Dipertimbangkan</small>
                            <small class="text-danger fw-bold d-block mt-1">❌ Tidak Layak</small>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-3 border rounded border-2 border-danger">
                            <div class="fw-bold text-danger mb-2">z* ≤ 0.40</div>
                            <span class="badge bg-secondary mb-2 d-inline-block">Prioritas IV</span>
                            <small class="text-muted d-block">Tidak Diprioritaskan</small>
                            <small class="text-danger fw-bold d-block mt-1">❌ Tidak Layak</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- INFO BOX v2.0 --}}
        <div class="alert alert-info alert-dismissible fade show no-print">
            <i class="bx bx-info-circle me-2"></i>
            <strong>FuzzySugenoService v2.0</strong> — 
            Laporan ini menggunakan implementasi Fuzzy Sugeno Orde Nol yang dioptimasi dengan:
            <ul class="mb-0 mt-2">
                <li>Direct evaluation dengan on-the-fly accumulation</li>
                <li>Smart filtering: hanya rule dengan membership aktif (μ > 0)</li>
                <li>Input validation untuk mencegah division by zero</li>
                <li>Pendekatan bahu: semakin tinggi Z* semakin layak</li>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>

    </div>
</div>

<style>
    .bg-light-alt { background-color: #f8f9fa; }
    .italic { font-style: italic; }
    
    {{-- MathJax styling --}}
    mjx-container { overflow: auto; }

    @media print {
        .no-print, .btn, .layout-navbar, .layout-menu, .alert-info { display: none !important; }
        .content-wrapper { margin: 0 !important; padding: 0 !important; }
        .card { border: 1px solid #eee !important; box-shadow: none !important; page-break-inside: avoid; }
        .table { width: 100% !important; border-collapse: collapse !important; font-size: 0.85rem !important; }
        body { background: white !important; }
        h4, h5, h6 { page-break-after: avoid; }
    }
</style>
@endsection
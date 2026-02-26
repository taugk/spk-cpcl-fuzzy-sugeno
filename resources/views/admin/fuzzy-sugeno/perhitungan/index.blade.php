@extends('admin.layouts.app')

@section('title', 'Laporan Analisis Fuzzy Sugeno')

@section('content')
<div class="content-wrapper">
    <div class="container-xxl container-p-y">
        
        <div class="card mb-4 border-0 shadow-sm no-print">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="fw-bold mb-1"><i class="bx bx-file-find text-primary me-2"></i>Hasil Analisis Kelayakan</h4>
                    <p class="text-muted mb-0">Subjek: <span class="badge bg-label-primary fs-6">{{ $hasil['cpcl']->nama_kelompok }}</span></p>
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

        {{-- KONFIGURASI MATHJAX --}}
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

        <div class="card shadow-sm border-0">
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <thead class="table-light text-center">
                        <tr class="text-uppercase small fw-bold">
                            <th style="width: 15%">Kriteria & Input</th>
                            <th style="width: 40%">Langkah 1: Fuzzifikasi ($\mu$)</th>
                            <th style="width: 15%">Langkah 2: Eval ($C$)</th>
                            <th style="width: 30%">Langkah 3: Inferensi & Keputusan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($hasil['fuzzifikasi'] as $index => $k)
                        <tr>
                            <td class="text-center bg-light-alt">
                                <span class="fw-bold d-block text-primary">{{ $k['kode'] }}</span>
                                <small class="text-dark d-block mb-2">{{ $k['nama'] }}</small>
                                <div class="px-2 py-1 rounded bg-white border small fw-bold">
                                    Input: {{ $k['input'] }} <br>
                                    ($x = {{ $k['x'] }}$)
                                </div>
                            </td>

                            <td class="p-3">
                                @foreach($k['sub'] as $s)
                                <div class="mb-3 p-3 border-start border-primary border-4 bg-light rounded-end shadow-xs">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="fw-bold text-uppercase small">{{ $s['nama'] }}</span>
                                        <span class="badge bg-white text-primary border shadow-sm fs-6">
                                            $\mu = {{ number_format($s['mu'], 4) }}$
                                        </span>
                                    </div>
                                    <div class="math-container text-dark small" style="overflow-x: auto;">
                                        @if($s['tipe'] == 'bahu_kiri')
                                            $$ \mu(x) = \begin{cases} 1, & x \le {{ $s['c'] }} \\ \frac{ {{ $s['d'] }} - x }{ {{ $s['d'] }} - {{ $s['c'] }} }, & {{ $s['c'] }} < x < {{ $s['d'] }} \\ 0, & x \ge {{ $s['d'] }} \end{cases} $$
                                        @elseif($s['tipe'] == 'trapesium')
                                            $$ \mu(x) = \begin{cases} \frac{x - {{ $s['a'] }} }{ {{ $s['b'] }} - {{ $s['a'] }} }, & {{ $s['a'] }} < x < {{ $s['b'] }} \\ 1, & {{ $s['b'] }} \le x \le {{ $s['c'] }} \\ \frac{ {{ $s['d'] }} - x }{ {{ $s['d'] }} - {{ $s['c'] }} }, & {{ $s['c'] }} < x < {{ $s['d'] }} \end{cases} $$
                                        @elseif($s['tipe'] == 'bahu_kanan')
                                            $$ \mu(x) = \begin{cases} 0, & x \le {{ $s['a'] }} \\ \frac{x - {{ $s['a'] }} }{ {{ $s['b'] }} - {{ $s['a'] }} }, & {{ $s['a'] }} < x < {{ $s['b'] }} \\ 1, & x \ge {{ $s['b'] }} \end{cases} $$
                                        @else
                                            <p class="mb-0 italic text-muted">Diskrit: Bernilai 1 jika cocok, 0 jika tidak.</p>
                                        @endif
                                    </div>
                                    <div class="mt-2 text-muted" style="font-size: 0.75rem;">
                                        <i class="bx bx-chevron-right"></i> Konsekuen Singleton ($k = {{ number_format($s['k'], 2) }}$)
                                    </div>
                                </div>
                                @endforeach
                            </td>

                            <td class="text-center">
                                <div class="p-3 bg-label-primary rounded border border-primary border-dashed">
                                    <small class="text-muted d-block mb-1 italic">Direct Evaluation</small>
                                    <div class="fw-bold text-primary">
                                        $ C = \frac{\sum (\mu_i \cdot k_i)}{\sum \mu_i} $
                                    </div>
                                    <h4 class="fw-bold mt-2 text-primary mb-0">{{ number_format($k['C'], 4) }}</h4>
                                </div>
                            </td>

                            @if($index == 0)
                            <td rowspan="5" class="p-4 bg-white align-top">
                                <div class="sticky-top" style="top: 20px;">
                                    <div class="p-4 border rounded shadow-sm bg-label-dark">
                                        <div class="mb-4">
                                            <label class="small text-muted d-block mb-2">1. Firing Strength ($\alpha$):</label>
                                            <div class="p-2 bg-white rounded border mb-2">
                                                $\alpha = \min(C_1, \dots, C_5)$
                                            </div>
                                            <h3 class="text-success fw-bold mb-0">{{ number_format($hasil['alpha'], 4) }}</h3>
                                        </div>

                                        <div class="mb-4">
                                            <label class="small text-muted d-block mb-2">2. Konsekuen Kolektif ($K_i$):</label>
                                            <div class="p-2 bg-white rounded border mb-2">
                                                $K_i = \frac{\sum C_n}{n}$
                                            </div>
                                            <h3 class="text-success fw-bold mb-0">{{ number_format($hasil['ki'], 4) }}</h3>
                                        </div>

                                        <hr class="my-4">

                                        <div class="text-center mb-4">
                                            <label class="small text-muted d-block mb-2 text-uppercase fw-bold">Nilai Akhir (Z)</label>
                                            <div class="display-4 fw-bold text-primary mb-0">{{ $hasil['skor_akhir'] }}%</div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="small text-muted d-block mb-1">Skala Prioritas:</label>
                                            <div class="badge bg-primary w-100 py-3 fs-6 shadow-sm">
                                                {{ $hasil['skala_prioritas'] }}
                                            </div>
                                        </div>

                                        <div class="mb-0">
                                            <label class="small text-muted d-block mb-1">Keputusan Akhir:</label>
                                            <div class="p-3 rounded bg-white text-dark fw-bold border border-2 border-primary text-center">
                                                {{ strtoupper($hasil['interpretasi']) }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            @endif
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card mt-4 border-0 shadow-sm border-start border-primary border-4 no-print">
            <div class="card-body">
                <h6 class="fw-bold mb-3"><i class="bx bx-info-circle me-1"></i> Referensi Skala Prioritas (Z)</h6>
                <div class="row text-center g-3">
                    <div class="col-6 col-md-3">
                        <div class="p-2 border rounded">
                            <small class="text-muted d-block">0.81 - 1.00</small>
                            <span class="fw-bold text-success">Prioritas I</span>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-2 border rounded">
                            <small class="text-muted d-block">0.61 - 0.80</small>
                            <span class="fw-bold text-primary">Prioritas II</span>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-2 border rounded">
                            <small class="text-muted d-block">0.41 - 0.60</small>
                            <span class="fw-bold text-warning">Prioritas III</span>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-2 border rounded">
                            <small class="text-muted d-block">≤ 0.40</small>
                            <span class="fw-bold text-danger">Prioritas IV</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .bg-light-alt { background-color: #f8f9fa; }
    .bg-label-dark { background-color: #f2f2f3; }
    .italic { font-style: italic; }
    .shadow-xs { box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075); }
    
    @media print {
        .no-print, .btn, .layout-navbar, .layout-menu { display: none !important; }
        .content-wrapper { margin: 0 !important; padding: 0 !important; }
        .card { border: 1px solid #eee !important; box-shadow: none !important; }
        .table { width: 100% !important; border-collapse: collapse !important; }
        body { background: white !important; }
        .sticky-top { position: static !important; }
    }
</style>
@endsection
@extends('admin.layouts.app')

@section('title', 'Laporan Analisis Fuzzy Sugeno')

@section('content')
<div class="content-wrapper">
    <div class="container-xxl container-p-y">

        {{-- HEADER LAPORAN --}}
        <div class="card mb-4 border-0 shadow-sm no-print">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="fw-bold mb-1">
                        <i class="bx bx-file-find text-primdarkary me-2"></i>
                        Laporan Analisis Kelayakan Fuzzy Sugeno
                    </h4>
                    <p class="text-muted mb-0">
                        Subjek: <span class="badge bg-label-dark fs-6">{{ $hasil['cpcl']->nama_kelompok }}</span>
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

        {{-- STEP 1: FUZZIFIKASI & GRAFIK --}}
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white border-bottom py-3">
                <h5 class="fw-bold mb-0">
                    <span class="badge bg-success me-2">Step 1</span>
                    Fuzzifikasi — Visualisasi Kurva & Derajat Keanggotaan ($\mu$)
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0">
                        <thead class="table-light text-center small fw-bold text-uppercase">
                            <tr>
                                <th style="width:30%">Kriteria & Visualisasi</th>
                                <th>Analisis Himpunan Aktif</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($hasil['fuzzifikasi'] as $k)
                            <tr>
                                {{-- KOLOM GRAFIK --}}
                                <td class="p-3 bg-light">
                                    <div class="text-center mb-2">
                                        <span class="fw-bold text-dark d-block">{{ $k['kode'] }} - {{ $k['nama'] }}</span>
                                        <small class="text-muted">Input: <strong>{{ $k['input'] }}</strong></small>
                                    </div>
                                    <div style="height: 180px; width: 100%;">
                                        <canvas id="chart-{{ $k['kode'] }}"></canvas>
                                    </div>
                                </td>
                                {{-- KOLOM DATA --}}
                                <td class="p-3">
                                    <div class="row g-2">
                                        @forelse($k['himpunan'] as $s)
                                        <div class="col-md-6">
                                            <div class="p-3 rounded border border-success bg-label-success shadow-sm">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <small class="fw-bold d-block text-uppercase">{{ $s['nama'] }}</small>
                                                        <span class="small">Tipe: {{ ucfirst(str_replace('_', ' ', $s['params']['tipe'])) }}</span>
                                                    </div>
                                                    <div class="text-end">
                                                        <span class="badge bg-success fs-6">$\mu$ = {{ number_format($s['mu'], 4) }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        @empty
                                        <div class="col-12 text-center py-3">
                                            <span class="text-danger italic">Tidak ada himpunan fuzzy yang aktif ($\mu = 0$)</span>
                                        </div>
                                        @endforelse
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- STEP 2 & 3: RULE BASE --}}
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white border-bottom py-3">
                <h5 class="fw-bold mb-0"><span class="badge bg-warning text-dark me-2">Step 2 & 3</span> Rule Base & Firing Strength</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <thead class="table-light text-center small fw-bold">
                        <tr>
                            <th>Rule</th><th>Anteceden (IF)</th><th>$\alpha$ (Min)</th><th>$z$ (Konsekuen)</th><th>$\alpha \times z$</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($hasil['rules'] as $rule)
                        <tr class="text-center">
                            <td class="fw-bold text-dark">{{ $rule['rule_id'] }}</td>
                            <td class="text-start">
                                @foreach($rule['anteceden'] as $i => $ant)
                                    @if($i > 0) <span class="badge bg-label-dark small">AND</span> @endif
                                    <span class="badge bg-label-success">{{ $ant['kriteria'] }} = {{ $ant['himpunan'] }}</span>
                                @endforeach
                            </td>
                            <td><span class="badge bg-success">{{ number_format($rule['alpha'], 4) }}</span></td>
                            <td>{{ number_format($rule['z_rule'], 2) }}</td>
                            <td class="fw-bold text-dark">{{ number_format($rule['alpha_x_z'], 4) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light fw-bold text-center">
                        <tr>
                            <td colspan="2" class="text-end text-uppercase">Total ($\Sigma$):</td>
                            <td class="text-dark">{{ number_format($hasil['sum_alpha'], 4) }}</td>
                            <td></td>
                            <td class="text-dark">{{ number_format($hasil['sum_alpha_z'], 4) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- STEP 4: HASIL AKHIR --}}
        <div class="card shadow-sm border-0 mb-4 border-top border-success border-3">
            <div class="card-body py-4">
                <div class="row align-items-center">
                    <div class="col-md-5 text-center border-end">
                        <h6 class="text-uppercase fw-bold text-muted small">Defuzzifikasi (Sugeno)</h6>
                        <div class="py-2">
                            $$z^* = \frac{\sum (\alpha_i \cdot z_i)}{\sum \alpha_i} = \frac{ {{ number_format($hasil['sum_alpha_z'], 4) }} }{ {{ number_format($hasil['sum_alpha'], 4) }} }$$
                            <h3 class="fw-bold text-dark mt-2">z = {{ number_format($hasil['z'], 4) }}</h3>
                        </div>
                    </div>
                    <div class="col-md-7 ps-md-5">
                        <div class="p-4 rounded-3 {{ $hasil['status_kelayakan'] == 'Layak' ? 'bg-label-success border border-success' : 'bg-label-danger border border-danger' }}">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h5 class="fw-bold mb-1 {{ $hasil['status_kelayakan'] == 'Layak' ? 'text-success' : 'text-danger' }}">
                                        {{ $hasil['skala_prioritas'] }}
                                    </h5>
                                    <p class="mb-0 text-dark italic">{{ $hasil['interpretasi'] }}</p>
                                </div>
                                <div class="text-end">
                                    <div class="display-5 fw-bold mb-0">{{ number_format($hasil['skor_akhir'], 2) }}%</div>
                                    <small class="fw-bold text-uppercase">Skor Kelayakan</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<style>
    mjx-container { margin: 10px 0 !important; }
    .bg-label-success { background: #e8fadf !important; color: #71dd37 !important; }
    .bg-label-primary { background: #e7e7ff !important; color: #696cff !important; }
    .bg-label-danger { background: #ffe5e5 !important; color: #ff3e1d !important; }
    .italic { font-style: italic; }
    @media print {
        .no-print, .btn, .layout-navbar, .layout-menu { display: none !important; }
        .content-wrapper { margin: 0 !important; padding: 0 !important; }
        .card { border: 1px solid #eee !important; box-shadow: none !important; page-break-inside: avoid; }
    }
</style>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-annotation@2.1.0"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        @foreach($hasil['fuzzifikasi'] as $k)
        (function() {
            const ctx = document.getElementById('chart-{{ $k['kode'] }}').getContext('2d');
            
            // Cek jika tipe data adalah Diskrit
            let isDiskrit = false;
            @if(isset($k['himpunan'][0]) && $k['himpunan'][0]['params']['tipe'] === 'diskrit')
                isDiskrit = true;
            @endif

            if (isDiskrit) {
                // RENDER BAR CHART UNTUK DATA DISKRIT
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: @json(array_column($k['himpunan'], 'nama')),
                        datasets: [{
                            data: @json(array_column($k['himpunan'], 'mu')),
                            backgroundColor: '#43a047',
                            borderRadius: 4,
                            barThickness: 30
                        }]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        scales: { y: { beginAtZero: true, max: 1.1, display: false }, x: { grid: { display: false } } },
                        plugins: { legend: { display: false } }
                    }
                });
            } else {
                // RENDER LINE CHART UNTUK DATA KONTINU
                @php
                    $cleanInput = (float) preg_replace('/[^0-9.]/', '', $k['input']);
                    $allValues = [0];
                    foreach($k['himpunan'] as $h) {
                        $allValues = array_merge($allValues, [
                            (float)$h['params']['a'], (float)$h['params']['b'], 
                            (float)$h['params']['c'], (float)$h['params']['d']
                        ]);
                    }
                    $maxX = count($allValues) > 0 ? max($allValues) * 1.1 : 100;
                @endphp

                new Chart(ctx, {
                    type: 'line',
                    data: {
                        datasets: [
                            @foreach($k['himpunan'] as $idx => $s)
                            @php
                                $p = $s['params'];
                                $a = (float)$p['a']; $b = (float)$p['b']; $c = (float)$p['c']; $d = (float)$p['d'];
                                
                                if($p['tipe'] == 'bahu_kiri') {
                                    $pts = "[{x:0, y:1}, {x:$c, y:1}, {x:$d, y:0}, {x:$maxX, y:0}]";
                                } elseif($p['tipe'] == 'bahu_kanan') {
                                    $pts = "[{x:0, y:0}, {x:$a, y:0}, {x:$b, y:1}, {x:$maxX, y:1}]";
                                } else { // trapesium
                                    $pts = "[{x:$a, y:0}, {x:$b, y:1}, {x:$c, y:1}, {x:$d, y:0}]";
                                }
                            @endphp
                            {
                                label: '{{ $s['nama'] }}',
                                data: {!! $pts !!},
                                borderColor: ['#2e7d32', '#66bb6a', '#c0ca33', '#8bc34a'][{{ $idx }} % 4],
                                backgroundColor: 'transparent',
                                borderWidth: 2, pointRadius: 0, tension: 0
                            },
                            @endforeach
                        ]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        scales: { 
                            x: { type: 'linear', min: 0, max: {{ $maxX }}, display: true },
                            y: { min: 0, max: 1.2, display: true }
                        },
                        plugins: {
                            legend: { display: false },
                            annotation: {
                                annotations: {
                                    line1: {
                                        type: 'line', xMin: {{ $cleanInput }}, xMax: {{ $cleanInput }},
                                        borderColor: 'red', borderWidth: 2, borderDash: [5, 5],
                                        label: { display: true, content: '{{ $cleanInput }}', position: 'top' }
                                    }
                                }
                            }
                        }
                    }
                });
            }
        })();
        @endforeach
    });

    window.MathJax = { tex: { inlineMath: [['$', '$'], ['\\(', '\\)']] } };
</script>
<script async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-svg.js"></script>
@endpush
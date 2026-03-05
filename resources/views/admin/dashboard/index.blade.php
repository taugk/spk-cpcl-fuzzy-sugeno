@extends('admin.layouts.app')

@section('title', 'Dashboard Admin')
@section('header', 'Ringkasan Sistem SPK')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    
    <div class="row">
        <div class="col-lg-8 mb-4 order-0">
            <div class="card bg-primary text-white">
                <div class="d-flex align-items-end row">
                    <div class="col-sm-7">
                        <div class="card-body">
                            <h5 class="card-title text-white">Selamat Datang, {{ auth()->user()->name }}! 🧑‍🌾</h5>
                            <p class="mb-4">
                                Hari ini terdapat <span class="fw-bold text-warning">{{ $countBaru ?? 0 }}</span> data CPCL baru. 
                                Sistem SPK telah meranking <span class="fw-bold text-white">{{ $countRanking ?? 0 }}</span> kelompok tani.
                            </p>
                            <a href="{{ route('admin.cpcl.index') }}" class="btn btn-sm btn-dark">Kelola Data CPCL</a>
                        </div>
                    </div>
                    <div class="col-sm-5 text-center text-sm-left">
                        <div class="card-body pb-0 px-0 px-md-4 text-end">
                            <img src="{{ asset('assets/img/illustrations/man-with-laptop-light.png') }}" height="140" alt="Illustration">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-md-4 mb-4">
            <div class="card h-100 shadow-none border">
                <div class="card-body">
                    <span class="d-block mb-1 text-muted small">Konfigurasi Fuzzy Sugeno</span>
                    <h5 class="card-title mb-3">Model Parameter</h5>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-secondary small">Total Kriteria</span>
                        <span class="badge bg-label-primary">{{ $jmlKriteria ?? 0 }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-secondary small">Himpunan Fuzzy (Sub)</span>
                        <span class="badge bg-label-info">{{ $jmlSubKriteria ?? 0 }}</span>
                    </div>
                    <div class="progress mb-2" style="height: 8px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: 100%"></div>
                    </div>
                    <small class="text-muted small fst-italic">Status: Sistem Siap Menghitung</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        @php
            $stats = [
                ['label' => 'Total CPCL', 'value' => $totalCpcl, 'color' => 'primary', 'icon' => 'bx-spreadsheet'],
                ['label' => 'Belum Verifikasi', 'value' => $countBaru, 'color' => 'warning', 'icon' => 'bx-time-five'],
                ['label' => 'Terverifikasi', 'value' => $countTerverifikasi, 'color' => 'success', 'icon' => 'bx-check-shield'],
                ['label' => 'Ditolak', 'value' => $countDitolak, 'color' => 'danger', 'icon' => 'bx-x-circle'],
            ];
        @endphp
        @foreach($stats as $s)
        <div class="col-md-3 col-6 mb-4">
            <div class="card border-bottom border-{{ $s['color'] }} border-3 shadow-none">
                <div class="card-body">
                    <div class="avatar flex-shrink-0 mb-2">
                        <span class="badge bg-label-{{ $s['color'] }} p-2"><i class="bx {{ $s['icon'] }} fs-4"></i></span>
                    </div>
                    <span class="fw-semibold d-block mb-1 small">{{ $s['label'] }}</span>
                    <h3 class="card-title mb-0">{{ $s['value'] ?? 0 }}</h3>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="row">
        <div class="col-12 col-lg-8 mb-4">
            <div class="card h-100">
                <div class="card-header pb-0">
                    <h5 class="m-0">Distribusi CPCL Per Wilayah</h5>
                    <small class="text-muted">Berdasarkan data lokasi</small>
                </div>
                <div class="card-body">
                    <div id="lokasiChart" style="min-height: 350px;"></div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-header pb-0">
                    <h5 class="m-0">Total Tiap Bidang</h5>
                    <small class="text-muted">Sektor Usulan</small>
                </div>
                <div class="card-body">
                    <div id="bidangChart"></div>
                    <div class="mt-4">
                        @foreach($bidangLabels as $index => $label)
                        <div class="d-flex justify-content-between align-items-center mb-2 border-bottom pb-1">
                            <span class="small text-muted">{{ $label }}</span>
                            <span class="fw-bold small">{{ $bidangData[$index] }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="card-title m-0"><i class="bx bx-trophy text-warning me-2"></i>Top 5 Skor Kelayakan</h5>
                    <a href="{{ route('admin.perhitungan.index') }}" class="btn btn-xs btn-outline-primary">Lihat Semua</a>
                </div>
                <div class="table-responsive text-nowrap">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Ranking</th>
                                <th>Kelompok Tani</th>
                                <th>Wilayah</th>
                                <th class="text-center">Skor Kelayakan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($topRank as $rank)
                            <tr>
                                <td><span class="badge bg-label-primary">#{{ $rank->ranking }}</span></td>
                                <td><strong>{{ $rank->cpcl->nama_kelompok }}</strong></td>
                                <td>{{ $rank->cpcl->lokasi }}</td>
                                <td class="text-center fw-bold text-primary">{{ number_format($rank->skor_akhir, 2) }}%</td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center py-4">Belum ada data ranking.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')


<script>
    document.addEventListener('DOMContentLoaded', function () {
        setTimeout(() => {
            // --- 1. CHART LOKASI (BAR) ---
            const lokasiChartEl = document.querySelector('#lokasiChart');
            const lokasiLabels = @json($lokasiLabels);
            const lokasiData = @json($lokasiData);

            if (lokasiChartEl && lokasiLabels.length > 0) {
                const lokasiOptions = {
                    chart: { type: 'bar', height: 350, toolbar: { show: false } },
                    series: [{ name: 'Kelompok Tani', data: lokasiData }],
                    xaxis: { categories: lokasiLabels },
                    plotOptions: { bar: { horizontal: true, borderRadius: 4, barHeight: '60%' } },
                    colors: ['#696cff'],
                    dataLabels: { enabled: true }
                };
                new ApexCharts(lokasiChartEl, lokasiOptions).render();
            }

            // --- 2. CHART BIDANG (DONUT) ---
            const bidangChartEl = document.querySelector('#bidangChart');
            const bidangLabels = @json($bidangLabels);
            const bidangData = @json($bidangData);

            if (bidangChartEl && bidangLabels.length > 0) {
                const bidangOptions = {
                    chart: { type: 'donut', height: 250 },
                    labels: bidangLabels,
                    series: bidangData,
                    colors: ['#696cff', '#03c3ec', '#71dd37', '#ff3e1d', '#ffab00'],
                    legend: { show: false },
                    plotOptions: { 
                        pie: { 
                            donut: { 
                                size: '70%',
                                labels: { 
                                    show: true, 
                                    total: { show: true, label: 'Total', formatter: () => {{ $totalCpcl }} } 
                                } 
                            } 
                        } 
                    }
                };
                new ApexCharts(bidangChartEl, bidangOptions).render();
            }
        }, 800);
    });
</script>
@endpush
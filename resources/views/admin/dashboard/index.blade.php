@extends('admin.layouts.app')

@section('title', 'Dashboard Admin')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    {{-- Widget Atas --}}
    <div class="row">
        <div class="col-lg-8 mb-4">
            <div class="card bg-primary text-white">
                <div class="d-flex align-items-end row">
                    <div class="col-sm-7">
                        <div class="card-body">
                            <h5 class="card-title text-white">Selamat Datang, {{ auth()->user()->name }}! 🧑‍🌾</h5>
                            <p class="mb-4">Sistem SPK telah meranking <span class="fw-bold">{{ $countRanking }}</span> data kelompok tani dengan skor rata-rata <span class="fw-bold">{{ number_format($avgSkor, 2) }}</span>.</p>
                            <a href="{{ route('admin.cpcl.index') }}" class="btn btn-sm btn-dark">Kelola Data CPCL</a>
                        </div>
                    </div>
                    <div class="col-sm-5 text-end">
                        <img src="{{ asset('assets/img/illustrations/man-with-laptop-light.png') }}" height="140" class="me-4" alt="Illustration">
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 mb-4">
            <div class="card h-100 shadow-none border">
                <div class="card-body">
                    <span class="d-block mb-1 text-muted small">Metadata Wilayah & Parameter</span>
                    <h5 class="card-title mb-3">Cakupan Sistem</h5>
                    
                    <div class="d-flex justify-content-between mb-2">
                        <span class="small">Jumlah Kriteria</span>
                        <span class="badge bg-label-info">{{ $jmlKriteria }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="small">Sub-Kriteria</span>
                        <span class="badge bg-label-secondary">{{ $jmlSubKriteria }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Statistik Card --}}
    <div class="row">
        @php
            $stats = [
                ['label' => 'Total CPCL', 'value' => $totalCpcl, 'color' => 'primary', 'icon' => 'bx-spreadsheet'],
                ['label' => 'Baru', 'value' => $countBaru, 'color' => 'warning', 'icon' => 'bx-time-five'],
                ['label' => 'Terverifikasi', 'value' => $countTerverifikasi, 'color' => 'success', 'icon' => 'bx-check-shield'],
                ['label' => 'Ditolak', 'value' => $countDitolak, 'color' => 'danger', 'icon' => 'bx-x-circle'],
            ];
        @endphp
        @foreach($stats as $s)
        <div class="col-md-3 col-6 mb-4">
            <div class="card border-bottom border-{{ $s['color'] }} border-3 shadow-none text-center h-100">
                <div class="card-body">
                    <div class="badge bg-label-{{ $s['color'] }} p-2 mb-2"><i class="bx {{ $s['icon'] }} fs-4"></i></div>
                    <span class="d-block mb-1 small text-muted">{{ $s['label'] }}</span>
                    <h3 class="card-title mb-0">{{ $s['value'] }}</h3>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Grafik --}}
    <div class="row">
        <div class="col-lg-8 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">Tren Registrasi CPCL</h5>
                    <div id="trenChart"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">Rasio Verifikasi</h5>
                    <div id="statusChart"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-7 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">Sebaran Per Kecamatan (Top 10)</h5>
                    <div id="lokasiChart"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-5 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">Bidang Usulan</h5>
                    <div id="bidangChart"></div>
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Top 5 Kelompok Tani Berdasarkan Skor Kelayakan</h5>
                    <div id="topSkorChart"></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const themeColors = ['#696cff', '#03c3ec', '#71dd37', '#ff3e1d', '#ffab00', '#8592a3'];

    // 1. Tren Chart (Line)
    new ApexCharts(document.querySelector("#trenChart"), {
        chart: { height: 300, type: 'area', toolbar: { show: false } },
        dataLabels: { enabled: false },
        series: [{ name: 'Registrasi', data: @json($trenData) }],
        xaxis: { categories: @json($trenLabels) },
        colors: [themeColors[0]],
        stroke: { curve: 'smooth' }
    }).render();

    // 2. Status Chart (Polar Area)
    
new ApexCharts(document.querySelector("#statusChart"), {
    chart: { 
        height: 320, 
        type: 'polarArea' 
    },
    labels: @json($statusLabels),
    series: @json($statusData),
    colors: ['#ffab00', '#71dd37', '#ff3e1d'],
    legend: { 
        position: 'bottom' 
    },

    yaxis: {
        show: false, // Menyembunyikan angka desimal yang berantakan di tengah grafik
    },
    plotOptions: {
        polarArea: {
            rings: {
                strokeWidth: 0
            },
            spokes: {
                strokeWidth: 0
            }
        }
    },
    tooltip: {
        y: {
            formatter: function(val) {
                return val + " Data" // Memastikan tooltip tetap rapi
            }
        }
    }
    // -------------------------------------
}).render();

    // 3. Lokasi Chart (Horizontal Bar)
    new ApexCharts(document.querySelector("#lokasiChart"), {
        chart: { height: 350, type: 'bar' },
        plotOptions: { bar: { horizontal: true, borderRadius: 4 } },
        series: [{ name: 'Total CPCL', data: @json($lokasiData) }],
        xaxis: { categories: @json($lokasiLabels) },
        colors: [themeColors[1]]
    }).render();

    // 4. Bidang Chart (Donut)
    new ApexCharts(document.querySelector("#bidangChart"), {
        chart: { height: 350, type: 'donut' },
        labels: @json($bidangLabels),
        series: @json($bidangData),
        colors: themeColors,
        legend: { position: 'bottom' }
    }).render();

    // 5. Top Skor Chart (Bar)
    new ApexCharts(document.querySelector("#topSkorChart"), {
        chart: { height: 300, type: 'bar' },
        plotOptions: { bar: { borderRadius: 10, columnWidth: '40%', distributed: true } },
        series: [{ name: 'Skor Akhir', data: @json($topSkorData) }],
        xaxis: { categories: @json($topSkorLabels) },
        colors: themeColors,
        dataLabels: { 
            enabled: true, 
            formatter: (v) => v.toFixed(3) 
        }
    }).render();
});
</script>
@endpush
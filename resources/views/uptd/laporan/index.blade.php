@extends('uptd.layouts.app')

@section('title', 'Laporan Surat Keputusan (SK) CPCL')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    
    <!-- Filter Section -->
    <div class="card mb-4 no-print">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="bx bx-filter-alt me-2"></i>Filter Laporan Surat Keputusan
            </h5>
            <small class="text-muted">UPTD - Sistem Pendukung Keputusan CPCL</small>
        </div>
        <div class="card-body">
            <form action="{{ route('uptd.laporan.index') }}" method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label fw-bold">Tahun Periode</label>
                    <select name="tahun" class="form-select">
                        <option value="">Semua Tahun</option>
                        @foreach($listTahun as $t)
                            <option value="{{ $t }}" {{ request('tahun') == $t ? 'selected' : '' }}>{{ $t }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Bidang Sektor</label>
                    <select name="bidang" class="form-select">
                        <option value="">Semua Bidang</option>
                        @foreach($listBidang as $b)
                            <option value="{{ $b }}" {{ request('bidang') == $b ? 'selected' : '' }}>{{ $b }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Wilayah/Lokasi</label>
                    <select name="lokasi" class="form-select">
                        <option value="">Semua Lokasi</option>
                        @foreach($listLokasi as $l)
                            <option value="{{ $l }}" {{ request('lokasi') == $l ? 'selected' : '' }}>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bx bx-filter-alt me-1"></i> Filter
                    </button>
                    <a href="{{ route('uptd.laporan.index') }}" class="btn btn-outline-secondary" title="Reset Filter">
                        <i class="bx bx-refresh"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Main Report Card -->
    <div class="card printable-area">
        <!-- Print Header (Hidden on Screen) -->
        <div class="d-none d-print-block mb-4">
            <div class="d-flex align-items-center border-bottom pb-3">
                <img src="{{ asset('assets/img/icons/brands/logo-dinas-pertanian.png') }}" alt="Logo" width="60" class="me-3">
                <div>
                    <h4 class="mb-0 fw-bold text-dark">LAPORAN SURAT KEPUTUSAN (SK) CPCL</h4>
                    <p class="mb-0 text-muted">Sistem Pendukung Keputusan - Unit Pelaksana Teknis Daerah (UPTD)</p>
                    <small class="text-muted">Periode: {{ request('tahun') ?? 'Semua Tahun' }}</small>
                </div>
            </div>
        </div>

        <!-- Screen Header (Hidden on Print) -->
        <div class="card-header d-flex justify-content-between align-items-center border-bottom mb-3 py-3 no-print">
            <div class="d-flex align-items-center">
                <img src="{{ asset('assets/img/icons/brands/logo-dinas-pertanian.png') }}" alt="Logo" width="60" class="me-3">
                <div>
                    <h4 class="mb-0 fw-bold text-dark">LAPORAN SURAT KEPUTUSAN (SK) CPCL</h4>
                    <p class="mb-0 text-muted">Sistem Pendukung Keputusan - Unit Pelaksana Teknis Daerah (UPTD)</p>
                    <small class="text-muted">Periode: {{ request('tahun') ?? 'Semua Tahun' }}</small>
                </div>
            </div>
            <div class="d-flex gap-2 no-print">
                <button onclick="window.print()" class="btn btn-danger btn-lg" title="Cetak/Export PDF">
                    <i class="bx bxs-file-pdf me-1"></i> Cetak PDF
                </button>
            </div>
        </div>

        <!-- Statistics Summary -->
        <div class="card-body pb-0 no-print">
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="alert alert-primary mb-0 p-3">
                        <h6 class="mb-1"><i class="bx bx-file me-1"></i>Total Kelompok</h6>
                        <h3 class="mb-0 text-primary">{{ $totalKelompok ?? 0 }}</h3>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="alert alert-success mb-0 p-3">
                        <h6 class="mb-1"><i class="bx bx-check-circle me-1"></i>Prioritas I</h6>
                        <h3 class="mb-0 text-success">{{ $totalPrioritas1 ?? 0 }}</h3>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="alert alert-info mb-0 p-3">
                        <h6 class="mb-1"><i class="bx bx-chart me-1"></i>Total Luas Lahan</h6>
                        <h3 class="mb-0 text-info">{{ $totalLuas ?? 0 }} Ha</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Table -->
        <div class="card-body pt-3">
            <div class="table-responsive-print">
                <table class="table table-bordered table-hover align-middle w-100">
                    <thead class="table-dark">
                        <tr class="text-center">
                            <th width="4%">Rank</th>
                            <th width="15%">Kelompok Tani</th>
                            <th width="12%">Ketua Kelompok</th>
                            <th width="12%">Lokasi</th>
                            <th width="10%">Bidang</th>
                            <th width="10%">Luas Lahan</th>
                            <th width="10%">Skor (%)</th>
                            <th width="17%">Prioritas</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($dataSK as $index => $row)
                        <tr>
                            <td class="text-center fw-bold">{{ $row->ranking }}</td>
                            <td class="text-wrap">
                                <strong>{{ $row->cpcl->nama_kelompok }}</strong>
                                <br>
                                <small class="text-muted">NIK: {{ $row->cpcl->nik_ketua ?? '-' }}</small>
                            </td>
                            <td class="text-wrap">{{ $row->cpcl->nama_ketua }}</td>
                            <td class="text-center">{{ $row->cpcl->lokasi }}</td>
                            <td class="text-center">{{ $row->cpcl->bidang }}</td>
                            <td class="text-center">{{ number_format($row->cpcl->luas_lahan, 2) }} Ha</td>
                            <td class="text-center fw-bold text-primary">
                                {{ number_format($row->skor_akhir, 2) }}%
                            </td>
                            <td class="text-center">
                                @php
                                    $skala = $row->skala_prioritas;
                                    $badgeClass = match($skala) {
                                        'Prioritas I' => 'bg-success',
                                        'Prioritas II' => 'bg-primary',
                                        'Prioritas III' => 'bg-warning text-dark',
                                        'Prioritas IV' => 'bg-danger',
                                        default => 'bg-secondary'
                                    };
                                @endphp
                                <span class="badge {{ $badgeClass }}">{{ $skala }}</span>
                            </td>
                            <td class="text-center no-print">
                                <a href="{{ route('uptd.laporan-sk.print', $row->cpcl) }}" 
                                   class="btn btn-sm btn-primary" target="_blank" 
                                   title="Cetak SK untuk {{ $row->cpcl->nama_kelompok }}">
                                    <i class="bx bxs-file-pdf"></i> Cetak
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center py-5">
                                <i class="bx bx-info-circle fs-2 d-block mb-2"></i>
                                <p class="mb-0">Tidak ada data untuk ditampilkan.</p>
                                <small class="text-muted">Silakan sesuaikan filter atau hubungi administrator.</small>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Ringkasan Detail -->
            @if($dataSK && count($dataSK) > 0)
            <div class="mt-5 pt-4 border-top">
                <h5 class="mb-3 fw-bold">
                    <i class="bx bx-detail me-2"></i>Ringkasan Prioritas
                </h5>
                <div class="row">
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="card-title fw-bold mb-3">Distribusi per Prioritas</h6>
                                <table class="table table-sm table-borderless">
                                    <tbody>
                                        <tr>
                                            <td><span class="badge bg-success">Prioritas I</span></td>
                                            <td class="text-end fw-bold">{{ $prioritasDistribusi['Prioritas I'] ?? 0 }} Kelompok</td>
                                        </tr>
                                        <tr>
                                            <td><span class="badge bg-primary">Prioritas II</span></td>
                                            <td class="text-end fw-bold">{{ $prioritasDistribusi['Prioritas II'] ?? 0 }} Kelompok</td>
                                        </tr>
                                        <tr>
                                            <td><span class="badge bg-warning text-dark">Prioritas III</span></td>
                                            <td class="text-end fw-bold">{{ $prioritasDistribusi['Prioritas III'] ?? 0 }} Kelompok</td>
                                        </tr>
                                        <tr>
                                            <td><span class="badge bg-danger">Prioritas IV</span></td>
                                            <td class="text-end fw-bold">{{ $prioritasDistribusi['Prioritas IV'] ?? 0 }} Kelompok</td>
                                        </tr>
                                        <tr class="border-top">
                                            <td><strong>Total</strong></td>
                                            <td class="text-end fw-bold">{{ $totalKelompok ?? 0 }} Kelompok</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="card-title fw-bold mb-3">Distribusi per Bidang</h6>
                                <table class="table table-sm table-borderless">
                                    <tbody>
                                        @foreach($bidangDistribusi ?? [] as $bidang => $count)
                                        <tr>
                                            <td><strong>{{ $bidang }}</strong></td>
                                            <td class="text-end fw-bold">{{ $count }} Kelompok</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Print Footer -->
            <div class="print-footer row mt-5 d-none d-print-flex justify-content-between text-center">
                <div class="col-4">
                    <p class="mb-4">Mengetahui,<br>UPTD</p>
                    <br><br><br>
                    <p class="fw-bold text-decoration-underline mb-0">( ........................................... )</p>
                    <p>NIP. .......................................</p>
                </div>
                <div class="col-4">
                    <p class="mb-4">Kuningan, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}<br>Kepala Dinas,</p>
                    <br><br><br>
                    <p class="fw-bold text-decoration-underline mb-0">( ........................................... )</p>
                    <p>NIP. .......................................</p>
                </div>
                <div class="col-4">
                    <p class="mb-4">Diverifikasi Oleh,<br>Operator SPK</p>
                    <br><br><br>
                    <p class="fw-bold text-decoration-underline mb-0">( ........................................... )</p>
                    <p>NIP. .......................................</p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Custom Styles */
    .table-responsive-print {
        overflow-x: auto;
    }

    .alert {
        border-radius: 0.75rem;
        border: none;
    }

    .badge {
        padding: 0.5rem 0.75rem;
        font-size: 0.8rem;
        font-weight: 600;
    }

    /* Print Styles */
    @media print {
        @page {
            size: A4 landscape;
            margin: 1.5cm;
        }

        /* Hide non-printable elements */
        .no-print, .layout-navbar, .layout-menu, .footer, .content-footer, .btn {
            display: none !important;
        }

        /* Reset layout */
        .container-xxl, .content-wrapper, .card {
            padding: 0 !important;
            margin: 0 !important;
            border: none !important;
            box-shadow: none !important;
            width: 100% !important;
        }

        /* Table printing */
        table {
            width: 100% !important;
            border-collapse: collapse !important;
            table-layout: auto !important;
        }

        thead {
            display: table-header-group !important;
        }

        tr {
            page-break-inside: avoid !important;
        }

        table td, table th {
            white-space: normal !important;
            word-wrap: break-word !important;
            font-size: 9pt !important;
            padding: 5px !important;
            border: 1px solid #000 !important;
        }

        table th {
            background-color: #212529 !important;
            color: white !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        /* Print footer */
        .d-print-flex {
            display: flex !important;
        }

        .d-none {
            display: block !important;
        }

        .d-print-block {
            display: block !important;
        }

        /* Remove backgrounds in print */
        .alert, .bg-light {
            background-color: transparent !important;
            border: none !important;
        }
    }

    /* Responsive for small screens */
    @media (max-width: 768px) {
        .table-responsive-print {
            font-size: 0.85rem;
        }

        table td, table th {
            padding: 6px !important;
        }
    }
</style>
@endsection
@extends('admin.layouts.app')

@section('title', 'Laporan Akhir CPCL')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    
    <div class="card mb-4 no-print">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Filter Laporan Akhir</h5>
            <small class="text-muted">Dinas Pertanian Kabupaten Kuningan</small>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.laporan.index') }}" method="GET" class="row g-3">
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
                    <label class="form-label fw-bold">Wilayah/Lokasi</label>
                    <select name="lokasi" class="form-select">
                        <option value="">Semua Lokasi</option>
                        @foreach($listLokasi as $l)
                            <option value="{{ $l }}" {{ request('lokasi') == $l ? 'selected' : '' }}>{{ $l }}</option>
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
                <div class="col-md-3 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bx bx-filter-alt me-1"></i> Filter
                    </button>
                    <a href="{{ route('admin.laporan.index') }}" class="btn btn-outline-secondary">
                        <i class="bx bx-refresh"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card printable-area">
        <div class="d-none d-print-block mb-4">
            <div class="d-flex align-items-center border-bottom pb-3">
                <img src="{{ asset('assets/img/icons/brands/logo-dinas-pertanian.png') }}" alt="Logo" width="60" class="me-3">
                <div>
                    <h4 class="mb-0 fw-bold text-dark">LAPORAN HASIL PENILAIAN CPCL</h4>
                    <p class="mb-0 text-muted">Sistem Pendukung Keputusan Dinas Pertanian Kabupaten Kuningan</p>
                    <small class="text-muted">Periode Laporan: {{ request('tahun') ?? 'Semua Tahun' }}</small>
                </div>
            </div>
        </div>

        <div class="card-header d-flex justify-content-between align-items-center border-bottom mb-3 py-3 no-print">
            <div class="d-flex align-items-center">
                <img src="{{ asset('assets/img/icons/brands/logo-dinas-pertanian.png') }}" alt="Logo" width="60" class="me-3">
                <div>
                    <h4 class="mb-0 fw-bold text-dark">LAPORAN HASIL PENILAIAN CPCL</h4>
                    <p class="mb-0 text-muted">Sistem Pendukung Keputusan Dinas Pertanian Kabupaten Kuningan</p>
                    <small class="text-muted">Periode Laporan: {{ request('tahun') ?? 'Semua Tahun' }}</small>
                </div>
            </div>
            <div class="no-print">
                <button onclick="window.print()" class="btn btn-danger btn-lg">
                    <i class="bx bxs-file-pdf me-1"></i> Cetak PDF / Print
                </button>
            </div>
        </div>

        <div class="card-body">
            <div class="table-responsive-print">
                <table class="table table-bordered align-middle w-100">
                    <thead class="table-dark">
                        <tr class="text-center">
                            <th width="5%">Rank</th>
                            <th width="20%">Kelompok Tani</th>
                            <th width="15%">Ketua</th>
                            <th width="15%">Lokasi</th>
                            <th width="12%">Bidang</th>
                            <th width="10%">Luas</th>
                            <th width="10%">Skor (%)</th>
                            <th width="13%">Prioritas</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($data as $row)
                        <tr>
                            <td class="text-center fw-bold">{{ $row->ranking }}</td>
                            <td class="text-wrap"><strong>{{ $row->cpcl->nama_kelompok }}</strong></td>
                            <td class="text-wrap">{{ $row->cpcl->nama_ketua }}</td>
                            <td>{{ $row->cpcl->lokasi }}</td>
                            <td>{{ $row->cpcl->bidang }}</td>
                            <td class="text-center">{{ $row->cpcl->luas_lahan }} Ha</td>
                            <td class="text-center fw-bold text-primary">{{ number_format($row->skor_akhir, 2) }}%</td>
                            <td class="text-center">
                                @php
                                    $badgeClass = match($row->skala_prioritas) {
                                        'Prioritas I' => 'bg-success',
                                        'Prioritas II' => 'bg-primary',
                                        'Prioritas III' => 'bg-warning',
                                        'Prioritas IV' => 'bg-danger',
                                        default => 'bg-secondary'
                                    };
                                @endphp
                                <span class="badge {{ $badgeClass }}">{{ $row->skala_prioritas }}</span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <i class="bx bx-info-circle fs-2 d-block mb-2"></i>
                                Tidak ada data untuk ditampilkan.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="print-footer row mt-5 d-none d-print-flex justify-content-end text-center">
                <div class="col-4">
                    <p class="mb-5">Kuningan, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}<br>Kepala Dinas Pertanian,</p>
                    <br><br><br>
                    <p class="fw-bold text-decoration-underline mb-0">( ........................................... )</p>
                    <p>NIP. .......................................</p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* CSS Print */
    @media print {
        @page {
            size: A4 landscape;
            margin: 1.5cm;
        }

        /* Hilangkan elemen navigasi dan tombol */
        .no-print, .layout-navbar, .layout-menu, .footer, .content-footer, .btn {
            display: none !important;
        }

        /* Reset layout untuk print */
        .container-xxl, .content-wrapper, .card {
            padding: 0 !important;
            margin: 0 !important;
            border: none !important;
            box-shadow: none !important;
            width: 100% !important;
        }

        /* Logika Tabel agar header muncul di setiap halaman */
        table {
            width: 100% !important;
            border-collapse: collapse !important;
            table-layout: fixed !important;
        }

        thead {
            display: table-header-group !important; /* Header muncul di tiap halaman */
        }

        tr {
            page-break-inside: avoid !important; /* Mencegah baris terpotong */
        }

        table td, table th {
            white-space: normal !important;
            word-wrap: break-word !important;
            font-size: 10pt !important;
            padding: 4px !important;
            border: 1px solid #000 !important;
        }

        /* Memastikan footer tanda tangan tampil */
        .d-print-flex {
            display: flex !important;
        }
        
        .d-none {
            display: block !important;
        }
    }
</style>
@endsection
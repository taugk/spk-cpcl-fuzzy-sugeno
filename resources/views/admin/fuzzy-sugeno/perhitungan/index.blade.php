@extends('admin.layouts.app')

@section('title', 'Perhitungan dan Ranking CPCL')

@section('content')
<div class="content-wrapper">
    <div class="container-xxl container-p-y">

        {{-- HEADER HALAMAN --}}
        <div class="d-flex align-items-center justify-content-between mb-4 border-bottom pb-3">
            <div>
                <h4 class="fw-bold text-uppercase mb-1">
                    <i class="bx bx-calculator me-2 text-success"></i>Perhitungan & Ranking Kelayakan CPCL
                </h4>
                <p class="text-muted mb-0">
                    Hasil Analisis Metode <strong>Fuzzy Sugeno Orde Nol</strong> Tahun Anggaran {{ $periode }}
                </p>
            </div>
            <div class="no-print">
                <button onclick="window.print()" class="btn btn-secondary shadow-sm">
                    <i class="bx bx-printer me-1"></i> Cetak Laporan
                </button>
            </div>
        </div>

        {{-- PANEL KONTROL --}}
        <div class="card shadow-sm mb-4 border-top border-success border-3 no-print">
            <div class="card-body">
                <div class="row g-4 align-items-center">
                    <div class="col-md-4 border-end">
                        <label class="form-label fw-bold text-dark small text-uppercase">Pilih Periode Laporan</label>
                        <form method="GET" action="{{ route('admin.perhitungan.index') }}">
                            <div class="input-group border-success">
                                <span class="input-group-text bg-white"><i class="bx bx-calendar"></i></span>
                                <select name="periode" class="form-select border-success" onchange="this.form.submit()">
                                    @foreach($periodeList as $p)
                                        <option value="{{ $p }}" {{ $periode == $p ? 'selected' : '' }}>
                                            Tahun Anggaran {{ $p }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </form>
                    </div>

                    <div class="col-md-8">
                        <label class="form-label fw-bold text-dark small text-uppercase">Otomasi Perhitungan Sistem</label>
                        <form method="POST" action="{{ route('admin.perhitungan.proses') }}">
                            @csrf
                            <input type="hidden" name="periode" value="{{ $periode }}">
                            <div class="d-flex align-items-center gap-3">
                                <button type="submit" class="btn btn-success px-4 shadow-sm" {{ $totalTerverifikasi == 0 ? 'disabled' : '' }}>
                                    <i class="bx bx-sync me-1"></i> Jalankan Kalkulasi Ranking
                                </button>
                                <div class="vr"></div>
                                <span class="badge bg-label-info">
                                    <i class="bx bx-info-circle me-1"></i> 
                                    Data Terverifikasi: <strong>{{ $totalTerverifikasi }}</strong> Kelompok
                                </span>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- TABEL DATA UTAMA --}}
        <div class="card shadow-sm border-0">
            <div class="card-header bg-dark d-flex justify-content-between align-items-center">
                <h6 class="mb-0 text-white fw-bold text-uppercase">Daftar Prioritas Penerima Manfaat CPCL</h6>
                <span class="badge bg-success">Dokumen Hasil Sistem</span>
            </div>

            @if($hasilRanking->isEmpty())
                <div class="card-body text-center py-5">
                    <i class="bx bx-data text-light mb-3" style="font-size: 5rem;"></i>
                    <p class="text-muted">Data hasil perhitungan belum tersedia.<br>Silakan klik tombol <strong>Jalankan Kalkulasi</strong>.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover table-sm align-middle mb-0">
                        <thead class="table-light border-bottom">
                            <tr class="text-uppercase small fw-bolder text-center">
                                <th class="py-3" style="width: 60px;">Rank</th>
                                <th class="py-3 text-start">Kelompok Tani</th>
                                <th class="py-3 text-start">Bidang</th>
                                <th class="py-3">Nilai Z</th>
                                <th class="py-3">Skor (%)</th>
                                <th class="py-3">Skala Prioritas</th>
                                <th class="py-3">Status</th>
                                <th class="py-3 no-print">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($hasilRanking as $index => $h)
                                <tr class="text-center">
                                    <td><span class="badge rounded-pill bg-label-dark fw-bold">{{ $h->ranking ?? $index + 1 }}</span></td>
                                    <td class="text-start">
                                        <div class="fw-bold text-dark">{{ $h->cpcl->nama_kelompok }}</div>
                                        <div class="small text-muted">Ketua: {{ $h->cpcl->nama_ketua }}</div>
                                    </td>
                                    <td class="text-start"><span class="small">{{ $h->cpcl->bidang ?? '-' }}</span></td>
                                    <td class="font-monospace">{{ number_format($h->nilai_z, 4) }}</td>
                                    <td class="fw-bold">{{ number_format($h->skor_akhir, 2) }}%</td>
                                    <td>
                                        @php
                                            $prioBadge = match($h->skala_prioritas) {
                                                'Prioritas I'   => 'bg-success',
                                                'Prioritas II'  => 'bg-primary',
                                                'Prioritas III' => 'bg-warning text-dark',
                                                default         => 'bg-secondary',
                                            };
                                        @endphp
                                        <span class="badge {{ $prioBadge }} px-3">{{ $h->skala_prioritas }}</span>
                                    </td>
                                    <td>
                                        @php
                                            $statusClass = match($h->status_kelayakan) {
                                                'Sangat Layak'   => 'text-success',
                                                'Diprioritaskan' => 'text-primary',
                                                'Dipertimbangkan' => 'text-warning',
                                                default          => 'text-muted',
                                            };
                                        @endphp
                                        <span class="{{ $statusClass }} fw-bold small text-uppercase">{{ $h->status_kelayakan }}</span>
                                    </td>
                                    <td class="no-print">
                                        <a href="{{ route('admin.perhitungan.detail', $h->cpcl_id) }}" class="btn btn-sm btn-outline-success">
                                            <i class="bx bx-list-check me-1"></i> Detail
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- FOOTER / REFERENSI SKALA --}}
        <div class="row mt-4 no-print">
            <div class="col-md-8">
                <div class="card border-0 shadow-sm border-start border-success border-3">
                    <div class="card-body p-3">
                        <h6 class="fw-bold mb-3"><i class="bx bx-info-square me-2 text-dark"></i>Legenda Klasifikasi Kelayakan (Sugeno)</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered small mb-0">
                                <thead class="table-light text-center text-uppercase">
                                    <tr>
                                        <th style="width: 25%;">Ambang Batas (\(z\))</th>
                                        <th>Kategori</th>
                                        <th>Status</th>
                                        <th>Interpretasi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="text-center fw-bold">\(z \ge 0.81\)</td>
                                        <td class="text-center"><span class="badge bg-success">Prioritas I</span></td>
                                        <td class="fw-bold text-success">Sangat Layak</td>
                                        <td class="text-muted">Sangat Diprioritaskan</td>
                                    </tr>
                                    <tr>
                                        <td class="text-center fw-bold">\(0.70 \le z < 0.81\)</td>
                                        <td class="text-center"><span class="badge bg-primary">Prioritas II</span></td>
                                        <td class="fw-bold text-primary">Diprioritaskan</td>
                                        <td class="text-muted">Diprioritaskan</td>
                                    </tr>
                                    <tr>
                                        <td class="text-center fw-bold">\(0.55 \le z < 0.70\)</td>
                                        <td class="text-center"><span class="badge bg-warning text-dark">Prioritas III</span></td>
                                        <td class="fw-bold text-warning">Dipertimbangkan</td>
                                        <td class="text-muted">Dipertimbangkan</td>
                                    </tr>
                                    <tr>
                                        <td class="text-center fw-bold">\(z < 0.55\)</td>
                                        <td class="text-center"><span class="badge bg-secondary">Prioritas IV</span></td>
                                        <td class="fw-bold text-secondary">Tidak Diprioritaskan</td>
                                        <td class="text-muted">Tidak Diprioritaskan</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="alert alert-secondary border-0 shadow-sm small h-100 mb-0">
                    <h6 class="fw-bold"><i class="bx bx-help-circle me-1"></i> Catatan Sistem</h6>
                    <p class="mb-0 text-justify">
                        Ranking dihitung secara otomatis berdasarkan nilai Defuzzifikasi (Z) tertinggi. Interpretasi status kelayakan mengacu pada Peraturan Standar Teknis yang telah dikonfigurasi pada sistem pakar.
                    </p>
                </div>
            </div>
        </div>

    </div>
</div>

<style>
/* KUSTOMISASI KEDINASAN */
body { background-color: #f4f7f6; }
.bg-label-info { background-color: #e0f7fa !important; color: #00acc1 !important; }
.bg-label-dark { background-color: #e9ecef !important; color: #495057 !important; }
.text-justify { text-align: justify; }

@media print {
    .no-print, .layout-navbar, .layout-menu { display: none !important; }
    .content-wrapper { margin: 0 !important; padding: 0 !important; }
    .card { border: 1px solid #dee2e6 !important; box-shadow: none !important; }
    table { font-size: 10px !important; }
}
</style>

{{-- Script MathJax untuk render simbol matematika --}}
<script async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>
@endsection
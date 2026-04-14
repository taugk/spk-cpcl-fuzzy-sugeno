@extends('admin.layouts.app')

@section('title', 'Perhitungan dan Ranking CPCL')

@section('content')
<div class="content-wrapper">
    <div class="container-xxl container-p-y">

        {{-- ==========================================
             HEADER HALAMAN (Khas Kedinasan)
             ========================================== --}}
        <div class="d-flex align-items-center justify-content-between mb-4 border-bottom pb-3">
            <div>
                <h4 class="fw-bold text-uppercase mb-1">
                    <i class="bx bx-calculator me-2 text-primary"></i>Perhitungan & Ranking Kelayakan CPCL
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

        {{-- ==========================================
             PANEL KONTROL (FILTER & PROSES)
             ========================================== --}}
        <div class="card shadow-sm mb-4 border-top border-primary border-3 no-print">
            <div class="card-body">
                <div class="row g-4 align-items-center">
                    {{-- Filter Periode --}}
                    <div class="col-md-4 border-end">
                        <label class="form-label fw-bold text-dark small text-uppercase">Pilih Periode Laporan</label>
                        <form method="GET" action="{{ route('admin.perhitungan.index') }}">
                            <div class="input-group border-primary">
                                <span class="input-group-text bg-white"><i class="bx bx-calendar"></i></span>
                                <select name="periode" class="form-select border-primary" onchange="this.form.submit()">
                                    @foreach($periodeList as $p)
                                        <option value="{{ $p }}" {{ $periode == $p ? 'selected' : '' }}>
                                            Tahun Anggaran {{ $p }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </form>
                    </div>

                    {{-- Proses Hitung --}}
                    <div class="col-md-8">
                        <label class="form-label fw-bold text-dark small text-uppercase">Otomasi Perhitungan Sistem</label>
                        <form method="POST" action="{{ route('admin.perhitungan.proses') }}">
                            @csrf
                            <input type="hidden" name="periode" value="{{ $periode }}">
                            <div class="d-flex align-items-center gap-3">
                                <button type="submit" class="btn btn-primary px-4 shadow-sm" {{ $totalTerverifikasi == 0 ? 'disabled' : '' }}>
                                    <i class="bx bx-sync me-1"></i> Jalankan Kalkulasi Ranking
                                </button>
                                <div class="vr"></div>
                                <div>
                                    <span class="badge bg-label-info d-block">
                                        <i class="bx bx-info-circle me-1"></i> 
                                        Data Terverifikasi: <strong>{{ $totalTerverifikasi }}</strong> Kelompok
                                    </span>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- ==========================================
             TABEL DATA UTAMA
             ========================================== --}}
        <div class="card shadow-sm border-0">
            <div class="card-header bg-dark d-flex justify-content-between align-items-center">
                <h6 class="mb-0 text-white fw-bold text-uppercase">
                    Daftar Prioritas Penerima Manfaat CPCL
                </h6>
                <span class="badge bg-primary text-uppercase">Dokumen Hasil Sistem</span>
            </div>

            @if($hasilRanking->isEmpty())
                <div class="card-body text-center py-5">
                    <i class="bx bx-data text-light mb-3" style="font-size: 5rem;"></i>
                    <p class="text-muted">Data hasil perhitungan untuk periode {{ $periode }} belum tersedia.<br>Silakan klik tombol <strong>Jalankan Kalkulasi</strong>.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover table-sm align-middle mb-0">
                        <thead class="table-light border-bottom">
                            <tr class="text-uppercase small fw-bolder">
                                <th class="text-center py-3" style="width: 60px;">Rank</th>
                                <th class="py-3">Identitas Kelompok Tani</th>
                                <th class="py-3">Bidang</th>
                                <th class="text-center py-3">Nilai Z</th>
                                <th class="text-center py-3">Skor (%)</th>
                                <th class="text-center py-3">Skala Prioritas</th>
                                <th class="text-center py-3">Status Kelayakan</th>
                                <th class="text-center py-3 no-print">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($hasilRanking as $index => $h)
                                <tr>
                                    <td class="text-center">
                                        <span class="badge rounded-pill bg-label-dark fw-bold">
                                            {{ $h->ranking ?? $index + 1 }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark">{{ $h->cpcl->nama_kelompok }}</div>
                                        <div class="small text-muted"><i class="bx bx-user me-1"></i>Ketua: {{ $h->cpcl->nama_ketua }}</div>
                                    </td>
                                    <td><span class="text-uppercase small">{{ $h->cpcl->bidang ?? '-' }}</span></td>
                                    <td class="text-center font-monospace">{{ number_format($h->nilai_z, 4) }}</td>
                                    <td class="text-center fw-bold">{{ number_format($h->skor_akhir, 2) }}%</td>
                                    <td class="text-center">
                                        @php
                                            $prioBadge = match($h->skala_prioritas) {
                                                'Prioritas I'   => 'bg-success text-white',
                                                'Prioritas II'  => 'bg-info text-dark',
                                                'Prioritas III' => 'bg-warning text-dark',
                                                default         => 'bg-danger text-white',
                                            };
                                        @endphp
                                        <span class="badge {{ $prioBadge }} small px-3">
                                            {{ $h->skala_prioritas }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        @if($h->status_kelayakan == 'Sangat Layak' || $h->status_kelayakan == 'Layak')
                                            <span class="text-success fw-bold small text-uppercase">
                                                <i class="bx bx-check-circle me-1"></i>{{ $h->status_kelayakan }}
                                            </span>
                                        @else
                                            <span class="text-muted fw-bold small text-uppercase">
                                                {{ $h->status_kelayakan }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-center no-print">
                                        <a href="{{ route('admin.perhitungan.detail', $h->cpcl_id) }}" 
                                           class="btn btn-sm btn-outline-primary shadow-sm"
                                           data-bs-toggle="tooltip" title="Detail Perhitungan">
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

        {{-- ==========================================
             FOOTER / REFERENSI SKALA (4 PRIORITAS)
             ========================================== --}}
        <div class="row mt-4 no-print">
            <div class="col-md-7">
                <div class="card border-0 shadow-sm border-start border-primary border-3">
                    <div class="card-body p-3">
                        <h6 class="fw-bold mb-3"><i class="bx bx-info-square me-2 text-primary"></i>Keterangan Klasifikasi Prioritas</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered small text-center mb-0">
                                <thead class="table-light text-uppercase">
                                    <tr>
                                        <th>Ambang Batas (z)</th>
                                        <th>Kategori</th>
                                        <th>Interpretasi Kelayakan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="table-danger border-white">
                                        <td class="fw-bold">z > 0.75</td>
                                        <td>Prioritas I</td>
                                        <td>Sangat Layak (Prioritas Utama)</td>
                                    </tr>
                                    <tr class="table-warning border-white">
                                        <td class="fw-bold">0.60 < z ≤ 0.75</td>
                                        <td>Prioritas II</td>
                                        <td>Layak (Diprioritaskan)</td>
                                    </tr>
                                    <tr class="table-info border-white">
                                        <td class="fw-bold">0.55 < z ≤ 0.60</td>
                                        <td>Prioritas III</td>
                                        <td>Cukup Layak (Dapat Diterima)</td>
                                    </tr>
                                    <tr class="table-light">
                                        <td class="fw-bold text-muted">z ≤ 0.55</td>
                                        <td class="text-muted">Prioritas IV</td>
                                        <td class="text-muted italic text-start px-3">Dipertimbangkan (Cadangan/Evaluasi)</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-5">
                <div class="alert alert-secondary border-0 shadow-sm small h-100 mb-0">
                    <h6 class="fw-bold"><i class="bx bx-help-circle me-1"></i> Catatan Sistem</h6>
                    <p class="mb-0 text-justify">
                        Urutan ranking ditentukan berdasarkan nilai <strong>Defuzzifikasi (Z)</strong> tertinggi. Jika terdapat nilai Z yang sama, sistem akan mempertimbangkan urutan waktu pendaftaran (First Come First Served).
                    </p>
                </div>
            </div>
        </div>

    </div>
</div>

<style>
/* KUSTOMISASI KEDINASAN */
body { background-color: #f4f7f6; }
.card-header { letter-spacing: 0.5px; }
.bg-label-info { background-color: #e0f7fa !important; color: #00acc1 !important; }
.bg-label-dark { background-color: #e9ecef !important; color: #495057 !important; }
.text-justify { text-align: justify; }

/* OPTIMASI PRINT */
@media print {
    .no-print, .layout-navbar, .layout-menu, .btn-sm { display: none !important; }
    .content-wrapper { margin: 0 !important; padding: 0 !important; }
    .card { border: 1px solid #dee2e6 !important; box-shadow: none !important; border-top: none !important; }
    .card-header { background-color: #333 !important; color: white !important; -webkit-print-color-adjust: exact; }
    table { font-size: 11px !important; }
    .badge { border: 1px solid #ccc !important; color: black !important; background: transparent !important; }
}
</style>
@endsection
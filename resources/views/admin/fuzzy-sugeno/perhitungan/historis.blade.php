@extends('admin.layouts.app')

@section('title', 'Antrean Prioritas (Tahun Depan)')

@section('content')
<div class="content-wrapper">
    <div class="container-xxl container-p-y">

        {{-- HEADER HALAMAN --}}
        <div class="d-flex align-items-center justify-content-between mb-4 border-bottom pb-3">
            <div>
                <h4 class="fw-bold text-uppercase mb-1">
                    <i class="bx bx-time-five me-2 text-warning"></i>Antrean Prioritas CPCL
                </h4>
                <p class="text-muted mb-0">
                    Data historis hasil perhitungan kategori <strong>Dipertimbangkan</strong> untuk evaluasi periode mendatang.
                </p>
            </div>
            <div class="no-print">
                <button onclick="window.print()" class="btn btn-secondary shadow-sm">
                    <i class="bx bx-printer me-1"></i> Cetak Daftar
                </button>
            </div>
        </div>

        {{-- PANEL FILTER DINAMIS --}}
        <div class="card shadow-sm mb-4 border-top border-warning border-3 no-print">
            <div class="card-body">
                <form method="GET" action="{{ route('admin.historis-perhitungan.index') }}">
                    <div class="row g-3">
                        {{-- Filter Tahun --}}
                        <div class="col-md-3">
                            <label class="form-label fw-bold small text-uppercase">Tahun Sumber</label>
                            <select name="tahun" class="form-select border-warning" onchange="this.form.submit()">
                                <option value="">Semua Tahun</option>
                                @foreach($listTahun as $t)
                                    <option value="{{ $t }}" {{ request('tahun') == $t ? 'selected' : '' }}>{{ $t }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        {{-- Filter Bidang --}}
                        @if(Auth::user()->role == 'admin')
                        <div class="col-md-3">
                            <label class="form-label fw-bold small text-uppercase">Filter Bidang</label>
                            <select name="bidang" class="form-select border-warning" onchange="this.form.submit()">
                                <option value="">Semua Bidang</option>
                                @foreach($listBidang as $b)
                                    <option value="{{ $b }}" {{ request('bidang') == $b ? 'selected' : '' }}>{{ $b }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif

                        {{-- Filter Lokasi --}}
                        <div class="col-md-3">
                            <label class="form-label fw-bold small text-uppercase">Lokasi (Kecamatan)</label>
                            <select name="lokasi" class="form-select border-warning" onchange="this.form.submit()">
                                <option value="">Semua Lokasi</option>
                                @foreach($listLokasi as $l)
                                    <option value="{{ $l }}" {{ request('lokasi') == $l ? 'selected' : '' }}>{{ $l }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- TOMBOL RESET (DENGAN WARNA) --}}
                        <div class="col-md-3 d-flex align-items-end">
                            <a href="{{ route('admin.historis-perhitungan.index') }}" class="btn btn-danger w-100 shadow-sm">
                                <i class="bx bx-refresh me-1"></i> Reset Filter
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- TABEL DATA UTAMA --}}
        <div class="card shadow-sm border-0">
            <div class="card-header bg-dark d-flex justify-content-between align-items-center">
                <h6 class="mb-0 text-white fw-bold text-uppercase">Daftar Cadangan Kelompok Tani</h6>
                <span class="badge bg-warning text-dark">Data Antrean</span>
            </div>

            @if($data->isEmpty())
                <div class="card-body text-center py-5">
                    <i class="bx bx-archive text-light mb-3" style="font-size: 5rem;"></i>
                    <p class="text-muted">Tidak ditemukan data historis yang sesuai dengan kriteria filter.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover table-sm align-middle mb-0">
                        <thead class="table-light border-bottom">
                            <tr class="text-uppercase small fw-bolder text-center">
                                <th class="py-3" style="width: 60px;">No</th>
                                <th class="py-3 text-start">Kelompok Tani</th>
                                <th class="py-3 text-start">Bidang</th>
                                <th class="py-3">Skor (%)</th>
                                <th class="py-3">Desa & Kecamatan</th>
                                <th class="py-3">Tahun Anggaran</th>
                                <th class="py-3 no-print">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($data as $h)
                                <tr class="text-center">
                                    <td><span class="badge rounded-pill bg-label-dark fw-bold">{{ $loop->iteration }}</span></td>
                                    <td class="text-start">
                                        <div class="fw-bold text-dark">{{ $h->cpcl->nama_kelompok ?? '-' }}</div>
                                        <div class="small text-muted">Ketua: {{ $h->cpcl->nama_ketua ?? '-' }}</div>
                                    </td>
                                    <td class="text-start">
                                        <span class="small text-uppercase">{{ $h->cpcl->bidang ?? '-' }}</span>
                                    </td>
                                    <td class="fw-bold text-warning">{{ number_format($h->skor_akhir, 2) }}%</td>
                                    <td>
                                        @if($h->cpcl && $h->cpcl->alamat)
                                            <div class="d-flex flex-column align-items-center">
                                                <span class="badge bg-label-primary px-3 text-uppercase mb-1">
                                                    {{ $h->cpcl->alamat->desa ?? '-' }}
                                                </span>
                                                <small class="text-muted fw-bold" style="font-size: 0.7rem;">
                                                    {{ $h->cpcl->alamat->kecamatan ?? '-' }}
                                                </small>
                                            </div>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $h->cpcl->created_at ? $h->cpcl->created_at->year : '-' }}
                                    </td>
                                    <td class="no-print">
                                        <a href="{{ route('admin.perhitungan.detail', $h->cpcl_id) }}" class="btn btn-sm btn-outline-warning">
                                            <i class="bx bx-list-check me-1"></i> Evaluasi
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- PAGINATION --}}
        @if(method_exists($data, 'links') && $data->total() > $data->perPage())
            <div class="d-flex justify-content-center mt-4 no-print">
                {{ $data->links('pagination::bootstrap-5') }}
            </div>
        @endif

        {{-- INFO PANEL --}}
        <div class="alert alert-secondary border-0 shadow-sm mt-4 no-print">
            <h6 class="fw-bold"><i class="bx bx-help-circle me-1"></i> Catatan Historis</h6>
            <p class="mb-0 small text-justify">
                Kelompok tani yang terdaftar di sini adalah mereka yang memiliki status <strong>"Dipertimbangkan"</strong>. Data ini disimpan agar pada periode anggaran berikutnya, kelompok tersebut dapat diprioritaskan kembali untuk diverifikasi tanpa harus mengulang proses pendaftaran dari awal jika kebijakan dinas memungkinkan.
            </p>
        </div>

    </div>
</div>

<style>
/* KUSTOMISASI SESUAI TEMA */
body { background-color: #f4f7f6; }
.bg-label-warning { background-color: #fff8e1 !important; color: #ff8f00 !important; }
.bg-label-dark { background-color: #e9ecef !important; color: #495057 !important; }
.bg-label-primary { background-color: #e3f2fd !important; color: #1976d2 !important; }

@media print {
    .no-print, .layout-navbar, .layout-menu { display: none !important; }
    .content-wrapper { margin: 0 !important; padding: 0 !important; }
    .card { border: 1px solid #dee2e6 !important; box-shadow: none !important; }
}
</style>
@endsection
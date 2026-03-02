@extends('admin.layouts.app')

@section('title', 'Perhitungan & Ranking CPCL')

@section('content')
<div class="content-wrapper">
    <div class="container-xxl container-p-y">

        {{-- HEADER --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1">
                    <i class="bx bx-trophy text-warning me-2"></i>Perhitungan & Ranking Kelayakan CPCL
                </h4>
                <p class="text-muted mb-0">Hasil Fuzzy Sugeno Orde Nol — diurutkan berdasarkan skor tertinggi</p>
            </div>
            <button onclick="window.print()" class="btn btn-outline-secondary no-print">
                <i class="bx bx-printer me-1"></i> Cetak
            </button>
        </div>

        {{-- FLASH MESSAGES --}}
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bx bx-check-circle me-1"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('warning'))
            <div class="alert alert-warning alert-dismissible fade show">
                <i class="bx bx-error me-1"></i> {{ session('warning') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="bx bx-x-circle me-1"></i> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- PANEL KONTROL --}}
        <div class="card border-0 shadow-sm mb-4 no-print">
            <div class="card-body">
                <div class="row g-3 align-items-end">

                    {{-- Filter periode --}}
                    <div class="col-12 col-md-3">
                        <form method="GET" action="{{ route('admin.perhitungan.index') }}">
                            <label class="form-label fw-semibold small">Filter Periode</label>
                            <select name="periode" class="form-select" onchange="this.form.submit()">
                                @foreach($periodeList as $p)
                                    <option value="{{ $p }}" {{ $periode == $p ? 'selected' : '' }}>
                                        Tahun {{ $p }}
                                    </option>
                                @endforeach
                            </select>
                        </form>
                    </div>

                    {{-- Tombol hitung semua --}}
                    <div class="col-12 col-md-6">
                        <form method="POST" action="{{ route('admin.perhitungan.proses') }}"
                              onsubmit="return confirm('Proses hitung {{ $totalTerverifikasi }} CPCL terverifikasi periode {{ $periode }}?\n\nData ranking yang ada akan diperbarui.')">
                            @csrf
                            <label class="form-label fw-semibold small">Proses Hitung Semua CPCL Terverifikasi</label>
                            <div class="input-group">
                                <input type="number" name="periode" class="form-control"
                                       value="{{ $periode }}" min="2020" max="2099">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bx bx-calculator me-1"></i> Hitung & Ranking
                                </button>
                            </div>
                            <small class="text-muted mt-1 d-block">
                                <i class="bx bx-info-circle me-1"></i>
                                <strong>{{ $totalTerverifikasi }}</strong> CPCL terverifikasi periode {{ $periode }}.
                                @if($totalBelumDihitung > 0)
                                    <span class="text-warning fw-bold">{{ $totalBelumDihitung }} belum dihitung.</span>
                                @else
                                    <span class="text-success">Semua sudah dihitung.</span>
                                @endif
                            </small>
                        </form>
                    </div>

                    {{-- Statistik ringkas --}}
                    @if($hasilRanking->isNotEmpty())
                    <div class="col-12 col-md-3">
                        <div class="row g-2 text-center">
                            <div class="col-6">
                                <div class="p-2 rounded bg-label-success border">
                                    <div class="fw-bold fs-5 text-success">
                                        {{ $hasilRanking->where('status_kelayakan', 'Layak')->count() }}
                                    </div>
                                    <small class="text-muted">Layak</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-2 rounded bg-label-danger border">
                                    <div class="fw-bold fs-5 text-danger">
                                        {{ $hasilRanking->where('status_kelayakan', 'Tidak Layak')->count() }}
                                    </div>
                                    <small class="text-muted">Tidak Layak</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- TABEL RANKING --}}
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center py-3">
                <h6 class="fw-bold mb-0">
                    <i class="bx bx-list-ol me-1"></i>
                    Hasil Ranking — Periode {{ $periode }}
                    <span class="badge bg-label-primary ms-2">{{ $hasilRanking->count() }} CPCL</span>
                </h6>
            </div>

            @if($hasilRanking->isEmpty())
                <div class="card-body text-center py-5">
                    <i class="bx bx-calculator fs-1 text-muted d-block mb-3"></i>
                    <h6 class="text-muted">Belum ada data ranking untuk periode {{ $periode }}</h6>
                    <p class="text-muted small mb-0">
                        Klik tombol <strong>"Hitung &amp; Ranking"</strong> untuk memproses
                        {{ $totalTerverifikasi }} CPCL terverifikasi.
                    </p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr class="text-uppercase small fw-bold text-muted">
                                <th class="text-center" style="width:5%">Rank</th>
                                <th style="width:23%">Kelompok Tani</th>
                                <th style="width:10%">Bidang</th>
                                <th class="text-center" style="width:9%">z*</th>
                                <th class="text-center" style="width:13%">Skor</th>
                                <th class="text-center" style="width:15%">Skala Prioritas</th>
                                <th class="text-center" style="width:10%">Status</th>
                                <th class="text-center no-print" style="width:8%">Detail</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($hasilRanking as $h)
                            @php
                                $rank       = $h->ranking;
                                $rowClass   = $rank === 1 ? 'table-warning' : '';
                                $badgeClass = match($h->skala_prioritas ?? '') {
                                    'Prioritas I'   => 'bg-success',
                                    'Prioritas II'  => 'bg-primary',
                                    'Prioritas III' => 'bg-warning text-dark',
                                    default         => 'bg-secondary',
                                };
                            @endphp
                            <tr class="{{ $rowClass }}">

                                {{-- Rank --}}
                                <td class="text-center fw-bold">
                                    @if($rank == 1) <span class="fs-5">🥇</span>
                                    @elseif($rank == 2) <span class="fs-5">🥈</span>
                                    @elseif($rank == 3) <span class="fs-5">🥉</span>
                                    @else <span class="badge bg-label-secondary">#{{ $rank }}</span>
                                    @endif
                                </td>

                                {{-- Kelompok --}}
                                <td>
                                    <div class="fw-semibold">{{ $h->cpcl->nama_kelompok }}</div>
                                    <small class="text-muted">
                                        {{ $h->cpcl->nama_ketua }} · {{ $h->cpcl->lokasi ?? '-' }}
                                    </small>
                                </td>

                                {{-- Bidang --}}
                                <td>
                                    <span class="badge bg-label-info">{{ $h->cpcl->bidang ?? '-' }}</span>
                                </td>

                                {{-- z* --}}
                                <td class="text-center fw-bold text-primary">
                                    {{ number_format($h->nilai_z, 4) }}
                                </td>

                                {{-- Skor % --}}
                                <td class="text-center">
                                    <div class="fw-bold mb-1">{{ $h->skor_akhir }}%</div>
                                    <div class="progress" style="height:6px;">
                                        <div class="progress-bar
                                            {{ $h->skor_akhir >= 80 ? 'bg-success' : ($h->skor_akhir >= 60 ? 'bg-primary' : ($h->skor_akhir >= 40 ? 'bg-warning' : 'bg-danger')) }}"
                                             style="width:{{ $h->skor_akhir }}%">
                                        </div>
                                    </div>
                                </td>

                                {{-- Skala prioritas --}}
                                <td class="text-center">
                                    <span class="badge {{ $badgeClass }} py-2 px-3">
                                        {{ $h->skala_prioritas ?? '-' }}
                                    </span>
                                    <div class="small text-muted mt-1 fst-italic">
                                        {{ $h->interpretasi ?? '' }}
                                    </div>
                                </td>

                                {{-- Status layak --}}
                                <td class="text-center">
                                    <span class="badge {{ $h->status_kelayakan === 'Layak' ? 'bg-success' : 'bg-danger' }} py-2 px-3">
                                        {{ $h->status_kelayakan }}
                                    </span>
                                </td>

                                {{-- Tombol detail --}}
                                <td class="text-center no-print">
                                    <a href="{{ route('admin.perhitungan.detail', $h->cpcl_id) }}"
                                       class="btn btn-sm btn-outline-primary"
                                       title="Lihat langkah perhitungan fuzzy">
                                        <i class="bx bx-file-find"></i>
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Footer statistik --}}
                <div class="card-footer bg-white border-top py-3 no-print">
                    <div class="row text-center g-3">
                        <div class="col-4">
                            <small class="text-muted d-block">Rata-rata Skor</small>
                            <strong class="text-primary">{{ number_format($hasilRanking->avg('skor_akhir'), 2) }}%</strong>
                        </div>
                        <div class="col-4">
                            <small class="text-muted d-block">Skor Tertinggi</small>
                            <strong class="text-success">{{ number_format($hasilRanking->max('skor_akhir'), 2) }}%</strong>
                        </div>
                        <div class="col-4">
                            <small class="text-muted d-block">Skor Terendah</small>
                            <strong class="text-danger">{{ number_format($hasilRanking->min('skor_akhir'), 2) }}%</strong>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Referensi skala --}}
        <div class="card mt-4 border-0 shadow-sm no-print">
            <div class="card-body">
                <h6 class="fw-bold mb-3"><i class="bx bx-info-circle me-1"></i> Referensi Skala Prioritas (z*)</h6>
                <div class="row text-center g-3">
                    <div class="col-6 col-md-3">
                        <div class="p-2 border rounded border-success">
                            <small class="text-muted d-block">z* > 0.80</small>
                            <span class="fw-bold text-success">Prioritas I</span>
                            <small class="text-muted d-block">Sangat Diprioritaskan</small>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-2 border rounded border-primary">
                            <small class="text-muted d-block">0.60 < z* ≤ 0.80</small>
                            <span class="fw-bold text-primary">Prioritas II</span>
                            <small class="text-muted d-block">Diprioritaskan</small>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-2 border rounded border-warning">
                            <small class="text-muted d-block">0.40 < z* ≤ 0.60</small>
                            <span class="fw-bold text-warning">Prioritas III</span>
                            <small class="text-muted d-block">Dipertimbangkan</small>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-2 border rounded border-danger">
                            <small class="text-muted d-block">z* ≤ 0.40</small>
                            <span class="fw-bold text-danger">Prioritas IV</span>
                            <small class="text-muted d-block">Tidak Diprioritaskan</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<style>
    @media print {
        .no-print, .layout-navbar, .layout-menu { display: none !important; }
        .content-wrapper { margin: 0 !important; }
        .card { box-shadow: none !important; border: 1px solid #ddd !important; }
        .table { font-size: 0.8rem !important; }
    }
</style>
@endsection
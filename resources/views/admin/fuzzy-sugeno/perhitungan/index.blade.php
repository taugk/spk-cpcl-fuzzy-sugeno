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
                <p class="text-muted mb-0">Hasil Fuzzy Sugeno Orde Nol — diurutkan berdasarkan skor tertinggi (pendekatan bahu)</p>
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

        {{-- INFO BOX v2.0 --}}
        <div class="alert alert-info alert-dismissible fade show mb-4 no-print">
            <i class="bx bx-info-circle me-2"></i>
            <strong>FuzzySugenoService v2.0</strong> — Implementasi Fuzzy Sugeno Orde Nol dengan direct evaluation.
            Semakin tinggi skor (z*) semakin layak. <a href="#" onclick="alert('Lihat dokumentasi untuk detail lebih lanjut.')">Learn more</a>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>

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
                              onsubmit="return confirm('Proses hitung {{ $totalTerverifikasi }} CPCL terverifikasi periode {{ $periode }}?\n\nData ranking yang ada akan diperbarui. Proses ini menggunakan algoritma Fuzzy Sugeno v2.0 yang dioptimasi.')">
                            @csrf
                            <label class="form-label fw-semibold small">Proses Hitung Semua CPCL Terverifikasi</label>
                            <div class="input-group">
                                <input type="number" name="periode" class="form-control"
                                       value="{{ $periode }}" min="2020" max="2099">
                                <button type="submit" class="btn btn-primary" {{ $totalTerverifikasi == 0 ? 'disabled' : '' }}>
                                    <i class="bx bx-calculator me-1"></i> Hitung & Ranking
                                </button>
                            </div>
                            <small class="text-muted mt-1 d-block">
                                <i class="bx bx-info-circle me-1"></i>
                                <strong>{{ $totalTerverifikasi }}</strong> CPCL terverifikasi periode {{ $periode }}.
                                @if($totalBelumDihitung > 0)
                                    <span class="text-warning fw-bold">{{ $totalBelumDihitung }} belum dihitung.</span>
                                @else
                                    <span class="text-success">✅ Semua sudah dihitung dan di-ranking.</span>
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
                        {{ $totalTerverifikasi }} CPCL terverifikasi menggunakan Fuzzy Sugeno v2.0.
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
                                // ✅ FIX v2.0: Gunakan ranking dari hasil_fuzzy
                                $rank       = $h->ranking ?? '-';
                                $rowClass   = $rank == 1 ? 'table-warning' : '';
                                $badgeClass = match($h->skala_prioritas ?? '') {
                                    'Prioritas I'   => 'bg-success',
                                    'Prioritas II'  => 'bg-primary',
                                    'Prioritas III' => 'bg-warning text-dark',
                                    'Prioritas IV'  => 'bg-secondary',
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

                                {{-- z* (nilai_z) --}}
                                <td class="text-center fw-bold text-primary">
                                    {{ number_format($h->nilai_z, 4) }}
                                </td>

                                {{-- Skor % (skor_akhir) --}}
                                <td class="text-center">
                                    <div class="fw-bold mb-1">{{ number_format($h->skor_akhir, 2) }}%</div>
                                    <div class="progress" style="height:6px;">
                                        <div class="progress-bar
                                            {{ $h->skor_akhir >= 80 ? 'bg-success' : ($h->skor_akhir >= 60 ? 'bg-primary' : ($h->skor_akhir >= 40 ? 'bg-warning' : 'bg-danger')) }}"
                                             style="width:{{ min($h->skor_akhir, 100) }}%">
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
                                       title="Lihat langkah-langkah perhitungan Fuzzy Sugeno v2.0">
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

        {{-- REFERENSI SKALA PRIORITAS --}}
        <div class="card mt-4 border-0 shadow-sm no-print">
            <div class="card-body">
                <h6 class="fw-bold mb-3">
                    <i class="bx bx-info-circle me-1"></i> 
                    Referensi Skala Prioritas (z*) — Pendekatan Bahu (Semakin Tinggi Semakin Layak)
                </h6>
                <div class="row text-center g-3">
                    <div class="col-6 col-md-3">
                        <div class="p-3 border rounded border-success">
                            <div class="fw-bold text-success mb-1">z* > 0.80</div>
                            <span class="badge bg-success mb-2 d-inline-block">Prioritas I</span>
                            <small class="text-muted d-block">Sangat Diprioritaskan</small>
                            <small class="text-success d-block mt-1">✅ Layak</small>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-3 border rounded border-primary">
                            <div class="fw-bold text-primary mb-1">0.60 < z* ≤ 0.80</div>
                            <span class="badge bg-primary mb-2 d-inline-block">Prioritas II</span>
                            <small class="text-muted d-block">Diprioritaskan</small>
                            <small class="text-success d-block mt-1">✅ Layak</small>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-3 border rounded border-warning">
                            <div class="fw-bold text-warning mb-1">0.40 < z* ≤ 0.60</div>
                            <span class="badge bg-warning text-dark mb-2 d-inline-block">Prioritas III</span>
                            <small class="text-muted d-block">Dipertimbangkan</small>
                            <small class="text-danger d-block mt-1">❌ Tidak Layak</small>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-3 border rounded border-danger">
                            <div class="fw-bold text-danger mb-1">z* ≤ 0.40</div>
                            <span class="badge bg-secondary mb-2 d-inline-block">Prioritas IV</span>
                            <small class="text-muted d-block">Tidak Diprioritaskan</small>
                            <small class="text-danger d-block mt-1">❌ Tidak Layak</small>
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
@extends('admin.layouts.app')

@section('title', 'Perhitungan & Ranking CPCL')

@section('content')
<div class="content-wrapper">
    <div class="container-xxl container-p-y">

        {{-- HEADER --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1"><i class="bx bx-trophy text-warning me-2"></i>Perhitungan & Ranking Kelayakan CPCL</h4>
                <p class="text-muted mb-0">Hasil Fuzzy Sugeno Orde Nol — diurutkan berdasarkan skor tertinggi (pendekatan bahu)</p>
            </div>
            <button onclick="window.print()" class="btn btn-outline-secondary no-print">
                <i class="bx bx-printer me-1"></i> Cetak
            </button>
        </div>

        {{-- PANEL KONTROL --}}
        <div class="card border-0 shadow-sm mb-4 no-print">
            <div class="card-body">
                {{-- Menggunakan align-items-start agar label sejajar di atas --}}
                <div class="row g-3 align-items-start">
                    
                    {{-- Filter Periode --}}
                    <div class="col-12 col-md-3">
                        <form method="GET" action="{{ route('admin.perhitungan.index') }}">
                            <label class="form-label fw-semibold small">Filter Periode</label>
                            <select name="periode" class="form-select mt-1" onchange="this.form.submit()">
                                @foreach($periodeList as $p)
                                    <option value="{{ $p }}" {{ $periode == $p ? 'selected' : '' }}>Tahun {{ $p }}</option>
                                @endforeach
                            </select>
                        </form>
                    </div>

                    {{-- Tombol Hitung --}}
                    <div class="col-12 col-md-6">
                        <form method="POST" action="{{ route('admin.perhitungan.proses') }}" onsubmit="return confirm('Proses hitung {{ $totalTerverifikasi }} CPCL periode {{ $periode }}?')">
                            @csrf
                            <label class="form-label fw-semibold small">Proses Hitung Semua CPCL Terverifikasi</label>
                            <div class="input-group mt-1">
                                <input type="number" name="periode" class="form-control" value="{{ $periode }}" min="2020" max="2099">
                                <button type="submit" class="btn btn-primary" {{ $totalTerverifikasi == 0 ? 'disabled' : '' }}>
                                    <i class="bx bx-calculator me-1"></i> Hitung & Ranking
                                </button>
                            </div>
                            <small class="text-muted mt-1 d-block">
                                <strong>{{ $totalTerverifikasi }}</strong> CPCL. 
                                @if($totalBelumDihitung > 0) <span class="text-warning fw-bold">{{ $totalBelumDihitung }} belum dihitung.</span>
                                @else <span class="text-success">✅ Semua sudah dihitung.</span> @endif
                            </small>
                        </form>
                    </div>

                    {{-- Statistik --}}
                    @if($hasilRanking->isNotEmpty())
                    <div class="col-12 col-md-3">
                        <div class="row g-2 text-center">
                            <div class="col-6">
                                <div class="p-2 rounded bg-label-success border"><div class="fw-bold fs-5 text-success">{{ $hasilRanking->where('status_kelayakan', 'Layak')->count() }}</div><small class="text-muted">Layak</small></div>
                            </div>
                            <div class="col-6">
                                <div class="p-2 rounded bg-label-danger border"><div class="fw-bold fs-5 text-danger">{{ $hasilRanking->where('status_kelayakan', 'Tidak Layak')->count() }}</div><small class="text-muted">Tidak Layak</small></div>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- TABEL --}}
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="fw-bold mb-0"><i class="bx bx-list-ol me-1"></i> Hasil Ranking — Periode {{ $periode }}</h6>
            </div>
            @if($hasilRanking->isEmpty())
                <div class="card-body text-center py-5 text-muted">Belum ada data untuk periode {{ $periode }}</div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-uppercase small fw-bold text-muted">
                            <tr>
                                <th class="text-center">Rank</th>
                                <th>Kelompok Tani</th>
                                <th>Bidang</th>
                                <th class="text-center">z*</th>
                                <th class="text-center">Skor</th>
                                <th class="text-center">Skala Prioritas</th>
                                <th class="text-center">Status</th>
                                <th class="text-center no-print">Detail</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($hasilRanking as $h)
                                @php
                                    $rank = $h->ranking ?? '-';
                                    $badgeClass = match($h->skala_prioritas ?? '') {
                                        'Prioritas I' => 'bg-success', 'Prioritas II' => 'bg-primary', 'Prioritas III' => 'bg-warning text-dark', default => 'bg-secondary',
                                    };
                                @endphp
                                <tr class="{{ $rank == 1 ? 'table-warning' : '' }}">
                                    <td class="text-center fw-bold">@if($rank <= 3) <span class="fs-5">@if($rank==1)🥇@elseif($rank==2)🥈@else🥉@endif</span> @else #{{ $rank }} @endif</td>
                                    <td><div class="fw-semibold">{{ $h->cpcl->nama_kelompok }}</div><small class="text-muted">{{ $h->cpcl->nama_ketua }}</small></td>
                                    <td><span class="badge bg-label-info">{{ $h->cpcl->bidang ?? '-' }}</span></td>
                                    <td class="text-center fw-bold text-primary">{{ number_format($h->nilai_z, 4) }}</td>
                                    <td class="text-center">
                                        <div class="fw-bold mb-1">{{ number_format($h->skor_akhir, 2) }}%</div>
                                        <div class="progress" style="height:6px;"><div class="progress-bar {{ $h->skor_akhir >= 60 ? 'bg-primary' : 'bg-danger' }}" style="width:{{ min($h->skor_akhir, 100) }}%"></div></div>
                                    </td>
                                    <td class="text-center"><span class="badge {{ $badgeClass }} py-2 px-3">{{ $h->skala_prioritas ?? '-' }}</span></td>
                                    <td class="text-center"><span class="badge {{ $h->status_kelayakan === 'Layak' ? 'bg-success' : 'bg-danger' }} py-2 px-3">{{ $h->status_kelayakan }}</span></td>
                                    <td class="text-center no-print"><a href="{{ route('admin.perhitungan.detail', $h->cpcl_id) }}" class="btn btn-sm btn-outline-primary"><i class="bx bx-file-find"></i></a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- REFERENSI --}}
        <div class="card mt-4 border-0 shadow-sm no-print">
            <div class="card-body">
                <h6 class="fw-bold mb-3"><i class="bx bx-info-circle me-1"></i> Referensi Skala Prioritas (z*)</h6>
                <div class="row text-center g-3">
                    @foreach(['Prioritas I' => 'z* > 0.80|border-success|text-success', 'Prioritas II' => '0.60 < z* ≤ 0.80|border-primary|text-primary', 'Prioritas III' => '0.40 < z* ≤ 0.60|border-warning|text-warning', 'Prioritas IV' => 'z* ≤ 0.40|border-danger|text-danger'] as $label => $meta)
                        @php [$val, $border, $text] = explode('|', $meta); @endphp
                        <div class="col-6 col-md-3">
                            <div class="p-3 border rounded {{ $border }}">
                                <div class="fw-bold {{ $text }} mb-1">{{ $val }}</div>
                                <span class="badge {{ str_replace('text-', 'bg-', $text) }} mb-2">{{ $label }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

    </div>
</div>

<style>
    @media print { .no-print, .layout-navbar, .layout-menu { display: none !important; } .content-wrapper { margin: 0 !important; } .card { box-shadow: none !important; border: 1px solid #ddd !important; } }
</style>
@endsection
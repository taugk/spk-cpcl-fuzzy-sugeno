@extends('admin.layouts.app')

@section('title', 'Dashboard UPTD')

@section('content')
<div class="content-wrapper">
    <div class="container-xxl container-p-y">
        
        <h4 class="fw-bold py-3 mb-4">Dashboard Monitoring UPTD</h4>

        {{-- ROW STATISTIK (WIDGETS) --}}
        <div class="row">
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card shadow-none border">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between">
                            <span class="badge bg-label-primary rounded-pill"><i class="bx bx-group"></i></span>
                        </div>
                        <span class="fw-semibold d-block mb-1">Total CPCL</span>
                        <h3 class="card-title mb-2">{{ $stats['total_cpcl'] }}</h3>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card shadow-none border">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between">
                            <span class="badge bg-label-warning rounded-pill"><i class="bx bx-time"></i></span>
                        </div>
                        <span class="fw-semibold d-block mb-1">Menunggu Verifikasi</span>
                        <h3 class="card-title mb-2">{{ $stats['pending'] }}</h3>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card shadow-none border">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between">
                            <span class="badge bg-label-success rounded-pill"><i class="bx bx-check-shield"></i></span>
                        </div>
                        <span class="fw-semibold d-block mb-1">Terverifikasi</span>
                        <h3 class="card-title mb-2">{{ $stats['verified'] }}</h3>
                    </div>
                </div>
            </div>
        </div>

        {{-- TABEL AKTIVITAS TERBARU --}}
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <h5 class="mb-0">Aktivitas Terbaru</h5>
                <a href="{{ route('uptd.cpcl.index') }}" class="btn btn-sm btn-primary">Lihat Semua</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Kelompok</th>
                            <th>Status</th>
                            <th>Tanggal Masuk</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recent_activities as $activity)
                        <tr>
                            <td>{{ $activity->nama_kelompok }}</td>
                            <td>
                                <span class="badge {{ $activity->status == 'pending' ? 'bg-label-warning' : 'bg-label-success' }}">
                                    {{ ucfirst($activity->status) }}
                                </span>
                            </td>
                            <td>{{ $activity->created_at->diffForHumans() }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
@extends('admin.layouts.app')

@section('title', 'Data Sub-Kriteria')

@section('content')
<div class="content-wrapper">
    <div class="container-xxl container-p-y">
        <div class="card">

            {{-- HEADER --}}
            <div class="card-header border-bottom d-flex flex-column flex-md-row justify-content-between align-items-md-center">
                <h5 class="card-title mb-3 mb-md-0">
                    Data Sub-Kriteria (Himpunan Fuzzy)
                </h5>

                <div class="d-flex gap-2">
                    <button type="button"
                            class="btn btn-primary"
                            data-bs-toggle="modal"
                            data-bs-target="#modalTambahSubKriteria">
                        <i class="bx bx-plus me-1"></i>
                        <span class="d-none d-sm-inline-block">
                            Tambah Sub-Kriteria
                        </span>
                    </button>
                </div>
            </div>

            {{-- FILTER & SEARCH --}}
            <div class="card-body mt-3">
                <div class="row align-items-center justify-content-between">
                    <div class="col-md-6 mb-3 mb-md-0">
                        <div class="d-flex align-items-center gap-2">
                            <span class="text-muted">Filter Kriteria:</span>
                            <select class="form-select form-select-sm w-auto"
                                    onchange="window.location.href='?filter=' + this.value">
                                <option value="">Semua</option>
                                @foreach($data as $k)
                                    <option value="{{ $k->id }}"
                                        {{ request('filter') == $k->id ? 'selected' : '' }}>
                                        {{ $k->nama_kriteria }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="d-flex justify-content-md-end gap-2">
                            <input type="search"
                                   class="form-control form-control-sm w-auto"
                                   placeholder="Cari Himpunan...">
                        </div>
                    </div>
                </div>
            </div>

            {{-- TABLE --}}
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-light text-center">
                        <tr>
                            <th width="5%">No</th>
                            <th width="20%">Kriteria Induk</th>
                            <th width="20%">Nama Sub-Kriteria</th>
                            <th width="15%">Tipe Kurva</th>
                            <th width="30%">Parameter / Nilai (μ)</th>
                            <th width="10%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>

                        @php $no = $data->firstItem() ?? 1; @endphp

                        @forelse($data as $kriteria)
                            @php $rowspan = $kriteria->subKriteria->count() > 0 ? $kriteria->subKriteria->count() : 1; @endphp

                            {{-- Jika Kriteria belum memiliki Sub Kriteria sama sekali --}}
                            @if($kriteria->subKriteria->isEmpty())
                                <tr>
                                    <td class="text-center">{{ $no++ }}</td>
                                    <td>
                                        <strong>{{ $kriteria->nama_kriteria }}</strong><br>
                                        <span class="badge bg-label-{{ $kriteria->jenis_kriteria == 'kontinu' ? 'primary' : 'warning' }} mt-1">
                                            {{ ucfirst($kriteria->jenis_kriteria) }}
                                        </span>
                                    </td>
                                    <td colspan="4" class="text-center text-muted fst-italic">Belum ada data sub-kriteria.</td>
                                </tr>
                            @else
                                {{-- Looping Sub Kriteria --}}
                                @foreach($kriteria->subKriteria as $index => $sub)
                                    <tr>
                                        @if($index === 0)
                                            <td rowspan="{{ $rowspan }}" class="text-center">
                                                {{ $no++ }}
                                            </td>
                                            <td rowspan="{{ $rowspan }}">
                                                <strong>{{ $kriteria->nama_kriteria }}</strong><br>
                                                <span class="badge bg-label-{{ $kriteria->jenis_kriteria == 'kontinu' ? 'primary' : 'warning' }} mt-1">
                                                    {{ ucfirst($kriteria->jenis_kriteria) }}
                                                </span>
                                            </td>
                                        @endif

                                        <td>
                                            <span class="fw-medium text-primary">
                                                {{ $sub->nama_sub_kriteria }}
                                            </span>
                                        </td>

                                        <td class="text-center">
                                            @if($sub->tipe_kurva == 'diskrit')
                                                <span class="badge bg-secondary">Diskrit</span>
                                            @elseif($sub->tipe_kurva == 'bahu_kiri')
                                                <span class="badge bg-info">Bahu Kiri</span>
                                            @elseif($sub->tipe_kurva == 'bahu_kanan')
                                                <span class="badge bg-success">Bahu Kanan</span>
                                            @else
                                                <span class="badge bg-dark">Trapesium</span>
                                            @endif
                                        </td>

                                        <td>
                                            {{-- LOGIKA PENAMPILAN PARAMETER --}}
                                            @if($kriteria->jenis_kriteria == 'diskrit')
                                                <div class="d-flex justify-content-between align-items-center px-2">
                                                    <span class="text-muted small">Nilai Keanggotaan:</span>
                                                    <strong>{{ $sub->nilai_konsekuen }}</strong>
                                                </div>
                                            @else
                                                <div class="d-flex justify-content-center gap-2 small">
                                                    <span class="badge bg-label-secondary" title="Batas Bawah (a)">{{ $sub->batas_bawah ?? '-' }}</span>
                                                    <span class="badge bg-label-secondary" title="Batas Tengah 1 (b)">{{ $sub->batas_tengah_1 ?? '-' }}</span>
                                                    <span class="badge bg-label-secondary" title="Batas Tengah 2 (c)">{{ $sub->batas_tengah_2 ?? '-' }}</span>
                                                    <span class="badge bg-label-secondary" title="Batas Atas (d)">{{ $sub->batas_atas ?? '-' }}</span>
                                                </div>
                                            @endif
                                        </td>

                                        @if($index === 0)
                                            <td rowspan="{{ $rowspan }}" class="text-center">
                                                <div class="d-flex justify-content-center gap-2">
                                                    {{-- EDIT --}}
                                                    <button type="button"
                                                            class="btn btn-icon btn-outline-primary btn-sm"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#modalEditSubKriteria"
                                                            onclick="isiFormEditSub('{{ $kriteria->id }}', '{{ $kriteria->nama_kriteria }}', '{{ $kriteria->jenis_kriteria }}', '{{ $kriteria->subKriteria->toJson() }}')">
                                                        <i class="bx bx-edit-alt"></i>
                                                    </button>

                                                    {{-- DELETE --}}
                                                    <form action="{{ route('admin.sub-kriteria.destroy', $kriteria->id) }}"
                                                          method="POST">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="button"
                                                                class="btn btn-icon btn-outline-danger btn-sm btn-delete-confirm">
                                                            <i class="bx bx-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            @endif

                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <i class="bx bx-folder-open fs-1 text-muted mb-2"></i><br>
                                    Data belum tersedia.
                                </td>
                            </tr>
                        @endforelse

                    </tbody>
                </table>
            </div>

            {{-- PAGINATION --}}
            <div class="card-footer d-flex justify-content-between align-items-center">
                <small class="text-muted">
                    Showing {{ $data->firstItem() ?? 0 }}
                    to {{ $data->lastItem() ?? 0 }}
                    of {{ $data->total() }} entries
                </small>

                {{ $data->appends(request()->query())->links('pagination::bootstrap-5') }}
            </div>

        </div>
    </div>
</div>

{{-- Memanggil Modal yang baru saja kita buat di pembahasan sebelumnya --}}
@include('admin.partials.modals.sub_kriteria_modal')
@endsection
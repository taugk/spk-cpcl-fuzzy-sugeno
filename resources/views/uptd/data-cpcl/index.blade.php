@extends('uptd.layouts.app')

@section('title', 'Data Master CPCL')

@section('content')
<div class="content-wrapper">
    <div class="container-xxl container-p-y">

        <div class="card">
            <div class="card-header border-bottom d-flex flex-column flex-md-row align-items-center justify-content-between">
                <div>
                    <h5 class="card-title mb-0">Master Data CPCL</h5>
                    <small class="text-muted">Data Calon Petani & Calon Lokasi (Periode 2026)</small>
                </div>
                
                <div class="d-flex gap-2 mt-3 mt-md-0">
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalImport">
                        <i class="bx bx-upload me-1"></i> Import Excel
                    </button>

                    <div class="btn-group">
                        <button type="button" class="btn btn-outline-success dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="bx bx-export me-1"></i> Export
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#"><i class="bx bx-table me-1"></i> Excel</a></li>
                            <li><a class="dropdown-item" href="#"><i class="bx bxs-file-pdf me-1"></i> PDF</a></li>
                        </ul>
                    </div>
                    <a href="{{ route('uptd.add.cpcl') }}" class="btn btn-success">
                        <i class="bx bx-plus me-1"></i> Tambah CPCL
                    </a>
                </div>
            </div>

            {{-- FILTER SECTION --}}
            <div class="card-body mt-3">
                <form action="{{ route('uptd.cpcl.index') }}" method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Kecamatan</label>
                        <select name="kecamatan" id="filter-kecamatan" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">Sedang memuat...</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Rencana Usaha</label>
                        <select name="rencana_usaha" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">Semua Usulan</option>
                            @php
                                $usulans = \App\Models\Cpcl::select('rencana_usaha')
                                            ->whereNotNull('rencana_usaha')
                                            ->distinct()
                                            ->orderBy('rencana_usaha', 'asc')
                                            ->pluck('rencana_usaha');
                            @endphp
                            @foreach($usulans as $usulan)
                                <option value="{{ $usulan }}" {{ request('rencana_usaha') == $usulan ? 'selected' : '' }}>
                                    {{ $usulan }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="col-md-6 d-flex align-items-end justify-content-md-end">
                        <div class="input-group input-group-sm w-75">
                            <span class="input-group-text"><i class="bx bx-search"></i></span>
                            <input type="text" name="search" class="form-control" placeholder="Cari Poktan / NIK Ketua..." value="{{ request('search') }}">
                            <button class="btn btn-success" type="submit">Cari</button>
                            @if(request()->anyFilled(['kecamatan', 'rencana_usaha', 'search']))
                                <a href="{{ route('uptd.cpcl.index') }}" class="btn btn-outline-danger" title="Reset Filter">
                                    <i class="bx bx-x"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>

            <div class="table-responsive text-nowrap">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th width="5%">No</th>
                            <th>Kelompok Tani & Ketua</th>
                            <th>Lokasi</th>
                            <th>Rencana Usaha</th>
                            <th>Data Teknis</th> 
                            <th>Lampiran</th>
                            <th>Status</th>
                            <th width="15%" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                        @forelse($data as $i => $row)
                        <tr>
                            <td>{{ $data->firstItem() + $i }}</td>
                            <td>
                                <div class="d-flex flex-column">
                                    <span class="fw-bold text-dark">{{ $row->nama_kelompok }}</span>
                                    <small class="text-muted">Ketua: {{ $row->nama_ketua }}</small>
                                    <small class="text-muted" style="font-size: 0.75rem;">NIK: {{ $row->nik_ketua }}</small>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex flex-column gap-1">
                                    <span class="badge bg-label-secondary text-wrap text-start" style="max-width: 150px; line-height: 1.4;">
                                        @if(isset($row->alamat->desa) || isset($row->alamat->kecamatan))
                                            <small class="text-muted" style="font-size: 0.75rem;">
                                                {{ $row->alamat->desa ?? '-' }}, {{ $row->alamat->kecamatan ?? '-' }}
                                            </small>
                                        @endif
                                    </span>
                                    @if(isset($row->latitude) && isset($row->longitude))
                                        <a href="https://www.google.com/maps?q={{ $row->latitude }},{{ $row->longitude }}" target="_blank" class="badge bg-label-success small mt-1" style="width: max-content;">
                                            <i class="bx bx-map-pin me-1"></i> Lihat Peta
                                        </a>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <span class="fw-semibold text-dark d-block">{{ $row->rencana_usaha }}</span>
                                <small class="badge bg-label-warning text-uppercase" style="font-size: 0.65rem;">
                                    {{ $row->bidang }}
                                </small>
                            </td>
                            <td>
                                <div class="d-flex flex-column gap-1" style="font-size: 0.85rem;">
                                    <span><i class="bx bx-area me-1 text-primary"></i> <strong>{{ $row->luas_lahan }}</strong> Ha</span>
                                    <span><i class="bx bx-trending-up me-1 text-success"></i> <strong>{{ $row->hasil_panen }}</strong> T/Ha</span>
                                    <span class="text-capitalize"><i class="bx bx-lock-alt me-1 text-warning"></i> {{ $row->status_lahan }}</span>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex gap-2">
                                    @if($row->file_proposal)
                                        <a href="javascript:void(0)" onclick="previewFile('{{ asset('storage/' . $row->file_proposal) }}', 'Proposal - {{ $row->nama_kelompok }}')" title="Proposal">
                                            <i class='bx bxs-file-pdf text-danger fs-4'></i>
                                        </a>
                                    @endif
                                    @if($row->file_ktp)
                                        <a href="javascript:void(0)" onclick="previewFile('{{ asset('storage/' . $row->file_ktp) }}', 'KTP - {{ $row->nama_ketua }}')" title="KTP">
                                            <i class='bx bxs-id-card text-info fs-4'></i>
                                        </a>
                                    @endif
                                </div>
                            </td>
                            <td>
                                @php
                                    $statusClass = [
                                        'baru' => 'bg-label-info',
                                        'terverifikasi' => 'bg-label-success',
                                        'perlu_perbaikan' => 'bg-label-warning',
                                        'ditolak' => 'bg-label-danger'
                                    ][strtolower($row->status)] ?? 'bg-label-secondary';
                                @endphp
                                <span class="badge {{ $statusClass }}">{{ ucfirst(str_replace('_', ' ', $row->status)) }}</span>
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    <a href="{{ route('uptd.cpcl.show', $row->id) }}" class="btn btn-icon btn-sm btn-label-info" title="Detail Data">
                                        <i class="bx bx-show"></i>
                                    </a>
                                    
                                    <form action="{{ route('uptd.cpcl.destroy', $row->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-icon btn-sm btn-label-danger btn-delete-confirm">
                                            <i class="bx bx-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">Data tidak ditemukan</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="card-footer d-flex flex-column flex-md-row justify-content-between align-items-center border-top">
                <small class="text-muted">
                    Showing {{ $data->firstItem() ?? 0 }} to {{ $data->lastItem() ?? 0 }} of {{ $data->total() ?? 0 }} entries
                </small>
                {{ $data->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalImport" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Import & Preview Excel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Pilih File Excel (.xlsx / .xls)</label>
                        <input type="file" id="inputExcel" class="form-control" accept=".xlsx, .xls">
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <small class="text-muted">Pastikan kolom sesuai dengan template sistem.</small>
                    </div>
                </div>

                <div id="previewContainer" style="display: none;" class="mt-4">
                    <h6 class="fw-bold text-primary"><i class="bx bx-table me-1"></i> Preview 10 Baris Pertama:</h6>
                    <div class="table-responsive border rounded" style="max-height: 350px;">
                        <table class="table table-sm table-striped table-hover mb-0" id="tablePreviewSheetJS">
                            <thead class="table-light sticky-top"></thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
                
                <div id="emptyPreview" class="text-center py-5 border rounded bg-light mt-3">
                    <i class="bx bx-spreadsheet display-4 text-muted"></i>
                    <p class="mb-0">Silahkan pilih file untuk melihat preview data.</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-success" id="btnSimpanImport" disabled>
                    <i class="bx bx-check-double me-1"></i> Konfirmasi & Simpan
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalPreview" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Preview Lampiran</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0" style="background: #f1f8e9;">
                <div id="previewContent" style="height: 70vh; overflow: auto;"></div>
            </div>
            <div class="modal-footer">
                <a href="javascript:void(0)" id="downloadBtn" class="btn btn-outline-success btn-sm">
                    <i class="bx bx-download me-1"></i> Download
                </a>
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>

<script>
    let fileToUpload = null;

    // --- LOGIKA SHEETJS PREVIEW ---
    document.getElementById('inputExcel').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (!file) return;
        
        fileToUpload = file;
        const reader = new FileReader();

        reader.onload = function(e) {
            const data = new Uint8Array(e.target.result);
            const workbook = XLSX.read(data, {type: 'array'});
            const firstSheet = workbook.Sheets[workbook.SheetNames[0]];
            const jsonData = XLSX.utils.sheet_to_json(firstSheet, {header: 1});

            renderPreview(jsonData);
        };
        reader.readAsArrayBuffer(file);
    });

    function renderPreview(data) {
        const thead = document.querySelector('#tablePreviewSheetJS thead');
        const tbody = document.querySelector('#tablePreviewSheetJS tbody');
        const btnSimpan = document.getElementById('btnSimpanImport');
        
        thead.innerHTML = '';
        tbody.innerHTML = '';

        if (data.length > 0) {
            // Render Header
            let headHtml = '<tr>';
            data[0].forEach(cell => headHtml += `<th>${cell || ''}</th>`);
            thead.innerHTML = headHtml + '</tr>';

            // Render Body (Max 10 baris)
            for (let i = 1; i < Math.min(data.length, 11); i++) {
                let bodyHtml = '<tr>';
                data[i].forEach(cell => bodyHtml += `<td>${cell || ''}</td>`);
                tbody.innerHTML += bodyHtml + '</tr>';
            }

            document.getElementById('previewContainer').style.display = 'block';
            document.getElementById('emptyPreview').style.display = 'none';
            btnSimpan.disabled = false;
        }
    }

    // --- AJAX SIMPAN KE SERVER ---
    document.getElementById('btnSimpanImport').addEventListener('click', function() {
        const btn = this;
        const formData = new FormData();
        formData.append('file_excel', fileToUpload);
        formData.append('_token', "{{ csrf_token() }}");

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Processing...';

        fetch("{{ route('uptd.cpcl.import') }}", {
            method: "POST",
            body: formData
        })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                Swal.fire('Berhasil!', res.message, 'success').then(() => location.reload());
            } else {
                Swal.fire('Gagal!', res.message, 'error');
                btn.disabled = false;
                btn.innerHTML = '<i class="bx bx-check-double me-1"></i> Konfirmasi & Simpan';
            }
        })
        .catch(() => {
            Swal.fire('Error!', 'Terjadi kesalahan jaringan.', 'error');
            btn.disabled = false;
        });
    });

    // --- FUNGSI PREVIEW LAMPIRAN (LAMA) ---
    function previewFile(url, title) {
        const previewContent = document.getElementById('previewContent');
        const modalTitle = document.getElementById('modalTitle');
        const downloadBtn = document.getElementById('downloadBtn');
        
        previewContent.innerHTML = '<div class="d-flex justify-content-center p-5"><div class="spinner-border text-primary" role="status"></div></div>';
        modalTitle.innerText = title;
        downloadBtn.href = url;

        const extension = url.split('.').pop().toLowerCase();
        
        setTimeout(() => {
            let html = '';
            if (extension === 'pdf') {
                html = `<iframe src="${url}" width="100%" height="100%" style="border:none;"></iframe>`;
            } else if (['jpg', 'jpeg', 'png', 'webp'].includes(extension)) {
                html = `<div class="d-flex justify-content-center align-items-center h-100 p-3">
                            <img src="${url}" class="img-fluid rounded shadow" style="max-height: 100%; object-fit: contain;">
                        </div>`;
            }
            previewContent.innerHTML = html || '<p class="text-center p-5">Preview tidak tersedia.</p>';
        }, 300);

        new bootstrap.Modal(document.getElementById('modalPreview')).show();
    }

    // --- FILTER KECAMATAN API ---
    document.addEventListener('DOMContentLoaded', function(){
        const filterKecamatan = document.getElementById('filter-kecamatan');
        const currentKecamatan = "{{ request('kecamatan') }}";

        fetch(`/proxy-wilayah/districts/32.08`)
            .then(res => res.json())
            .then(data => {
                filterKecamatan.innerHTML = '<option value="">Semua Kecamatan</option>';
                data.data.forEach(item => {
                    const opt = document.createElement('option');
                    opt.value = item.name;
                    opt.text = item.name;
                    if(item.name === currentKecamatan) opt.selected = true;
                    filterKecamatan.appendChild(opt);
                });
            })
            .catch(() => {
                filterKecamatan.innerHTML = '<option value="">Gagal memuat</option>';
            });
    });
</script>

<style>
    .table-hover tbody tr:hover { background-color: #f1f8e9; }
    .sticky-top { top: 0; z-index: 10; background: #fff; }
    #tablePreviewSheetJS th { font-size: 0.75rem; text-transform: uppercase; }
    #tablePreviewSheetJS td { font-size: 0.8rem; }
</style>
@endpush
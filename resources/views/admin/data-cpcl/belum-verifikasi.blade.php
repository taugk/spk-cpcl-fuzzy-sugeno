@extends('admin.layouts.app')

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
                    <div class="btn-group">
                        <button type="button" class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="bx bx-export me-1"></i> Export
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#"><i class="bx bx-table me-1"></i> Excel</a></li>
                            <li><a class="dropdown-item" href="#"><i class="bx bxs-file-pdf me-1"></i> PDF</a></li>
                        </ul>
                    </div>
                    <a href="{{ route('admin.add.cpcl') }}" class="btn btn-primary">
                        <i class="bx bx-plus me-1"></i> Tambah CPCL
                    </a>
                </div>
            </div>

            {{-- FILTER SECTION AKTIF --}}
            <div class="card-body mt-3">
                <form action="{{ route('admin.cpcl.belum-verifikasi') }}" method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Kecamatan</label>
                        <select name="kecamatan" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">Semua Kecamatan</option>
                            @php
                                $kecamatans = [
                                    'Ciawigebang', 'Ciawilor', 'Cidahu', 'Cigandamekar', 'Cigugur', 'Cilebak', 'Cilimus', 'Cimahi', 
                                    'Ciniru', 'Cipicung', 'Ciwaru', 'Darma', 'Garawangi', 'Hantara', 'Jalaksana', 'Japara', 
                                    'Kadugede', 'Kalimanggis', 'Karangkancana', 'Kramatmulya', 'Kuningan', 'Luragung', 'Maleber', 
                                    'Mandirancan', 'Nusaherang', 'Pancalang', 'Pasawahan', 'Lebakwangi', 'Selajambe', 'Sindangagung', 'Subang'
                                ];
                                sort($kecamatans);
                            @endphp
                            @foreach($kecamatans as $kec)
                                <option value="{{ $kec }}" {{ request('kecamatan') == $kec ? 'selected' : '' }}>{{ $kec }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Rencana Usaha</label>
                        <select name="rencana_usaha" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">Semua Usulan</option>
                            @php
                                $usulans = [
                                    'Pengembangan Benih' => 'Pengembangan Benih/Bibit',
                                    'Penyediaan Pupuk' => 'Penyediaan Pupuk',
                                    'Pengadaan Alsintan' => 'Pengadaan Alsintan',
                                    'Rehabilitasi Jaringan Irigasi' => 'Rehabilitasi Jaringan Irigasi',
                                    'Peningkatan Produksi' => 'Peningkatan Produksi/Budidaya'
                                ];
                            @endphp
                            @foreach($usulans as $val => $label)
                                <option value="{{ $val }}" {{ request('rencana_usaha') == $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 d-flex align-items-end justify-content-md-end">
                        <div class="input-group input-group-sm w-75">
                            <span class="input-group-text"><i class="bx bx-search"></i></span>
                            <input type="text" name="search" class="form-control" placeholder="Cari Poktan / NIK Ketua..." value="{{ request('search') }}">
                            <button class="btn btn-primary" type="submit">Cari</button>
                            @if(request()->anyFilled(['kecamatan', 'rencana_usaha', 'search']))
                                <a href="{{ route('admin.cpcl.belum-verifikasi') }}" class="btn btn-outline-danger" title="Reset Filter">
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
                                <span class="badge bg-label-secondary mb-1 text-wrap" style="max-width: 150px;">{{ $row->lokasi }}</span><br>
                                <a href="https://www.google.com/maps?q={{ $row->latitude }},{{ $row->longitude }}" target="_blank" class="badge bg-label-primary small">
                                    <i class="bx bx-map-pin me-1"></i> Lihat Peta
                                </a>
                            </td>

                            <td>
                                <span class="fw-semibold text-primary d-block">{{ $row->rencana_usaha }}</span>
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
                                    @if($row->foto_lahan)
                                        <a href="javascript:void(0)" onclick="previewFile('{{ asset('storage/' . $row->foto_lahan) }}', 'Foto Lahan - {{ $row->nama_kelompok }}')" title="Foto Lahan">
                                            <i class='bx bxs-image text-success fs-4'></i>
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
                                <a href="{{ route('admin.cpcl.verify',$row->id) }}" class="btn btn-sm btn-primary">
                                    <i class="bx bx-check-shield"></i> Verifikasi
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bx bx-folder-open display-4 d-block mb-2"></i>
                                Data tidak ditemukan sesuai filter
                            </td>
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

{{-- MODAL PREVIEW --}}
<div class="modal fade" id="modalPreview" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Preview Lampiran</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0" style="background: #f5f5f9;">
                <div id="previewContent" style="height: 70vh; overflow: auto;"></div>
            </div>
            <div class="modal-footer">
                <a href="javascript:void(0)" id="downloadBtn" class="btn btn-outline-primary btn-sm">
                    <i class="bx bx-download me-1"></i> Download File
                </a>
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function previewFile(url, title) {
        const previewContent = document.getElementById('previewContent');
        const modalTitle = document.getElementById('modalTitle');
        const downloadBtn = document.getElementById('downloadBtn');
        
        previewContent.innerHTML = '<div class="d-flex justify-content-center p-5"><div class="spinner-border text-primary" role="status"></div></div>';
        modalTitle.innerText = title;

        downloadBtn.onclick = function(e) {
            e.preventDefault();
            const link = document.createElement('a');
            link.href = url;
            link.setAttribute('download', '');
            link.setAttribute('target', '_blank');
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        };

        const extension = url.split('.').pop().toLowerCase();
        
        setTimeout(() => {
            let html = '';
            if (extension === 'pdf') {
                html = `<iframe src="${url}" width="100%" height="100%" style="border:none;"></iframe>`;
            } else if (['jpg', 'jpeg', 'png', 'webp', 'gif'].includes(extension)) {
                html = `<div class="d-flex justify-content-center align-items-center h-100 p-3">
                            <img src="${url}" class="img-fluid rounded shadow" style="max-height: 100%; object-fit: contain; background: white;">
                        </div>`;
            } else {
                html = `<div class="text-center p-5"><i class='bx bx-file display-1 text-muted'></i><p class="mt-3">Preview tidak didukung.</p></div>`;
            }
            previewContent.innerHTML = html;
        }, 300);

        const previewModal = new bootstrap.Modal(document.getElementById('modalPreview'));
        previewModal.show();
    }

    function confirmDelete(id) {
        if(confirm('Apakah Anda yakin ingin menghapus data ini?')) {
            document.getElementById('delete-form-' + id).submit();
        }
    }
</script>
@endpush
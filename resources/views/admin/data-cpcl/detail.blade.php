@extends('admin.layouts.app')

@section('title', 'Detail CPCL - ' . $cpcl->nama_kelompok)

@section('content')
<div class="content-wrapper">
    <div class="container-xxl flex-grow-1 container-p-y">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-style1 mb-1">
                        <li class="breadcrumb-item"><a href="{{ route('admin.cpcl.index') }}">Master Data</a></li>
                        <li class="breadcrumb-item active">Rincian CPCL</li>
                    </ol>
                </nav>
                <h4 class="fw-bold mb-0">{{ $cpcl->nama_kelompok }}</h4>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.cpcl.index') }}" class="btn btn-label-secondary shadow-sm">
                    <i class="bx bx-chevron-left me-1"></i> Kembali
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-4 col-lg-5 col-md-5">
                <div class="card mb-4 border-0 shadow-sm">
                    <div class="card-body text-center">
                        <div class="mx-auto mb-3">
                            <div class="avatar avatar-xl d-inline-block">
                                <span class="avatar-initial rounded-circle bg-label-primary shadow-sm">
                                    <i class="bx bx-group fs-1"></i>
                                </span>
                            </div>
                        </div>
                        <h5 class="mb-1 fw-bold">{{ $cpcl->nama_kelompok }}</h5>
                        <span class="badge bg-label-warning rounded-pill px-3 mb-4 text-uppercase">
                            {{ $cpcl->status ?? 'Diajukan' }}
                        </span>

                        <div class="d-flex justify-content-around align-items-center border-top pt-3 mt-2">
                            <div>
                                <h5 class="mb-0 fw-bold">{{ $cpcl->luas_lahan }}</h5>
                                <small class="text-muted text-uppercase" style="font-size: 10px;">Luas (Ha)</small>
                            </div>
                            <div class="vr" style="height: 30px;"></div>
                            <div>
                                <h5 class="mb-0 fw-bold">{{ $cpcl->hasil_panen }}</h5>
                                <small class="text-muted text-uppercase" style="font-size: 10px;">Est. Panen (Ton)</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4 border-0 shadow-sm">
                    <div class="card-body pt-4">
                        <h6 class="text-muted text-uppercase small fw-bold mb-3 border-bottom pb-2">Kontak Utama</h6>
                        <div class="d-flex align-items-center mb-3">
                            <div class="badge bg-label-secondary p-2 rounded me-3">
                                <i class="bx bx-user fs-4 text-primary"></i>
                            </div>
                            <div>
                                <p class="mb-0 fw-semibold text-dark">{{ $cpcl->nama_ketua }}</p>
                                <small class="text-muted">NIK: {{ $cpcl->nik_ketua }}</small>
                            </div>
                        </div>
                        <div class="d-flex align-items-start">
                            <div class="badge bg-label-secondary p-2 rounded me-3 flex-shrink-0">
                                <i class="bx bx-map-pin fs-4 text-danger"></i>
                            </div>
                                <div>
                                    {{-- Menampilkan detail jalan / blok / patokan --}}
                                    <p class="mb-1 small fw-medium text-dark lh-base">
                                        {{ $cpcl->lokasi ?? 'Detail alamat belum diatur' }}
                                    </p>
                                    
                                    {{-- Menampilkan wilayah administratif --}}
                                    @if(isset($cpcl->alamat->desa) || isset($cpcl->alamat->kecamatan))
                                        <small class="text-muted d-block lh-sm">
                                            Desa {{ $cpcl->alamat->desa ?? '-' }}, 
                                            Kec. {{ $cpcl->alamat->kecamatan ?? '-' }}, <br>
                                            {{ ucwords(strtolower($cpcl->alamat->kabupaten ?? 'Kabupaten Kuningan')) }}
                                        </small>
                                    @endif
                                </div>
                            </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-header border-bottom py-3 d-flex align-items-center">
                        <i class="bx bx-file-blank text-primary me-2"></i>
                        <h6 class="mb-0 fw-bold">Berkas Pendukung</h6>
                    </div>
                    <div class="card-body pt-2">
                        <div class="list-group list-group-flush">
                            @php
                                $files = [
                                    'file_proposal' => ['label' => 'Proposal Kelompok', 'icon' => 'bxs-file-pdf', 'color' => 'text-danger'],
                                    'file_ktp' => ['label' => 'KTP Elektronik', 'icon' => 'bxs-id-card', 'color' => 'text-info'],
                                    'file_sk' => ['label' => 'SK Kelompok', 'icon' => 'bx-file', 'color' => 'text-primary'],
                                    'foto_lahan' => ['label' => 'Dokumentasi Lahan', 'icon' => 'bxs-image', 'color' => 'text-success']
                                ];
                            @endphp

                            @foreach($files as $key => $attr)
                                @if($cpcl->$key)
                                <a href="javascript:void(0)" onclick="previewFile('{{ asset('storage/' . $cpcl->$key) }}', '{{ $attr['label'] }}')" 
                                   class="list-group-item list-group-item-action d-flex align-items-center px-0">
                                    <i class="bx {{ $attr['icon'] }} {{ $attr['color'] }} fs-4 me-3"></i>
                                    <span class="flex-grow-1 small fw-medium">{{ $attr['label'] }}</span>
                                    <i class="bx bx-chevron-right text-muted"></i>
                                </a>
                                @else
                                <div class="list-group-item d-flex align-items-center px-0 opacity-50">
                                    <i class="bx {{ $attr['icon'] }} text-secondary fs-4 me-3"></i>
                                    <span class="flex-grow-1 small text-muted italic">{{ $attr['label'] }} (Tidak Ada)</span>
                                </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-8 col-lg-7 col-md-7">
                <div class="card mb-4 border-0 shadow-sm">
                    <div class="card-header border-bottom d-flex align-items-center justify-content-between bg-white py-3">
                        <h5 class="card-title mb-0 fw-bold"><i class="bx bx-detail me-2 text-primary"></i>Rincian Teknis Usulan</h5>
                        <div class="dropdown">
                            <button class="btn p-0" type="button" data-bs-toggle="dropdown"><i class="bx bx-dots-vertical-rounded"></i></button>
                            <div class="dropdown-menu dropdown-menu-end">
                                <a class="dropdown-item" href="#"><i class="bx bx-edit-alt me-1"></i> Edit Data</a>
                                <a class="dropdown-item text-danger" href="#"><i class="bx bx-trash me-1"></i> Hapus</a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body pt-4">
                        <div class="row g-4 mb-4">
                            <div class="col-md-6 border-end">
                                <label class="text-muted small text-uppercase fw-bold mb-1 d-block">Rencana Usaha</label>
                                <p class="fw-bold text-dark mb-0">{{ $cpcl->rencana_usaha }}</p>
                            </div>
                            <div class="col-md-6 ps-md-4">
                                <label class="text-muted small text-uppercase fw-bold mb-1 d-block">Bidang Komoditas</label>
                                <p class="fw-bold mb-0 text-primary">{{ $cpcl->bidang }}</p>
                            </div>
                        </div>
                        <div class="row g-4 border-top pt-4">
                            <div class="col-md-6 border-end">
                                <label class="text-muted small text-uppercase fw-bold mb-1 d-block">Masa Berdiri Kelompok</label>
                                <p class="fw-medium mb-0">{{ $cpcl->lama_berdiri }} Tahun</p>
                            </div>
                            <div class="col-md-6 ps-md-4">
                                <label class="text-muted small text-uppercase fw-bold mb-1 d-block">Status Kepemilikan Lahan</label>
                                <span class="badge bg-label-dark text-capitalize px-3">{{ $cpcl->status_lahan }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4 border-0 shadow-sm overflow-hidden">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center">
                                <div class="avatar flex-shrink-0 me-3">
                                    <span class="avatar-initial rounded bg-label-danger shadow-sm"><i class="bx bx-map-pin fs-4"></i></span>
                                </div>
                                <div>
                                    <h6 class="mb-0 fw-bold">Pemetaan Lokasi</h6>
                                    <small class="text-muted">Koordinat Global (GPS)</small>
                                </div>
                            </div>
                            @if($cpcl->latitude)
                            <div class="text-end">
                                <p class="mb-1 small fw-bold">Lat: {{ $cpcl->latitude }} | Long: {{ $cpcl->longitude }}</p>
                                <a href="https://www.google.com/maps?q={{ $cpcl->latitude }},{{ $cpcl->longitude }}" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill">
                                    <i class="bx bx-directions me-1"></i> Navigasi Maps
                                </a>
                            </div>
                            @else
                            <span class="badge bg-label-secondary">Titik belum diatur</span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm border-start border-warning border-5">
                    <div class="card-body py-4">
                        <div class="d-flex">
                            <i class="bx bx-message-dots text-warning fs-3 me-3"></i>
                            <div>
                                <h6 class="fw-bold mb-1">Catatan Terakhir Verifikator</h6>
                                <p class="mb-0 text-dark small lh-base">
                                    {{ $cpcl->catatan_verifikator ?? 'Belum ada catatan untuk data ini.' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="previewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-dark border-0 py-3">
                <h6 class="modal-title text-white fw-bold" id="modalTitle">File Preview</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0 bg-secondary bg-opacity-10 position-relative">
                <div id="viewerWrapper" style="height:72vh; overflow: hidden;" class="d-flex justify-content-center align-items-center">
                    <div id="viewer" class="w-100 h-100 d-flex justify-content-center align-items-center"></div>
                </div>
            </div>
            <div class="modal-footer bg-white shadow-sm justify-content-between">
                <div class="btn-group shadow-sm" id="imgControls">
                    <button type="button" class="btn btn-white text-dark" onclick="zoomOut()" title="Perkecil"><i class="bx bx-minus"></i></button>
                    <button type="button" class="btn btn-white text-dark border-start border-end" onclick="resetTransform()" title="Reset"><i class="bx bx-refresh"></i></button>
                    <button type="button" class="btn btn-white text-dark" onclick="zoomIn()" title="Perbesar"><i class="bx bx-plus"></i></button>
                    <button type="button" class="btn btn-white text-dark border-start" onclick="rotate()" title="Putar 90°"><i class="bx bx-rotate-right"></i></button>
                </div>
                
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-label-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                    <a id="downloadBtn" class="btn btn-primary btn-sm shadow-sm" download>
                        <i class="bx bx-download me-1"></i> Unduh Berkas
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    let scale = 1;
    let angle = 0;
    let currentType = 'img';

    function previewFile(url, title) {
        // Reset parameter transformasi setiap kali membuka berkas baru
        scale = 1; 
        angle = 0;
        
        document.getElementById('modalTitle').innerText = 'Berkas: ' + title;
        document.getElementById('downloadBtn').href = url;

        const ext = url.split('.').pop().toLowerCase();
        const viewer = document.getElementById('viewer');
        const controls = document.getElementById('imgControls');

        if (ext === 'pdf') {
            currentType = 'pdf';
            controls.classList.add('d-none'); // Kontrol zoom/rotate tidak berfungsi pada iframe PDF
            viewer.innerHTML = `<iframe src="${url}#toolbar=0" width="100%" height="100%" style="border:none;"></iframe>`;
        } else {
            currentType = 'img';
            controls.classList.remove('d-none'); // Tampilkan kontrol untuk gambar
            viewer.innerHTML = `<img id="imgViewer" src="${url}" class="img-fluid" style="transition: transform 0.3s ease; transform-origin: center center; cursor: zoom-in;">`;
        }

        const modalEl = document.getElementById('previewModal');
        const myModal = bootstrap.Modal.getOrCreateInstance(modalEl);
        myModal.show();
    }

    // Fungsi Transformasi Gambar
    function applyTransform() {
        if (currentType === 'img') {
            const img = document.getElementById('imgViewer');
            if (img) {
                img.style.transform = `scale(${scale}) rotate(${angle}deg)`;
            }
        }
    }

    function zoomIn() { if(currentType === 'img') { scale += 0.2; applyTransform(); } }
    function zoomOut() { if(currentType === 'img') { scale = Math.max(0.2, scale - 0.2); applyTransform(); } }
    function rotate() { if(currentType === 'img') { angle += 90; applyTransform(); } }
    function resetTransform() { scale = 1; angle = 0; applyTransform(); }

    // Bersihkan viewer saat modal ditutup untuk mencegah pemborosan memori
    document.getElementById('previewModal').addEventListener('hidden.bs.modal', function () {
        document.getElementById('viewer').innerHTML = '';
    });
</script>
@endpush
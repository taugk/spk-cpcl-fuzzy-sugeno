@extends('admin.layouts.app')
@section('title', 'Verifikasi CPCL')

@section('content')
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

<style>
    :root {
        --primary-green: #2e7d32;
        --green-soft: #e8f5e9;
        --green-light: #66bb6a;
        --green-dark: #1b5e20;
        --border-color: #e0e0e0;
    }

    .card-modern {
        border: none;
        border-radius: 16px;
        box-shadow: 0 4px 24px rgba(0,0,0,0.04);
        background: #fff;
        margin-bottom: 1.5rem;
        overflow: hidden;
    }

    .card-header-modern {
        background: #fff;
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid var(--border-color);
        display: flex;
        align-items: center;
    }

    .section-icon {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 12px;
        font-size: 1.2rem;
    }

    .title-text {
        font-size: 14px;
        font-weight: 800;
        color: var(--primary-green);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        padding: 1.5rem;
    }

    .data-item {
        padding: 12px 16px;
        background: #f8f9fa;
        border-radius: 12px;
        border: 1px solid var(--border-color);
    }

    .data-label {
        font-size: 11px;
        color: #8a92a6;
        text-transform: uppercase;
        font-weight: 700;
        margin-bottom: 4px;
        display: block;
    }

    .data-value {
        font-weight: 700;
        font-size: 14px;
        color: #2d3748;
    }

    .tile-container {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 1rem;
        padding: 0 1.5rem 1.5rem;
    }

    .tile-item {
        padding: 16px;
        border-radius: 14px;
        border-left: 4px solid;
        background: #fff;
        box-shadow: 0 2px 8px rgba(0,0,0,0.02);
        transition: transform 0.2s;
        border: 1px solid var(--border-color);
        border-left-width: 4px;
    }

    .tile-item:hover { transform: translateY(-2px); }
    .tile-blue { border-left-color: #4e73df; }
    .tile-green { border-left-color: #1cc88a; }
    .tile-orange { border-left-color: #f6c23e; }
    .tile-cyan { border-left-color: #36b9cc; }

    #map { height: 350px; width: 100%; z-index: 1; border-radius: 0 0 16px 16px; }

    .coordinate-badge {
        background: rgba(255,255,255,0.9);
        padding: 6px 14px;
        border-radius: 30px;
        font-size: 11px;
        border: 1px solid var(--border-color);
        display: inline-flex;
        align-items: center;
        margin: 0.8rem;
        position: absolute;
        bottom: 0;
        left: 0;
        z-index: 1000;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    .kriteria-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1.5rem;
    }
    
    @media (max-width: 768px) {
        .kriteria-grid { grid-template-columns: 1fr; }
        .tile-container { grid-template-columns: repeat(2, 1fr); }
    }
</style>

<div class="content-wrapper">
    <div class="container-xxl container-p-y">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1"><i class="bi bi-shield-check text-primary me-2"></i>Verifikasi CPCL</h4>
                <p class="text-muted small mb-0">Tinjau data kelompok tani dan tentukan kelayakan usulan bantuan.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.cpcl.index') }}" class="btn btn-sm btn-outline-secondary shadow-sm px-3">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
                <span class="badge bg-label-success p-2 px-3 text-uppercase fw-bold shadow-sm">ID: #CPCL-{{ $cpcl->id }}</span>
            </div>
        </div>

        <form id="formVerify" method="POST" action="{{ route('admin.cpcl.verify.process', $cpcl->id) }}">
            @csrf
            
            <div class="row">
                <div class="col-lg-8">
                    {{-- Profil Kelompok --}}
                    <div class="card card-modern">
                        <div class="card-header-modern">
                            <div class="section-icon bg-label-success text-primary shadow-sm">
                                <i class="bi bi-people-fill"></i>
                            </div>
                            <span class="title-text">Profil Kelompok & Usulan</span>
                        </div>
                        <div class="info-grid">
                            <div class="data-item">
                                <span class="data-label">Nama Kelompok Tani</span>
                                <div class="data-value text-dark fw-bold">{{ $cpcl->nama_kelompok }}</div>
                            </div>
                            <div class="data-item">
                                <span class="data-label">Rencana Usaha</span>
                                <div class="data-value">{{ $cpcl->rencana_usaha }}</div>
                            </div>
                            <div class="data-item">
                                <span class="data-label">Nama Ketua</span>
                                <div class="data-value">{{ $cpcl->nama_ketua }}</div>
                            </div>
                            <div class="data-item">
                                <span class="data-label">NIK Ketua</span>
                                <div class="data-value text-muted">{{ $cpcl->nik_ketua }}</div>
                            </div>
                            <div class="data-item">
                                <span class="data-label">Bidang</span>
                                <div class="data-value"><span class="badge bg-label-info">{{ $cpcl->bidang }}</span></div>
                            </div>
                        </div>

                        <div class="card-header-modern border-top-0 pt-0">
                            <div class="section-icon bg-label-success text-success shadow-sm">
                                <i class="bi bi-gear-wide-connected"></i>
                            </div>
                            <span class="title-text">Data Operasional</span>
                        </div>
                        <div class="tile-container">
                            <div class="tile-item tile-blue">
                                <span class="data-label">Luas Lahan</span>
                                <div class="data-value text-dark">{{ number_format($cpcl->luas_lahan, 2) }} Ha</div>
                            </div>
                            <div class="tile-item tile-green">
                                <span class="data-label">Hasil Panen</span>
                                <div class="data-value text-success">{{ number_format($cpcl->hasil_panen, 2) }} T/Ha</div>
                            </div>
                            <div class="tile-item tile-orange">
                                <span class="data-label">Status Lahan</span>
                                <div class="data-value text-warning text-capitalize">{{ $cpcl->status_lahan }}</div>
                            </div>
                            <div class="tile-item tile-cyan">
                                <span class="data-label">Berdiri</span>
                                <div class="data-value text-info">{{ $cpcl->lama_berdiri }} Thn</div>
                            </div>
                        </div>
                        <div class="px-4 pb-4">
                            <div class="data-item bg-light p-3 rounded">
                                <span class="data-label d-block fw-semibold mb-1">Lokasi / Alamat Detail</span>
                                <div class="data-value fw-normal small text-muted lh-base">
                                    <i class="bi bi-geo-alt-fill text-danger me-1"></i>
                                    <span class="text-dark fw-medium">{{ $cpcl->lokasi ?? 'Detail alamat belum diatur' }}</span>
                                    <br>
                                    <span style="margin-left: 1.35rem; display: inline-block; margin-top: 2px;">
                                        Desa {{ $cpcl->alamat->desa ?? '-' }}, 
                                        Kecamatan {{ $cpcl->alamat->kecamatan ?? '-' }}, 
                                        {{ ucwords(strtolower($cpcl->alamat->kabupaten ?? 'Kabupaten Kuningan')) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Penilaian Kriteria (SUPPORT EDIT) --}}
                    <div class="card card-modern border-success border" style="border-width: 2px !important;">
                        <div class="card-header-modern bg-label-success">
                            <div class="section-icon bg-success text-white shadow-sm">
                                <i class="bi bi-bar-chart-line-fill"></i>
                            </div>
                            <span class="title-text text-dark">Verifikasi Penilaian Kriteria</span>
                        </div>

                        <div class="p-4 pt-3">
    @if(isset($kriteria) && $kriteria->count() > 0)
        <div class="alert alert-info border-0 mb-4 d-flex align-items-center shadow-sm">
            <i class="bi bi-info-circle-fill me-2 fs-4"></i>
            <small>Nilai yang tampil adalah nilai tersimpan (jika sudah diverifikasi) atau nilai awal dari UPTD.</small>
        </div>

        <div class="kriteria-grid">
            @foreach($kriteria as $item)
                @php
                    // Cek apakah kriteria ini adalah "Kelengkapan Dokumen"
                    // Sesuaikan kondisi berikut dengan data di database Anda
                    $isDokumen = str_contains(strtolower($item->nama_kriteria), 'dokumen') 
                                 || $item->kode_kriteria === 'DOK';

                    // Mencari nilai yang sudah tersimpan di tabel penilaian
                    $existing = $cpcl->penilaian->where('kriteria_id', $item->id)->first();
                    $savedValue = $existing ? $existing->nilai : null;

                    // Untuk kriteria dokumen, JANGAN ambil data dari UPTD (nilai awal dikosongkan)
                    // Untuk kriteria lain, prioritas: nilai tersimpan > data UPTD > kosong
                    if ($isDokumen) {
                        $currentValue = $savedValue ?? '';  // tidak pakai data UPTD
                    } else {
                        $currentValue = $savedValue ?? ($cpcl->{$item->mapping_field} ?? '');
                    }
                @endphp

                <div class="p-3 border rounded-3 bg-white shadow-none h-100 position-relative">
                    <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                        <div class="fw-bold small text-dark">
                            {{ $item->kode_kriteria }} - {{ $item->nama_kriteria }}
                        </div>
                        <div class="d-flex gap-1">
                            @if($savedValue !== null)
                                <span class="badge bg-label-warning text-xxs" style="font-size: 0.6rem;">TEREDIT</span>
                            @endif
                            <span class="badge {{ $item->jenis_kriteria == 'kontinu' ? 'bg-label-primary' : 'bg-label-success' }} text-xxs px-2" style="font-size: 0.65rem;">
                                {{ strtoupper($item->jenis_kriteria) }}
                            </span>
                        </div>
                    </div>

                    @if($item->jenis_kriteria == 'kontinu')
                        <div>
                            <label class="form-label text-xxs fw-bold text-muted mb-1">
                                Input Verifikasi
                                @if(!$isDokumen)
                                    <span class="text-muted fw-normal">
                                        (Data UPTD: {{ $cpcl->{$item->mapping_field} ?? '0' }})
                                    </span>
                                @endif
                            </label>
                            <div class="input-group input-group-sm">
                                <input type="number" step="0.01" name="nilai[{{ $item->id }}]" 
                                       value="{{ $currentValue }}" 
                                       class="form-control border-primary-subtle" required>
                            </div>
                        </div>
                    @else
                        <div>
                            <label class="form-label text-xxs fw-bold text-muted mb-1">
                                Pilih Kategori
                                @if(!$isDokumen)
                                    <span class="text-muted fw-normal">
                                        (Data UPTD: {{ $cpcl->{$item->mapping_field} ?? '-' }})
                                    </span>
                                @endif
                            </label>
                            <select name="nilai[{{ $item->id }}]" class="form-select form-select-sm border-success-subtle" required>
                                <option value="">-- Verifikasi Kategori --</option>
                                @foreach($item->subKriteria as $sub)
                                    <option value="{{ $sub->nama_sub_kriteria }}" 
                                        {{ strtolower($currentValue) == strtolower($sub->nama_sub_kriteria) ? 'selected' : '' }}>
                                        {{ $sub->nama_sub_kriteria }} 
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @else
        <div class="text-center py-4 bg-label-warning rounded">
            <i class="bi bi-exclamation-circle fs-4"></i>
            <p class="mb-0 mt-2 fw-bold">Kriteria penilaian belum dikonfigurasi.</p>
        </div>
    @endif
</div>
                    </div>
                </div>

                {{-- Sidebar --}}
                <div class="col-lg-4">
                    {{-- Dokumen --}}
                    <div class="card card-modern">
                        <div class="card-header-modern">
                            <div class="section-icon bg-label-warning text-warning shadow-sm">
                                <i class="bi bi-folder-fill"></i>
                            </div>
                            <span class="title-text">Dokumen Pendukung</span>
                        </div>
                        <div class="p-3">
                            <div class="row g-2">
                                @php
                                    $docs = [
                                        ['l' => 'Proposal', 'f' => $cpcl->file_proposal, 'i' => 'bi-file-earmark-pdf', 'c' => 'primary'],
                                        ['l' => 'KTP Ketua', 'f' => $cpcl->file_ktp, 'i' => 'bi-person-badge', 'c' => 'info'],
                                        ['l' => 'SK Kelompok', 'f' => $cpcl->file_sk, 'i' => 'bi-file-earmark-check', 'c' => 'success'],
                                        ['l' => 'Foto Lahan', 'f' => $cpcl->foto_lahan, 'i' => 'bi-camera', 'c' => 'danger']
                                    ];
                                @endphp
                                @foreach($docs as $doc)
                                <div class="col-6">
                                    <div class="text-center p-3 border rounded-3 bg-light">
                                        <i class="bi {{ $doc['i'] }} fs-4 text-{{ $doc['c'] }} mb-1 d-block"></i>
                                        <div class="text-xxs fw-bold text-muted mb-2" style="font-size: 0.7rem;">{{ $doc['l'] }}</div>
                                        @if($doc['f'])
                                            <button type="button" onclick="previewFile('{{ asset('storage/'.$doc['f']) }}')" class="btn btn-xs btn-{{ $doc['c'] }} w-100 py-1" style="font-size: 0.65rem;">Lihat</button>
                                        @else
                                            <span class="badge bg-secondary w-100" style="font-size: 0.65rem;">Kosong</span>
                                        @endif
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- Map --}}
                    <div class="card card-modern">
                        <div class="card-header-modern">
                            <div class="section-icon bg-label-danger text-danger shadow-sm">
                                <i class="bi bi-map-fill"></i>
                            </div>
                            <span class="title-text">Lokasi Lahan</span>
                        </div>
                        <div class="position-relative">
                            <div id="map"></div>
                            <div class="coordinate-badge">
                                <i class="bi bi-crosshair text-danger me-2"></i>
                                <span class="fw-bold">{{ $cpcl->latitude }}, {{ $cpcl->longitude }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Form Keputusan --}}
                    <div class="card card-modern border-top border-success border-5 position-sticky" style="top: 20px;">
                        <div class="card-header-modern border-bottom-0 pb-0">
                            <div class="section-icon bg-label-success text-primary shadow-sm">
                                <i class="bi bi-shield-check"></i>
                            </div>
                            <span class="title-text">Form Keputusan</span>
                        </div>
                        <div class="p-4">
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Status Verifikasi</label>
                                <select name="status" class="form-select border-primary-subtle shadow-sm" required>
                                    <option value="">-- Pilih Keputusan --</option>
                                    <option value="terverifikasi" class="text-success fw-bold" {{ $cpcl->status == 'terverifikasi' ? 'selected' : '' }}>TERVERIFIKASI</option>
                                    <option value="perlu_perbaikan" class="text-warning fw-bold" {{ $cpcl->status == 'perlu_perbaikan' ? 'selected' : '' }}>PERLU PERBAIKAN</option>
                                    <option value="ditolak" class="text-danger fw-bold" {{ $cpcl->status == 'ditolak' ? 'selected' : '' }}>DITOLAK</option>
                                </select>
                            </div>
                            <div class="mb-4">
                                <label class="form-label small fw-bold">Catatan Verifikator</label>
                                <textarea name="catatan_verifikator" class="form-control" rows="3" 
                                          placeholder="Masukkan alasan atau instruksi revisi...">{{ old('catatan_verifikator', $cpcl->catatan_verifikator) }}</textarea>
                            </div>
                            <button type="submit" class="btn btn-success w-100 fw-bold py-2 shadow">
                                <i class="bi bi-save me-2"></i> SIMPAN KEPUTUSAN
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Modal Preview --}}
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0">
            <div class="modal-header bg-light border-bottom">
                <h6 class="modal-title fw-bold">Pratinjau Dokumen</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0 text-center bg-dark" id="previewContent"></div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        @if($cpcl->latitude && $cpcl->longitude)
            var map = L.map('map').setView([{{ $cpcl->latitude }}, {{ $cpcl->longitude }}], 15);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
            L.marker([{{ $cpcl->latitude }}, {{ $cpcl->longitude }}]).addTo(map);
            setTimeout(() => { map.invalidateSize(); }, 500);
        @endif
    });

    function previewFile(url) {
        let content = '';
        if(url.toLowerCase().endsWith('.pdf')){
            content = `<iframe src="${url}" width="100%" height="750px" style="border:none;"></iframe>`;
        } else {
            content = `<div class="p-3 bg-white"><img src="${url}" class="img-fluid rounded shadow-lg" style="max-height: 80vh"></div>`;
        }
        document.getElementById('previewContent').innerHTML = content;
        new bootstrap.Modal(document.getElementById('previewModal')).show();
    }
</script>
@endsection
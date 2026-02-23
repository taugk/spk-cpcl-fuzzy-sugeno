@extends('uptd.layouts.app')

@php
    $isEdit = isset($cpcl);
    $title = $isEdit ? 'Edit Data CPCL' : 'Form Input Data CPCL';
    $route = $isEdit ? route('uptd.cpcl.update', $cpcl->id) : route('uptd.cpcl.store');
@endphp

@section('title', $title)

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="row justify-content-center">
        <div class="col-xl-10">
            <h4 class="fw-bold py-3 mb-4">
                <span class="text-muted fw-light">Pendaftaran /</span> {{ $isEdit ? 'Edit CPCL' : 'Calon Petani Calon Lokasi' }}
            </h4>

            <div class="card">
                <div class="card-header border-bottom mb-4">
                    <div class="d-flex align-items-center">
                        <div class="avatar flex-shrink-0 me-3">
                            <span class="avatar-initial rounded bg-label-primary"><i class="bx {{ $isEdit ? 'bx-edit' : 'bx-file' }}"></i></span>
                        </div>
                        <div>
                            <h5 class="mb-0">{{ $isEdit ? 'Form Perubahan Data' : 'Formulir Input CPCL' }}</h5>
                            <small class="text-muted">{{ $isEdit ? 'Perbarui informasi yang diperlukan di bawah ini.' : 'Lengkapi seluruh informasi di bawah ini dengan data yang valid.' }}</small>
                        </div>
                    </div>
                </div>

                <form method="POST" action="{{ $route }}" enctype="multipart/form-data">
                    @csrf
                    @if($isEdit)
                        @method('PUT')
                    @endif
                    
                    <div class="card-body">
                        {{-- SEKSI 1 --}}
                        <div class="mb-5">
                            <div class="d-flex align-items-center mb-3">
                                <span class="badge badge-center rounded-pill bg-primary me-2">1</span>
                                <h6 class="mb-0 text-primary uppercase">Informasi Kelompok & Usulan</h6>
                            </div>
                            <div class="row g-3 px-lg-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Nama Kelompok Tani</label>
                                    <input type="text" name="nama_kelompok" value="{{ old('nama_kelompok', $cpcl->nama_kelompok ?? '') }}" class="form-control" placeholder="Contoh: Mekar Mulya" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Nama Ketua Kelompok</label>
                                    <input type="text" name="nama_ketua" value="{{ old('nama_ketua', $cpcl->nama_ketua ?? '') }}" class="form-control" placeholder="Nama lengkap ketua" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">NIK Ketua</label>
                                    <input type="text" name="nik_ketua" value="{{ old('nik_ketua', $cpcl->nik_ketua ?? '') }}" maxlength="16" class="form-control" placeholder="16 digit NIK" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Kategori Komoditas</label>
                                    <select name="bidang" class="form-select" required>
                                        <option value="" disabled {{ !$isEdit ? 'selected' : '' }}>Pilih Kategori</option>
                                        <option value="HARTIBUN" {{ old('bidang', $cpcl->bidang ?? '') == 'HARTIBUN' ? 'selected' : '' }}>Hortikultura & Perkebunan</option>
                                        <option value="PANGAN" {{ old('bidang', $cpcl->bidang ?? '') == 'PANGAN' ? 'selected' : '' }}>Tanaman Pangan</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Usulan Bantuan</label>
                                    <select name="rencana_usaha" class="form-select" required>
                                        <option value="" disabled {{ !$isEdit ? 'selected' : '' }}>Pilih Usulan</option>
                                        @php
                                            $usulans = ['Pengembangan Benih', 'Penyediaan Pupuk', 'Pengadaan Alsintan', 'Rehabilitasi Jaringan Irigasi', 'Peningkatan Produksi'];
                                        @endphp
                                        @foreach($usulans as $usulan)
                                            <option value="{{ $usulan }}" {{ old('rencana_usaha', $cpcl->rencana_usaha ?? '') == $usulan ? 'selected' : '' }}>{{ $usulan }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label fw-semibold">Lokasi / Alamat Lahan</label>
                                    <textarea name="lokasi" class="form-control" rows="2" placeholder="Masukkan alamat lengkap" required>{{ old('lokasi', $cpcl->lokasi ?? '') }}</textarea>
                                </div>
                            </div>
                        </div>

                        {{-- SEKSI 2 --}}
                        <div class="mb-5">
                            <div class="d-flex align-items-center mb-3">
                                <span class="badge badge-center rounded-pill bg-primary me-2">2</span>
                                <h6 class="mb-0 text-primary uppercase">Data Lahan & Titik Koordinat</h6>
                            </div>
                            <div class="row g-3 px-lg-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Luas Lahan</label>
                                    <div class="input-group">
                                        <input type="number" step="0.01" name="luas_lahan" value="{{ old('luas_lahan', $cpcl->luas_lahan ?? '') }}" class="form-control" placeholder="0.00" required>
                                        <span class="input-group-text">Hektar</span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Umur Kelompok</label>
                                    <div class="input-group">
                                        <input type="number" name="lama_berdiri" value="{{ old('lama_berdiri', $cpcl->lama_berdiri ?? '') }}" class="form-control" placeholder="0" required>
                                        <span class="input-group-text">Tahun</span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Estimasi Panen</label>
                                    <div class="input-group">
                                        <input type="number" step="0.1" name="hasil_panen" value="{{ old('hasil_panen', $cpcl->hasil_panen ?? '') }}" class="form-control" placeholder="0.0" required>
                                        <span class="input-group-text">Ton/Ha</span>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label fw-semibold mb-2">Status Kepemilikan Lahan</label>
                                    <div class="d-flex flex-wrap gap-3 p-3 border rounded">
                                        @foreach(['milik' => 'Milik Sendiri', 'sewa' => 'Sewa Lahan', 'garapan' => 'Lahan Garapan'] as $val => $label)
                                        <div class="form-check custom-option custom-option-basic">
                                            <input class="form-check-input" type="radio" name="status_lahan" id="lahan_{{ $val }}" value="{{ $val }}" 
                                                {{ old('status_lahan', $cpcl->status_lahan ?? '') == $val ? 'checked' : '' }} 
                                                {{ !$isEdit && $val == 'milik' ? 'checked' : '' }} required>
                                            <label class="form-check-label" for="lahan_{{ $val }}">{{ $label }}</label>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold"><i class="bx bx-map-pin me-1"></i>Latitude</label>
                                    <input type="text" name="latitude" value="{{ old('latitude', $cpcl->latitude ?? '') }}" class="form-control bg-light" placeholder="-6.975xxx">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold"><i class="bx bx-map-pin me-1"></i>Longitude</label>
                                    <input type="text" name="longitude" value="{{ old('longitude', $cpcl->longitude ?? '') }}" class="form-control bg-light" placeholder="108.483xxx">
                                </div>
                            </div>
                        </div>

                        {{-- SEKSI 3 --}}
                        <div class="mb-4">
                            <div class="d-flex align-items-center mb-3">
                                <span class="badge badge-center rounded-pill bg-primary me-2">3</span>
                                <h6 class="mb-0 text-primary uppercase">Lampiran Dokumen</h6>
                            </div>
                            <div class="row g-4 px-lg-3">
                                <div class="col-md-6 border-end">
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Proposal Kelompok <small class="text-danger">(.pdf)</small></label>
                                        <input type="file" name="file_proposal" class="form-control mb-2" accept=".pdf" {{ $isEdit ? '' : 'required' }}>
                                        @if($isEdit && $cpcl->file_proposal)
                                            <small class="text-muted"><i class="bx bx-link"></i> <a href="{{ asset('storage/'.$cpcl->file_proposal) }}" target="_blank">Lihat Proposal Saat Ini</a></small>
                                        @endif
                                        
                                        <label class="form-label fw-semibold mt-3">SK Kelompok <small class="text-danger">(.pdf)</small></label>
                                        <input type="file" name="file_sk" class="form-control" accept=".pdf">
                                        @if($isEdit && $cpcl->file_sk)
                                            <small class="text-muted"><i class="bx bx-link"></i> <a href="{{ asset('storage/'.$cpcl->file_sk) }}" target="_blank">Lihat SK Saat Ini</a></small>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">KTP Ketua <small class="text-danger">(.pdf, .jpg)</small></label>
                                        <input type="file" name="file_ktp" class="form-control mb-2" accept=".pdf,image/*" {{ $isEdit ? '' : 'required' }}>
                                        @if($isEdit && $cpcl->file_ktp)
                                            <small class="text-muted"><i class="bx bx-link"></i> <a href="{{ asset('storage/'.$cpcl->file_ktp) }}" target="_blank">Lihat KTP Saat Ini</a></small>
                                        @endif

                                        <label class="form-label fw-semibold mt-3">Foto Lahan <small class="text-danger">(.jpg, .png)</small></label>
                                        <input type="file" name="foto_lahan" class="form-control" accept="image/*">
                                        @if($isEdit && $cpcl->foto_lahan)
                                            <small class="text-muted"><i class="bx bx-link"></i> <a href="{{ asset('storage/'.$cpcl->foto_lahan) }}" target="_blank">Lihat Foto Lahan Saat Ini</a></small>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer bg-light d-flex justify-content-between align-items-center">
                        <a href="{{ route('uptd.cpcl.index') }}" class="btn btn-label-secondary">Batal</a>
                        <div class="gap-2">
                            <button type="submit" class="btn btn-primary btn-lg px-4 shadow">
                                <i class="bx bx-save me-1"></i> {{ $isEdit ? 'Update Data' : 'Simpan Data' }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
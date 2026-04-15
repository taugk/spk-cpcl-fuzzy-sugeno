@extends('admin.layouts.app')

@section('title', 'Data Kriteria')

@section('content')
<div class="content-wrapper">
    <div class="container-xxl container-p-y">
        <div class="card">
            <div class="card-header border-bottom d-flex flex-column flex-md-row align-items-center justify-content-between">
                <h5 class="card-title mb-3 mb-md-0">Data Kriteria</h5>
                
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalTambahKriteria">
                        <i class="bx bx-plus me-1"></i> <span class="d-none d-sm-inline-block">Tambah Kriteria</span>
                    </button>
                </div>
            </div>

            <div class="table-responsive text-nowrap">
                <table class="table table-hover table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th width="5%" class="text-center">No</th>
                            <th width="10%">Kode</th>
                            <th>Nama Kriteria</th>
                            <th width="10%" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($kriteria as $index => $item)
                        <tr>
                            <td class="text-center">{{ $index + 1 }}</td>
                            <td><span class="badge bg-label-success">C{{ $index + 1 }}</span></td>
                            <td><strong>{{ $item->nama_kriteria }}</strong></td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-2">
                                    {{-- Tombol Edit --}}
                                    <button type="button" class="btn btn-icon btn-outline-success btn-sm btn-edit" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#modalEditKriteria"
                                            data-id="{{ $item->id }}"
                                            data-nama="{{ $item->nama_kriteria }}"
                                            data-jenis="{{ $item->jenis_kriteria }}" {{-- Tambahkan ini --}}
                                            data-mapping="{{ $item->mapping_field }}">
                                        <i class="bx bx-edit-alt"></i>
                                    </button>
                                    
                                    <form action="{{ route('admin.kriteria.destroy', $item->id) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-icon btn-outline-danger btn-sm" onclick="return confirm('Yakin ingin menghapus?')">
                                            <i class="bx bx-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center">Data kriteria masih kosong.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- MODAL TARUH DI SINI (Masih di dalam section content) --}}
    
    <div class="modal fade" id="modalTambahKriteria" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Kriteria Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('admin.kriteria.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nama Kriteria</label>
                            <input type="text" name="nama_kriteria" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Jenis Kriteria</label>
                            <select name="jenis_kriteria" class="form-select" required>
                                <option value="">Pilih Jenis</option>
                                <option value="kontinu">Kontinu</option>
                                <option value="diskrit">Diskrit</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalEditKriteria" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Kriteria</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="formEditKriteria" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nama Kriteria</label>
                            <input type="text" name="nama_kriteria" id="edit_nama_kriteria" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Jenis Kriteria</label>
                            <select name="jenis_kriteria" id="edit_jenis_kriteria" class="form-select" required>
                                <option value="kontinu">Kontinu</option>
                                <option value="diskrit">Diskrit</option>
                            </select>
                        </div>
                        <input type="hidden" name="mapping_field" id="edit_mapping_field">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const editButtons = document.querySelectorAll('.btn-edit');
    
    editButtons.forEach(button => {
        button.addEventListener('click', function () {
            const id = this.getAttribute('data-id');
            const nama = this.getAttribute('data-nama');
            const jenis = this.getAttribute('data-jenis');
            const mapping = this.getAttribute('data-mapping');
            
            // Isi Form
            document.getElementById('edit_nama_kriteria').value = nama;
            document.getElementById('edit_jenis_kriteria').value = jenis;
            document.getElementById('edit_mapping_field').value = mapping;
            
            // Update Action URL
            const formEdit = document.getElementById('formEditKriteria');
            formEdit.action = `/admin/kriteria/${id}`;
        });
    });
});
</script>
@endpush
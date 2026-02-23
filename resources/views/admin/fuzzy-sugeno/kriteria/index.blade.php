@extends('admin.layouts.app')

@section('title', 'Data Kriteria')

@section('content')
<div class="content-wrapper">
    <div class="container-xxl container-p-y">

        <div class="card">
            <div class="card-header border-bottom d-flex flex-column flex-md-row align-items-center justify-content-between">
                <h5 class="card-title mb-3 mb-md-0">Data Kriteria</h5>
                
                <div class="d-flex gap-2">
                    <div class="btn-group">
                        <button type="button" class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bx bx-export me-1"></i> Export
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="javascript:void(0);"><i class="bx bx-printer me-1"></i> Print</a></li>
                            <li><a class="dropdown-item" href="javascript:void(0);"><i class="bx bx-file me-1"></i> Excel</a></li>
                        </ul>
                    </div>

                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambahKriteria">
                        <i class="bx bx-plus me-1"></i> <span class="d-none d-sm-inline-block">Tambah Kriteria</span>
                    </button>
                </div>
            </div>

            <div class="card-body mt-3">
                <div class="row justify-content-end align-items-center">
                    <div class="col-md-4 col-12">
                        <div class="d-flex align-items-center justify-content-center justify-content-md-end">
                            <span class="text-muted me-2">Search:</span>
                            <input type="search" class="form-control form-control-sm w-auto" placeholder="Cari Kriteria...">
                        </div>
                    </div>
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
                    <tbody class="table-border-bottom-0">
    @forelse ($kriteria as $index => $item)
    <tr>
        <td class="text-center">{{ $index + 1 }}</td>
        <td><span class="badge bg-label-primary">C{{ $index + 1 }}</span></td>
        <td><strong>{{ $item->nama_kriteria }}</strong></td>
        <td class="text-center">
            <div class="d-flex justify-content-center gap-2">
                {{-- Tombol Edit Langsung --}}
                <button type="button" class="btn btn-icon btn-outline-primary btn-sm btn-edit" 
                        data-bs-toggle="modal" 
                        data-bs-target="#modalEditKriteria"
                        data-id="{{ $item->id }}"
                        data-nama="{{ $item->nama_kriteria }}"
                        data-mapping="{{ $item->mapping_field }}"
                        title="Edit">
                    <i class="bx bx-edit-alt"></i>
                </button>
                
                {{-- Tombol Delete Langsung --}}
                <form action="{{ route('admin.kriteria.destroy', $item->id) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="button" class="btn btn-icon btn-outline-danger btn-sm btn-delete-confirm" title="Hapus">
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

            <div class="card-footer d-flex flex-column flex-md-row justify-content-between align-items-center">
                <small class="text-muted mb-2 mb-md-0">Showing {{ count($kriteria) }} entries</small>
                <nav aria-label="Page navigation">
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item active"><a class="page-link" href="#">1</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>
</div>

@include('admin.partials.modals.kriteria_modal')
@endsection

@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const editButtons = document.querySelectorAll('.btn-edit');
    
    editButtons.forEach(button => {
      button.addEventListener('click', function () {
        const id = this.getAttribute('data-id');
        const nama = this.getAttribute('data-nama');
        const mapping = this.getAttribute('data-mapping');
        
        document.getElementById('edit_nama_kriteria').value = nama;
        document.getElementById('edit_mapping_field').value = mapping;
        
        const formEdit = document.getElementById('formEditKriteria');
        formEdit.action = '{{ url("admin/kriteria") }}/' + id;
      });
    });
  });
</script>
@endpush
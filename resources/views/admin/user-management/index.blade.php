@extends('admin.layouts.app')

@section('title', 'Manajemen User')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    
    @if(session('success'))
        <div class="alert alert-success alert-dismissible" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card">
      
      <div class="card-header border-bottom d-flex flex-column flex-md-row align-items-center justify-content-between">
        <h5 class="card-title mb-3 mb-md-0">Data Pengguna</h5>
        
        <div class="d-flex gap-2">
          
          <a href="{{ route('admin.user-management.create') }}" class="btn btn-primary">
            <i class="bx bx-plus me-1"></i> 
            <span class="d-none d-sm-inline-block">Tambah User</span>
          </a>

        </div>
      </div>

      <div class="table-responsive text-nowrap">
        <table class="table table-hover">
          <thead class="table-light">
            <tr>
              <th width="5%">No</th>
              <th>User</th>
              <th>Username</th>
              <th>Role</th>
              <th>Status</th>
              <th>Dibuat Oleh</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody class="table-border-bottom-0">
            @forelse($users as $index => $user)
            <tr>
              <td>{{ $users->firstItem() + $index }}</td>
              
              <td>
                <div class="d-flex justify-content-start align-items-center">
                  <div class="avatar-wrapper">
                    <div class="avatar avatar-sm me-3">
                      <span class="avatar-initial rounded-circle bg-label-primary">
                        {{ strtoupper(substr($user->name, 0, 2)) }}
                      </span>
                    </div>
                  </div>
                  <div class="d-flex flex-column">
                    <span class="fw-semibold text-heading">{{ $user->name }}</span>
                    <small class="text-muted">{{ $user->email }}</small>
                  </div>
                </div>
              </td>

              <td>{{ $user->username }}</td>

              <td>
                @if($user->role == 'admin')
                    <span class="badge bg-label-primary">ADMIN</span>
                @else
                    <span class="badge bg-label-info">UPTD</span>
                @endif
              </td>

              <td>
                @if($user->status == 'aktif')
                    <span class="badge bg-label-success">Aktif</span>
                @else
                    <span class="badge bg-label-secondary">Nonaktif</span>
                @endif
              </td>

              <td>
                  <small class="text-muted">
                      {{ $user->creator->name ?? '-' }}
                  </small>
              </td>

              <td>
                <div class="d-flex align-items-center">
                    <a href="{{ route('admin.user-management.edit', $user->id) }}" class="text-primary me-3" title="Edit">
                        <i class="bx bx-edit-alt fs-5"></i>
                    </a>

                    <form action="{{ route('admin.user-management.destroy', $user->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus user ini?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-icon btn-text-danger p-0" title="Hapus" style="background:none; border:none;">
                            <i class="bx bx-trash fs-5"></i>
                        </button>
                    </form>
                </div>
              </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center py-5">
                    <i class="bx bx-folder-open fs-1 text-muted mb-2"></i>
                    <p class="text-muted">Belum ada data pengguna.</p>
                </td>
            </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="card-footer d-flex justify-content-end">
         {{ $users->links() }} 
         {{-- Jika tampilan pagination berantakan, gunakan: {{ $users->links('pagination::bootstrap-5') }} --}}
      </div>

    </div>
  </div>
@endsection
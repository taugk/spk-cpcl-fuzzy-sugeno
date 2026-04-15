@extends('admin.layouts.app')

@section('title', 'Detail Profil User')

@section('content')
<div class="container-xxl container-p-y">

<div class="row g-4">

    {{-- ================= PROFILE CARD ================= --}}
    <div class="col-lg-4">

        <div class="card border-0 shadow-sm">

            <div class="card-body text-center">

                {{-- Avatar --}}
                <div class="mb-3">
                    <div class="avatar-lg bg-success text-white rounded-circle d-flex align-items-center justify-content-center mx-auto">
                        <h2 class="mb-0">{{ strtoupper(substr($user->name,0,1)) }}</h2>
                    </div>
                </div>

                <h5 class="fw-bold mb-1">{{ $user->name }}</h5>
                <small class="text-muted">{{ $user->username }}</small>

                <div class="mt-3">
                    <span class="badge bg-{{ $user->status == 'aktif' ? 'success' : 'danger' }}">
                        {{ strtoupper($user->status) }}
                    </span>
                </div>

                <hr>

                {{-- Info --}}
                <div class="text-start small">

                    <p class="mb-2">
                        <strong>Email:</strong><br>
                        {{ $user->email ?? '-' }}
                    </p>

                    <p class="mb-2">
                        <strong>Role:</strong><br>
                        <span class="badge bg-info text-dark">
                            {{ strtoupper(str_replace('_',' ', $user->role)) }}
                        </span>
                    </p>

                    <p class="mb-2">
                        <strong>Last Login:</strong><br>
                        {{ $user->last_login_at 
                            ? \Carbon\Carbon::parse($user->last_login_at)->format('d M Y H:i') 
                            : '-' }}
                    </p>

                    <p class="mb-2">
                        <strong>Dibuat pada:</strong><br>
                        {{ $user->created_at->format('d M Y H:i') }}
                    </p>

                    <p class="mb-0">
                        <strong>Dibuat oleh:</strong><br>
                        {{ $user->creator->name ?? '-' }}
                    </p>

                </div>

                {{-- Action --}}
                <div class="mt-4 d-grid gap-2">
                    <a href="{{ route('admin.user-management.edit-profile', $user->id) }}" 
                       class="btn btn-success">
                        Edit Profil
                    </a>

                    <a href="{{ route('admin.user-management.index') }}" 
                       class="btn btn-outline-secondary">
                        Kembali
                    </a>
                </div>

            </div>

        </div>

    </div>


    {{-- ================= DETAIL INFO ================= --}}
    <div class="col-lg-8">

        <div class="card border-0 shadow-sm">

            <div class="card-header bg-white">
                <h5 class="fw-bold mb-0">Informasi Detail</h5>
            </div>

            <div class="card-body">

                <div class="row g-3">

                    <div class="col-md-6">
                        <label class="form-label text-muted">Nama Lengkap</label>
                        <div class="fw-semibold">{{ $user->name }}</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label text-muted">Username</label>
                        <div class="fw-semibold">{{ $user->username }}</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label text-muted">Email</label>
                        <div class="fw-semibold">{{ $user->email ?? '-' }}</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label text-muted">Role</label>
                        <div>
                            <span class="badge bg-info text-dark">
                                {{ strtoupper(str_replace('_',' ', $user->role)) }}
                            </span>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label text-muted">Status</label>
                        <div>
                            <span class="badge bg-{{ $user->status == 'aktif' ? 'success' : 'danger' }}">
                                {{ strtoupper($user->status) }}
                            </span>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label text-muted">Email Verified</label>
                        <div>
                            @if($user->email_verified_at)
                                <span class="badge bg-success">Verified</span>
                            @else
                                <span class="badge bg-warning text-dark">Belum</span>
                            @endif
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label text-muted">Last Login</label>
                        <div>
                            {{ $user->last_login_at 
                                ? \Carbon\Carbon::parse($user->last_login_at)->diffForHumans() 
                                : '-' }}
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label text-muted">Dibuat pada</label>
                        <div>{{ $user->created_at->format('d M Y H:i') }}</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label text-muted">Terakhir Update</label>
                        <div>{{ $user->updated_at->format('d M Y H:i') }}</div>
                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

</div>
@endsection
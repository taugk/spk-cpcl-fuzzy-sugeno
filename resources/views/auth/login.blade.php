@extends('main') {{-- Pastikan ini mengarah ke layout utama yang memuat CSS/JS --}}

@section('title', 'Login - SPK CPCL Dinas Pertanian')

@section('content')

{{-- CSS KHUSUS LOGIN --}}
<style>
    /* 1. Reset background agar bersih (Formal) */
    body {
        background-color: #f5f5f9 !important;
        background-image: none !important;
    }

    /* 2. Sembunyikan dekorasi shape abstrak bawaan template Sneat */
    .authentication-wrapper::before,
    .authentication-wrapper::after,
    .authentication-inner::before,
    .authentication-inner::after {
        display: none !important;
    }

    /* 3. Penyesuaian Logo & Teks */
    .app-brand {
        margin-bottom: 1.5rem !important;
    }
    .dinas-title {
        font-size: 0.9rem;
        font-weight: 600;
        color: #566a7f;
        text-transform: uppercase;
        line-height: 1.4;
    }
    .system-title {
        font-size: 1.3rem;
        font-weight: 800;
        color: #696cff; /* Warna Primary Sneat */
        margin-bottom: 5px;
    }
</style>

<div class="container-xxl">
    <div class="authentication-wrapper authentication-basic container-p-y">
        <div class="authentication-inner">
            
            <div class="card">
                <div class="card-body">
                    
                    <div class="app-brand justify-content-center flex-column mb-4">
                        <div class="mb-3">
                            <img src="{{ asset('assets/img/icons/brands/logo-dinas-pertanian.png') }}" alt="Logo Kab Kuningan" width="60">
                        </div>
                        
                        <h4 class="system-title text-center">SPK CPCL</h4>
                        
                        <div class="text-center dinas-title">
                            Dinas Ketahanan Pangan<br>dan Pertanian
                            <br><small class="text-muted fw-normal">Kabupaten Kuningan</small>
                        </div>
                    </div>
                    <form id="formAuthentication" class="mb-3" action="{{ route('login.proses') }}" method="POST">
                        @csrf
                        
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input
                                type="text"
                                class="form-control @error('username') is-invalid @enderror"
                                id="username"
                                name="username"
                                value="{{ old('username') }}"
                                placeholder="Masukkan Username / NIP"
                                autofocus
                            />
                            @error('username')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="mb-3 form-password-toggle">
                            <div class="d-flex justify-content-between">
                                <label class="form-label" for="password">Password</label>
                            </div>
                            <div class="input-group input-group-merge">
                                <input
                                    type="password"
                                    id="password"
                                    class="form-control"
                                    name="password"
                                    placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                                    aria-describedby="password"
                                />
                                <span class="input-group-text cursor-pointer"><i class="bx bx-hide"></i></span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="remember-me" name="remember" />
                                <label class="form-check-label" for="remember-me"> Ingat Saya </label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <button class="btn btn-primary d-grid w-100" type="submit">
                                <i class="bx bx-log-in-circle me-2"></i> MASUK APLIKASI
                            </button>
                        </div>
                    </form>

                    <div class="text-center mt-4">
                        <small class="text-muted">
                            &copy; {{ date('Y') }} Pemerintah Kab. Kuningan
                        </small>
                    </div>

                </div>
            </div>
            </div>
    </div>
</div>
@endsection
<nav
    class="layout-navbar container-xxl navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme"
    id="layout-navbar"
>
    <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
        <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)">
            <i class="bx bx-menu bx-sm"></i>
        </a>
    </div>

    <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">
        
        <div class="navbar-nav align-items-center">
            <div class="nav-item d-flex align-items-center">
                
                <div class="d-none d-md-block">
                    <h6 class="mb-0 fw-bold text-success text-uppercase" style="letter-spacing: 1px; font-size: 0.85rem;">
                        Dinas Ketahanan Pangan & Pertanian
                    </h6>
                    <small class="text-muted" style="font-size: 0.7rem;">Sistem Informasi CPCL Pertanian</small>
                </div>
            </div>
        </div>
        <ul class="navbar-nav flex-row align-items-center ms-auto">
            <li class="nav-item navbar-dropdown dropdown-user dropdown">
                <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
                    <div class="avatar avatar-online">
                        <span class="avatar-initial rounded-circle shadow-sm" style="background-color: #2e7d32; color: white; border: 2px solid #fff;">
                            {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 1)) }}
                        </span>
                    </div>
                </a>
                
                <ul class="dropdown-menu dropdown-menu-end shadow">
                    <li>
                        <a class="dropdown-item" href="javascript:void(0);">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0 me-3">
                                    <div class="avatar avatar-online">
                                        <span class="avatar-initial rounded-circle bg-label-success">
                                            {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 1)) }}
                                        </span>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <span class="fw-semibold d-block">
                                        {{ Auth::user()->name ?? 'User' }}
                                    </span>
                                    <small class="text-muted text-uppercase" style="font-size: 0.7rem;">
                                        {{ str_replace('_', ' ', Auth::user()->role ?? 'Guest') }}
                                    </small>
                                </div>
                            </div>
                        </a>
                    </li>
                    
                    <li><div class="dropdown-divider"></div></li>

                    <li>
                        <a class="dropdown-item" href="{{ route('admin.user-management.profile', Auth::user()->id) }}">
                            <i class="bx bx-user me-2 text-success"></i>
                            <span class="align-middle">Profil Saya</span>
                        </a>
                    </li>
                    
                    <li><div class="dropdown-divider"></div></li>

                    <li>
                        <form action="{{ route('logout') }}" method="POST" id="form-logout-navbar">
                            @csrf
                            <a class="dropdown-item" href="javascript:void(0);" onclick="document.getElementById('form-logout-navbar').submit();">
                                <i class="bx bx-power-off me-2 text-danger"></i>
                                <span class="align-middle">Keluar</span>
                            </a>
                        </form>
                    </li>
                </ul>
            </li>
        </ul>
    </div>
</nav>


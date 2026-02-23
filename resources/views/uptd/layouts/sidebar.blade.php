<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">

  <div class="app-brand demo" style="height: 80px;">
    <a href="{{ Route::has('uptd.dashboard') ? route('uptd.dashboard') : '#' }}" class="app-brand-link gap-2">
      
      <span class="app-brand-logo demo">
         <img src="{{ asset('assets/img/icons/brands/logo-dinas-pertanian.png') }}" alt="Logo Dinas Pertanian" width="42" height="auto">
      </span>

      <div class="d-flex flex-column justify-content-center">
          <span class="app-brand-text demo menu-text fw-bolder text-uppercase" style="font-size: 1.2rem; line-height: 1.2;">
            SPK CPCL
          </span>
          <span class="text-muted fw-bold text-uppercase" style="font-size: 0.65rem; letter-spacing: 0.5px;">
            Dinas Pertanian<br>Kab. Kuningan
          </span>
      </div>

    </a>

    <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-block d-xl-none">
      <i class="bx bx-chevron-left bx-sm align-middle"></i>
    </a>
  </div>

  <div class="menu-inner-shadow"></div>

  <ul class="menu-inner py-1">

    <li class="menu-item {{ request()->routeIs('uptd.dashboard') ? 'active' : '' }}">
      <a href="{{ Route::has('uptd.dashboard') ? route('uptd.dashboard') : '#' }}" class="menu-link">
        <i class="menu-icon tf-icons bx bx-home-circle"></i>
        <div>Dashboard</div>
      </a>
    </li>

    <li class="menu-header small text-uppercase">
      <span class="menu-header-text">Menu Utama</span>
    </li>

    <li class="menu-item {{ request()->routeIs('uptd.cpcl*') ? 'active' : '' }}">
      <a href="{{ Route::has('uptd.cpcl.index') ? route('uptd.cpcl.index') : '#' }}" class="menu-link">
        <i class="menu-icon tf-icons bx bx-data"></i>
        <div>Data CPCL</div>
      </a>
    </li>

    <li class="menu-item {{ request()->routeIs('uptd.laporan*') ? 'active' : '' }}">
      <a href="{{ Route::has('uptd.laporan.index') ? route('uptd.laporan.index') : '#' }}" class="menu-link">
        <i class="menu-icon tf-icons bx bxs-file-pdf"></i>
        <div>Laporan Akhir</div>
      </a>
    </li>

    <li class="menu-item mt-3">
        <form method="POST" action="{{ route('logout') }}" id="logout-form">
            @csrf
            <a href="javascript:void(0);" class="menu-link text-danger" onclick="document.getElementById('logout-form').submit();">
                <i class="menu-icon tf-icons bx bx-power-off"></i>
                <div>Logout</div>
            </a>
        </form>
    </li>

  </ul>
</aside>
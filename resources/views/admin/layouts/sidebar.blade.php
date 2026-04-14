<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
  
  {{-- BRAND / LOGO --}}
  <div class="app-brand demo" style="height: 80px;">
    @php
      // Logika untuk menentukan dashboard induk agar tidak error Route Not Defined
      $isAdminGroup = in_array(auth()->user()->role, ['admin', 'admin_pangan', 'admin_hartibun']);
      $dashRoute = $isAdminGroup ? 'admin.dashboard' : 'uptd.dashboard';
    @endphp
    
    <a href="{{ route($dashRoute) }}" class="app-brand-link gap-2">
      <span class="app-brand-logo demo">
        <img src="{{ asset('assets/img/icons/brands/logo-dinas-pertanian.png') }}" alt="Logo" width="42">
      </span>
      <div class="d-flex flex-column justify-content-center">
        <span class="app-brand-text demo menu-text fw-bolder text-uppercase" style="font-size: 1.2rem; line-height: 1.2;">SPK CPCL</span>
        <span class="text-muted fw-bold text-uppercase" style="font-size: 0.65rem;">Dinas Pertanian<br>Kab. Kuningan</span>
      </div>
    </a>
  </div>

  <div class="menu-inner-shadow"></div>

  <ul class="menu-inner py-1">

    {{-- DASHBOARD --}}
    <li class="menu-item {{ request()->routeIs('*.dashboard') ? 'active' : '' }}">
      <a href="{{ route($dashRoute) }}" class="menu-link">
        <i class="menu-icon tf-icons bx bx-home-circle"></i>
        <div>Dashboard</div>
      </a>
    </li>

    {{-- MENU ADMIN ONLY (Super Admin, Pangan, Hartibun) --}}
    @if($isAdminGroup)
      <li class="menu-header small text-uppercase">
        <span class="menu-header-text">Master Data</span>
      </li>
      <li class="menu-item {{ request()->routeIs('admin.cpcl.*') ? 'open active' : '' }}">
        <a href="javascript:void(0);" class="menu-link menu-toggle">
          <i class="menu-icon tf-icons bx bx-data"></i>
          <div>Data CPCL</div>
        </a>
        <ul class="menu-sub">
          <li class="menu-item {{ request()->routeIs('admin.cpcl.index') ? 'active' : '' }}"><a href="{{ route('admin.cpcl.index') }}" class="menu-link"><div>Semua Data</div></a></li>
          <li class="menu-item {{ request()->routeIs('admin.cpcl.belum-verifikasi') ? 'active' : '' }}"><a href="{{ route('admin.cpcl.belum-verifikasi') }}" class="menu-link"><div>Belum Verifikasi</div></a></li>
          <li class="menu-item {{ request()->routeIs('admin.cpcl.verifikasi') ? 'active' : '' }}"><a href="{{ route('admin.cpcl.verifikasi') }}" class="menu-link"><div>Terverifikasi</div></a></li>
          <li class="menu-item {{ request()->routeIs('admin.cpcl.perbaikan') ? 'active' : '' }}"><a href="{{ route('admin.cpcl.perbaikan') }}" class="menu-link"><div>Butuh Perbaikan</div></a></li>
          <li class="menu-item {{ request()->routeIs('admin.cpcl.ditolak') ? 'active' : '' }}"><a href="{{ route('admin.cpcl.ditolak') }}" class="menu-link"><div>Ditolak</div></a></li>
        </ul>
      </li>

      {{-- Hanya Super Admin yang bisa mengelola User --}}
      @if(auth()->user()->role == 'admin')
      <li class="menu-item {{ request()->routeIs('admin.user-management*') ? 'active' : '' }}">
        <a href="{{ route('admin.user-management.index') }}" class="menu-link">
          <i class="menu-icon tf-icons bx bx-group"></i>
          <div>Data Pengguna</div>
        </a>
      </li>
      @endif

      <li class="menu-header small text-uppercase">
        <span class="menu-header-text">Logika Fuzzy</span>
      </li>
      <li class="menu-item {{ request()->routeIs('admin.kriteria*') ? 'active' : '' }}">
        <a href="{{ route('admin.kriteria.index') }}" class="menu-link">
          <i class="menu-icon tf-icons bx bx-list-ul"></i>
          <div>Data Kriteria</div>
        </a>
      </li>
      <li class="menu-item {{ request()->routeIs('admin.sub-kriteria*') ? 'active' : '' }}">
        <a href="{{ route('admin.sub-kriteria.index') }}" class="menu-link">
          <i class="menu-icon tf-icons bx bx-slider-alt"></i>
          <div>Data Sub-Kriteria</div>
        </a>
      </li>
      <li class="menu-item {{ request()->routeIs('admin.perhitungan*') ? 'active' : '' }}">
        <a href="{{ route('admin.perhitungan.index') }}" class="menu-link">
          <i class="menu-icon tf-icons bx bx-calculator"></i>
          <div>Perhitungan & Ranking</div>
        </a>
      </li>
    @endif

    {{-- MENU UPTD ONLY --}}
    @if(auth()->user()->role == 'uptd')
      <li class="menu-header small text-uppercase">
        <span class="menu-header-text">Operasional</span>
      </li>
      <li class="menu-item {{ request()->routeIs('uptd.cpcl.*') ? 'active' : '' }}">
        <a href="{{ route('uptd.cpcl.index') }}" class="menu-link">
          <i class="menu-icon tf-icons bx bx-edit"></i>
          <div>Data CPCL Saya</div>
        </a>
      </li>
    @endif

    {{-- MENU LAPORAN (SHARED) --}}
    <li class="menu-header small text-uppercase">
      <span class="menu-header-text">Laporan</span>
    </li>
    <li class="menu-item {{ request()->routeIs('*.laporan*') ? 'active' : '' }}">
      @php
        $laporanRoute = $isAdminGroup ? 'admin.laporan.index' : 'uptd.laporan.index';
      @endphp
      <a href="{{ route($laporanRoute) }}" class="menu-link">
        <i class="menu-icon tf-icons bx bxs-file-pdf"></i>
        <div>Laporan Akhir</div>
      </a>
    </li>

    {{-- LOGOUT --}}
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
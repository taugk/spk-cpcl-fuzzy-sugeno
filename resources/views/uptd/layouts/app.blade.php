<!DOCTYPE html>
<html
  lang="en"
  class="light-style layout-menu-fixed"
  dir="ltr"
  data-theme="theme-default"
  data-assets-path="{{ asset('assets') }}/"
  data-template="vertical-menu-template-free"
>
  <head>
    <meta charset="utf-8" />
    <meta
      name="viewport"
      content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0"
    />

    <title>@yield('title') | SPK CPCL Dinas Pertanian</title>

    <meta name="description" content="" />

    <link rel="icon" type="image/x-icon" href="{{ asset('assets/img/favicon/favicon.ico') }}" />

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap"
      rel="stylesheet"
    />

    <link rel="stylesheet" href="{{ asset('assets/vendor/fonts/boxicons.css') }}" />

    <link rel="stylesheet" href="{{ asset('assets/vendor/css/core.css') }}" class="template-customizer-core-css" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/css/theme-default.css') }}" class="template-customizer-theme-css" />
    <link rel="stylesheet" href="{{ asset('assets/css/demo.css') }}" />

    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/apex-charts/apex-charts.css') }}" />

    <script src="{{ asset('assets/vendor/js/helpers.js') }}"></script>
    <script src="{{ asset('assets/js/config.js') }}"></script>

    <style>
        /* Overlay menutup seluruh layar */
        #global-loader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.95); /* Putih bersih transparan */
            z-index: 999999; /* Paling atas */
            display: flex;
            justify-content: center;
            align-items: center;
            transition: opacity 0.4s ease-out, visibility 0.4s;
        }

        /* Class untuk menyembunyikan loader */
        .loader-hidden {
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
        }

        /* Animasi teks kedip */
        .loading-text {
            animation: blink 1.5s infinite;
        }
        @keyframes blink {
            0% { opacity: 0.5; }
            50% { opacity: 1; }
            100% { opacity: 0.5; }
        }
    </style>
  </head>

<body>

    <div id="global-loader">
        <div class="d-flex flex-column justify-content-center align-items-center">
            <div class="spinner-border spinner-border-lg text-primary" role="status" style="width: 3.5rem; height: 3.5rem; border-width: 0.25em;">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-3 fw-bold text-primary loading-text" style="letter-spacing: 1px; font-size: 0.9rem;">MEMPROSES DATA...</p>
        </div>
    </div>
    <div class="layout-wrapper layout-content-navbar">
      <div class="layout-container">

        @include('uptd.layouts.sidebar')

        <div class="layout-page">

          @include('uptd.layouts.navbar')

          <div class="content-wrapper">
            @yield('content')

            @include('uptd.layouts.footer')

            <div class="content-backdrop fade"></div>
          </div>
        </div>
      </div>

      <div class="layout-overlay layout-menu-toggle"></div>
    </div>

    <script src="{{ asset('assets/vendor/libs/jquery/jquery.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/popper/popper.js') }}"></script>
    <script src="{{ asset('assets/vendor/js/bootstrap.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js') }}"></script>

    <script src="{{ asset('assets/vendor/js/menu.js') }}"></script>

    <script src="{{ asset('assets/vendor/libs/apex-charts/apexcharts.js') }}"></script>

    <script src="{{ asset('assets/js/main.js') }}"></script>
    <script src="{{ asset('assets/js/dashboards-analytics.js') }}"></script>
    <script async defer src="https://buttons.github.io/buttons.js"></script>


    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const loader = document.getElementById("global-loader");

            // A. SAAT HALAMAN SELESAI DIMUAT -> HILANGKAN LOADER
            window.addEventListener("load", function() {
                setTimeout(function() {
                    loader.classList.add("loader-hidden");
                }, 300); // Delay dikit biar mulus
            });

            // B. SAAT KLIK LINK (Pindah Halaman) -> MUNCULKAN LOADER
            document.addEventListener("click", function(e) {
                const link = e.target.closest("a");
                
                if (link) {
                    const href = link.getAttribute("href");
                    const target = link.getAttribute("target");

                    // Validasi: Loader cuma muncul kalau linknya pindah halaman internal
                    // Bukan modal, bukan javascript:, bukan link download
                    if (
                        href && 
                        href !== "#" && 
                        !href.startsWith("javascript") && 
                        target !== "_blank" &&
                        !link.hasAttribute('data-bs-toggle') && // Biar gak muncul pas buka dropdown/modal
                        !link.hasAttribute('data-bs-dismiss')
                    ) {
                        loader.classList.remove("loader-hidden");
                    }
                }
            });

            // C. SAAT SUBMIT FORM (Simpan/Edit/Hapus) -> MUNCULKAN LOADER
            document.addEventListener("submit", function(e) {
                // Pastikan form valid dulu
                if (e.target.checkValidity()) {
                    loader.classList.remove("loader-hidden");
                    
                    // Opsional: Disable tombol submit biar gak diklik 2x
                    const btn = e.target.querySelector('button[type="submit"]');
                    if(btn) {
                        // Simpan teks asli
                        const originalText = btn.innerHTML;
                        btn.disabled = true;
                        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Loading...';
                        
                        // Jaga-jaga kalau error validasi backend balik lagi, enable lagi (set timeout)
                        setTimeout(() => {
                            btn.disabled = false;
                            btn.innerHTML = originalText;
                        }, 8000); 
                    }
                }
            });

            // D. FIX TOMBOL BACK (Safari/Firefox Cache)
            // Biar kalau user tekan tombol Back browser, loadingnya ilang
            window.addEventListener("pageshow", function(event) {
                if (event.persisted) {
                    loader.classList.add("loader-hidden");
                }
            });
        });
    </script>

    @stack('scripts');

</body>
</html>
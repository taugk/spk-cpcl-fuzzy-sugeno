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

    <link rel="icon" type="image/x-icon" href="{{ asset('assets\img\icons\brands\logo.svg') }}" />

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
        /* 1. Pastikan Z-INDEX loader lebih tinggi dari sidebar Sneat (biasanya 1075-1100) */
        #global-loader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #f1f8e9, #e8f5e9);
            z-index: 999999 !important; /* Menaikkan lapisan ke paling atas */
            display: flex;
            justify-content: center;
            align-items: center;
            transition: opacity 0.3s ease-out, visibility 0.3s;
        }

        .loader-hidden {
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
        }

        /* 2. Style SweetAlert agar di atas segalanya */
        .swal2-container {
            z-index: 1000000 !important;
        }

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
            <div class="spinner-border spinner-border-lg" style="color:#2e7d32;" role="status" style="width: 3.5rem; height: 3.5rem; border-width: 0.25em;">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-3 fw-bold loading-text" style="color:#2e7d32;" style="letter-spacing: 1px; font-size: 0.9rem;">MEMPROSES DATA...</p>
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

        // A. HILANGKAN LOADER SAAT HALAMAN SELESAI DIMUAT
        window.addEventListener("load", function() {
            setTimeout(function() {
                loader.classList.add("loader-hidden");
            }, 300);
        });

        // B. LOADER SAAT KLIK LINK
        document.addEventListener("click", function(e) {
            const link = e.target.closest("a");
            if (link) {
                const href = link.getAttribute("href");
                const target = link.getAttribute("target");

                if (href && href !== "#" && !href.startsWith("javascript") && 
                    target !== "_blank" && !link.hasAttribute('data-bs-toggle') && 
                    !link.hasAttribute('data-bs-dismiss')) {
                    loader.classList.remove("loader-hidden");
                }
            }
        });

        // C. GLOBAL SWEETALERT UNTUK HAPUS
        document.addEventListener("click", function(e) {
            const btn = e.target.closest(".btn-delete-confirm");
            if (btn) {
                e.preventDefault();
                const form = btn.closest("form");

                Swal.fire({
                    title: 'Yakin ingin menghapus?',
                    text: "Data yang dihapus tidak dapat dikembalikan!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal',
                    customClass: {
                        confirmButton: 'btn btn-primary me-3',
                        cancelButton: 'btn btn-label-secondary'
                    },
                    buttonsStyling: false,
                    // Tambahan: Backdrop agar sidebar tertutup overlay hitam
                    backdrop: true 
                }).then((result) => {
                    if (result.isConfirmed) {
                        loader.classList.remove("loader-hidden");
                        form.submit();
                    }
                });
            }
        });

        // D. LOADER & SWEETALERT SAAT SUBMIT FORM
        document.addEventListener("submit", function(e) {
            const form = e.target;
            if (!form.checkValidity()) return;

            if (form.classList.contains("form-confirm") && !form.dataset.confirmed) {
                e.preventDefault();
                Swal.fire({
                    title: 'Simpan Data?',
                    text: "Pastikan data yang Anda masukkan sudah benar.",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Simpan!',
                    cancelButtonText: 'Batal',
                    customClass: {
                        confirmButton: 'btn btn-primary me-3',
                        cancelButton: 'btn btn-label-secondary'
                    },
                    buttonsStyling: false,
                    backdrop: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.dataset.confirmed = "true";
                        loader.classList.remove("loader-hidden");
                        form.submit();
                    }
                });
            } else {
                loader.classList.remove("loader-hidden");
            }
        });

        // E. FIX TOMBOL BACK
        window.addEventListener("pageshow", function(event) {
            if (event.persisted) {
                loader.classList.add("loader-hidden");
            }
        });
    
        // F. GLOBAL SWEETALERT UNTUK NOTIFIKASI SESSION DARI CONTROLLER
        @if(session('success'))
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: '{{ session('success') }}',
                timer: 3000,
                showConfirmButton: false,
                backdrop: true
            });
        @endif

        @if(session('error'))
            Swal.fire({
                icon: 'error',
                title: 'Gagal!',
                text: '{{ session('error') }}',
                backdrop: true
            });
        @endif

        // Tambahan: Global SweetAlert untuk error validasi ($errors)
        @if($errors->any())
            Swal.fire({
                icon: 'error',
                title: 'Validasi Gagal!',
                html: `
                    <ul class="text-start mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                `,
                backdrop: true
            });
        @endif
    });
    </script>

    @stack('scripts');

</body>
</html>
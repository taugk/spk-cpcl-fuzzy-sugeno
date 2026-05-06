@extends('admin.layouts.app')

@section('title', 'Laporan Surat Keputusan (SK) CPCL')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    
    <div class="card mb-4 no-print">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="bx bx-filter-alt me-2"></i>Filter Laporan Surat Keputusan
            </h5>
            <small class="text-muted">admin - Sistem Pendukung Keputusan CPCL</small>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.laporan.index') }}" method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label fw-bold">Tahun Periode</label>
                    <select name="tahun" class="form-select">
                        <option value="">Semua Tahun</option>
                        @foreach($listTahun as $t)
                            <option value="{{ $t }}" {{ request('tahun') == $t ? 'selected' : '' }}>{{ $t }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Bidang Sektor</label>
                    <select name="bidang" class="form-select">
                        <option value="">Semua Bidang</option>
                        @foreach($listBidang as $b)
                            <option value="{{ $b }}" {{ request('bidang') == $b ? 'selected' : '' }}>{{ $b }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-success w-100">
                        <i class="bx bx-filter-alt me-1"></i> Filter
                    </button>
                    <a href="{{ route('admin.laporan.index') }}" class="btn btn-outline-secondary" title="Reset Filter">
                        <i class="bx bx-refresh"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card printable-area">
        <div class="card-header d-flex justify-content-between align-items-center border-bottom mb-3 py-3 no-print">
            <div class="d-flex align-items-center">
                <img src="{{ asset('assets/img/icons/brands/logo.svg') }}" alt="Logo" width="90" class="me-3">
                <div>
                    <h4 class="mb-0 fw-bold text-dark">LAPORAN SURAT KEPUTUSAN (SK) CPCL</h4>
                    <p class="mb-0 text-muted">Sistem Pendukung Keputusan - Unit Pelaksana Teknis Daerah (admin)</p>
                </div>
            </div>
            <div class="d-flex gap-2 no-print">
                <button onclick="window.print()" class="btn btn-danger btn-lg" title="Cetak/Export PDF">
                    <i class="bx bxs-printer me-1"></i> 
                    @if(auth()->user()->role == 'admin')
                        Cetak SK
                    @else
                        Cetak Laporan
                    @endif
                </button>
            </div>
        </div>

        <div class="card-body pt-3 no-print">
            <div class="alert alert-info">
                <i class="bx bx-info-circle me-1"></i> 
                @if(auth()->user()->role == 'admin')
                    Klik tombol <strong>"Cetak SK"</strong> di atas untuk menghasilkan dokumen cetak SK resmi beserta tabel lampirannya.
                @else
                    Klik tombol <strong>"Cetak Laporan"</strong> di atas untuk menghasilkan dokumen laporan penetapan CPCL.
                @endif
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle w-100">
                    <thead class="table-dark">
                        <tr class="text-center">
                            <th width="5%">No</th>
                            <th>Kelompok Tani</th>
                            <th>Ketua Kelompok</th>
                            <th>Desa/Lokasi</th>
                            <th>Bidang</th>
                            <th>Skor</th>
                            <th>Prioritas</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($data as $index => $row)
                        <tr>
                            <td class="text-center fw-bold">{{ $row->ranking }}</td>
                            <td>{{ $row->cpcl->nama_kelompok }}</td>
                            <td>{{ $row->cpcl->nama_ketua }}</td>
                            <td>{{ $row->cpcl->lokasi }}</td>
                            <td>{{ $row->cpcl->bidang }}</td>
                            <td class="text-center">{{ number_format($row->skor_akhir, 2) }}</td>
                            <td class="text-center"><span class="badge bg-success">{{ $row->status_kelayakan }}</span></td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center py-5">Tidak ada data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- AREA CETAK --}}
        <div class="d-none d-print-block print-container">
            
            @if(auth()->user()->role == 'admin')
            {{-- BAGIAN 1: NASKAH SK (HANYA UNTUK ADMIN) --}}
            <div class="print-page">
                <div class="text-center fw-bold mb-4" style="line-height: 1.2;">
                    <div style="font-size: 14pt; margin-bottom: 5px;">BUPATI KUNINGAN</div>
                    <div style="font-size: 14pt; margin-bottom: 20px;">PROVINSI JAWA BARAT</div>
                    
                    <div style="font-size: 14pt; margin-bottom: 5px; text-decoration: underline;">KEPUTUSAN BUPATI KUNINGAN</div>
                    <div style="font-size: 12pt; margin-bottom: 20px;">Nomor: ......... /KPTS. ......... -DISKATAN/{{ request('tahun') ?? \Carbon\Carbon::now()->year }}</div>
                    
                    <div style="font-size: 12pt; margin-bottom: 5px;">TENTANG</div>
                    <div style="font-size: 12pt; margin-bottom: 20px;">
                        PENETAPAN KELAS KEMAMPUAN KELOMPOK TANI DAN GABUNGAN<br>
                        KELOMPOK ΤΑΝΙ ΚABUPATEN KUNINGAN TAHUN {{ request('tahun') ?? \Carbon\Carbon::now()->year }}
                    </div>
                    
                    <div style="font-size: 14pt;">BUPATI KUNINGAN,</div>
                </div>

                <table class="layout-table w-100 mb-3 text-justify">
                    <tr>
                        <td width="15%" class="vertical-top">Menimbang</td>
                        <td width="2%" class="vertical-top text-center">:</td>
                        <td class="vertical-top">
                            <ol type="a" class="print-list">
                                <li>bahwa dalam rangka pemberdayaan kelompok tani dan gabungan kelompok tani yang berfungsi sebagai kelas belajar, wahana kerjasama, unit produksi dan unit usaha, dilakukan kegiatan penyuluhan;</li>
                                <li>bahwa guna mendukung efektifitas kegiatan penyuluhan kelompok tani dan gabungan kelompok tani dimaksud perlu menetapkan kelas kemampuan kelompok tani, dan gabungan kelompok tani;</li>
                                <li>bahwa berdasarkan pertimbangan sebagaimana dimaksud huruf a dan huruf b, untuk menjamin kepastian hukum dalam pelaksanaannya perlu ditetapkan dengan Keputusan Bupati;</li>
                            </ol>
                        </td>
                    </tr>
                    <tr>
                        <td class="vertical-top">Mengingat</td>
                        <td class="vertical-top text-center">:</td>
                        <td class="vertical-top">
                            <ol type="1" class="print-list">
                                <li>Undang-Undang Nomor 12 Tahun 1992 tentang Sistem Budidaya Tanaman;</li>
                                <li>Undang-Undang Nomor 16 Tahun 2006 tentang Sistem Penyuluhan Pertanian, Perikanan dan Kehutanan;</li>
                                <li>Undang-Undang Nomor 5 Tahun 1990 tentang Konservasi Sumber Daya Alam Hayati dan Ekosistemnya; Sebagaimana telah diubah dengan Undang-Undang Nomor 32 Tahun 2024;</li>
                                <li>Undang-Undang Nomor 18 Tahun 2012 tentang Pangan;</li>
                            </ol>
                        </td>
                    </tr>
                </table>

                <div class="text-center fw-bold mb-3" style="font-size: 12pt;">MEMUTUSKAN:</div>
                
                <table class="layout-table w-100 text-justify">
                    <tr>
                        <td width="15%" class="vertical-top">Menetapkan</td>
                        <td width="2%" class="vertical-top text-center">:</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td class="vertical-top">KESATU</td>
                        <td class="vertical-top text-center">:</td>
                        <td class="vertical-top mb-2 d-block">
                            Menetapkan Kelas Kemampuan Kelompok Tani dan Prioritas Calon Penerima Calon Lokasi (CPCL) Kabupaten Kuningan Tahun {{ request('tahun') ?? \Carbon\Carbon::now()->year }} sebagaimana tercantum dalam Lampiran Keputusan ini.
                        </td>
                    </tr>
                    <tr>
                        <td class="vertical-top">KEDUA</td>
                        <td class="vertical-top text-center">:</td>
                        <td class="vertical-top mb-2 d-block">
                            Keputusan ini mulai berlaku pada tanggal ditetapkan, dengan ketentuan akan diadakan perbaikan atau perubahan seperlunya apabila di kemudian hari terdapat kekeliruan dalam penetapannya.
                        </td>
                    </tr>
                </table>

                <div class="signature-area mt-5">
                    <table style="width: 100%; border: none;">
                        <tr>
                            <td width="50%" style="border: none;"></td>
                            <td width="50%" class="text-left" style="border: none; padding-left: 50px;">
                                Ditetapkan di Kuningan<br>
                                pada tanggal .................. {{ request('tahun') ?? \Carbon\Carbon::now()->year }}<br><br>
                                <strong>   BUPATI KUNINGAN,</strong><br>
                                <br><br><br><br><br>
                                <strong>( ........................................... )</strong>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="page-break-print"></div>
            @endif

            {{-- HEADER HALAMAN TABEL --}}
            <div class="print-page">
                @if(auth()->user()->role == 'admin')
                    {{-- Header Lampiran SK untuk Admin --}}
                    <table class="layout-table w-100 mb-4">
                        <tr>
                            <td width="12%" class="vertical-top">Lampiran</td>
                            <td width="2%" class="vertical-top text-center">:</td>
                            <td class="vertical-top">KEPUTUSAN BUPATI KUNINGAN</td>
                        </tr>
                        <tr>
                            <td class="vertical-top">Nomor</td>
                            <td class="vertical-top text-center">:</td>
                            <td class="vertical-top">......... /KPTS. ......... -DISKATAN/{{ request('tahun') ?? \Carbon\Carbon::now()->year }}</td>
                        </tr>
                        <tr>
                            <td class="vertical-top">Tanggal</td>
                            <td class="vertical-top text-center">:</td>
                            <td class="vertical-top">......... {{ request('tahun') ?? \Carbon\Carbon::now()->year }}</td>
                        </tr>
                        <tr>
                            <td class="vertical-top">Tentang</td>
                            <td class="vertical-top text-center">:</td>
                            <td class="text-justify vertical-top">
                                PENETAPAN KELAS KEMAMPUAN KELOMPOK TANI DAN GABUNGAN KELOMPOK TANI KABUPATEN KUNINGAN TAHUN {{ request('tahun') ?? \Carbon\Carbon::now()->year }}
                            </td>
                        </tr>
                    </table>
                    <div class="text-center fw-bold mb-3" style="font-size: 11pt;">
                        A. DAFTAR KELAS KEMAMPUAN DAN PRIORITAS KELOMPOK TANI
                    </div>
                @else
                    {{-- Header Laporan Biasa untuk Non-Admin --}}
                    <div class="text-center mb-4">
                        <h4 class="fw-bold mb-1">LAPORAN PENETAPAN CPCL KABUPATEN KUNINGAN</h4>
                        <p class="mb-0">Tahun Periode: {{ request('tahun') ?? \Carbon\Carbon::now()->year }}</p>
                        @if(request('bidang')) <p class="mb-0">Sektor/Bidang: {{ request('bidang') }}</p> @endif
                        <hr style="border: 1px solid #000; margin-top: 10px;">
                    </div>
                @endif

                {{-- TABEL DATA (DIGUNAKAN OLEH KEDUA ROLE) --}}
                <table class="table-cetak w-100">
                    <thead>
                        <tr>
                            <th width="5%">No.</th>
                            <th width="20%">Nama Kelompok Tani</th>
                            <th width="15%">Nama Ketua</th>
                            <th width="15%">Desa / Lokasi</th>
                            <th width="12%">Sektor / Bidang</th>
                            <th width="10%">Luas Lahan</th>
                            <th width="7%">Skor</th>
                            <th width="10%">Prioritas</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($data as $index => $row)
                        <tr>
                            <td class="text-center">{{ $loop->iteration }}</td>
                            <td>{{ $row->cpcl->nama_kelompok }}</td>
                            <td>{{ $row->cpcl->nama_ketua }}</td>
                            <td class="text-center">{{ $row->cpcl->lokasi }}</td>
                            <td class="text-center">{{ $row->cpcl->bidang }}</td>
                            <td class="text-center">{{ number_format($row->cpcl->luas_lahan, 2) }} Ha</td>
                            <td class="text-center fw-bold">{{ number_format($row->skor_akhir, 2) }}</td>
                            <td class="text-center">{{ $row->status_kelayakan }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center py-3">Tidak ada data penetapan untuk periode ini.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>

                {{-- SIGNATURE AREA --}}
                <div class="signature-area mt-5" style="page-break-inside: avoid;">
                    <table style="width: 100%; border: none;">
                        <tr>
                            <td width="50%" style="border: none;"></td>
                            <td width="50%" class="text-left" style="border: none; padding-left: 50px;">
                                Ditetapkan di Kuningan<br>
                                pada tanggal .................. {{ request('tahun') ?? \Carbon\Carbon::now()->year }}<br><br>
                                @if(auth()->user()->role == 'admin')
                                    <strong>   BUPATI KUNINGAN,</strong><br>
                                    <br><br><br><br><br>
                                    <strong>( ........................................... )</strong>
                                @else
                                    <strong>   ADMINISTRATOR SPK,</strong><br>
                                    <br><br><br><br><br>
                                    <strong>( {{ auth()->user()->name }} )</strong>
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
            </div> {{-- End print-page --}}
        </div> {{-- End print-container --}}
    </div>
</div>

<style>
    /* Print Styles */
    @media print {
        @page {
            size: legal; 
            margin: 2cm 1.5cm 1.5cm 2cm; 
        }

        .no-print, .layout-navbar, .layout-menu, .footer, .content-footer, .btn, aside, nav, .alert {
            display: none !important;
        }

        body, html, .layout-wrapper, .layout-container, .layout-page, .content-wrapper, .container-xxl {
            background-color: transparent !important;
            padding: 0 !important;
            margin: 0 !important;
            width: 100% !important;
        }

        .card, .card-body {
            border: none !important;
            box-shadow: none !important;
        }

        .print-container {
            display: block !important;
            font-family: 'Bookman Old Style', 'Times New Roman', Times, serif !important;
            color: #000 !important;
            font-size: 11pt !important;
            line-height: 1.4 !important;
        }

        .text-justify { text-align: justify !important; }
        .vertical-top { vertical-align: top !important; }
        
        .layout-table td {
            border: none !important;
            padding: 2px 5px !important;
        }

        .print-list {
            padding-left: 20px !important;
            margin-top: 0 !important;
            margin-bottom: 0 !important;
        }
        
        .print-list li {
            margin-bottom: 8px !important;
        }

        .page-break-print {
            page-break-before: always !important;
            break-before: page !important;
        }

        .table-cetak {
            width: 100% !important;
            border-collapse: collapse !important;
            margin-bottom: 20px !important;
            font-family: 'Times New Roman', Times, serif !important;
        }

        .table-cetak th, .table-cetak td {
            border: 1px solid #000 !important;
            padding: 5px !important;
            font-size: 10pt !important;
            vertical-align: middle !important;
            word-wrap: break-word !important;
        }

        .table-cetak th {
            text-align: center !important;
            font-weight: bold !important;
            background-color: #f2f2f2 !important;
            -webkit-print-color-adjust: exact !important; 
            print-color-adjust: exact !important;
        }

        .table-cetak tr {
            page-break-inside: avoid !important;
        }
    }
</style>
@endsection
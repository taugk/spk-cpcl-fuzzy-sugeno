@extends('uptd.layouts.app')

@section('title', 'Cetak Surat Keputusan (SK)')

@section('content')

{{-- =====================================================
     TOMBOL AKSI — hilang saat print
===================================================== --}}
<div class="container-xxl flex-grow-1 container-p-y no-print">
    <div class="d-flex gap-2 mb-4">
        <button onclick="window.print()" class="btn btn-success">
            <i class="bx bxs-printer me-1"></i> Cetak Dokumen
        </button>
        <a href="{{ route('uptd.laporan.index') }}" class="btn btn-secondary">
            <i class="bx bx-arrow-back me-1"></i> Kembali
        </a>
    </div>
</div>


<div id="printable-content">

    <div class="sk-container">

        {{-- =====================================================
             KOP SURAT
        ===================================================== --}}
        <table class="header-table">
            <tr>
                <td style="width:25%; vertical-align:middle;">
                    <img src="{{ asset('assets/img/icons/brands/logo.svg') }}" class="logo">
                </td>
                <td style="text-align:center; vertical-align:middle;">
                    <h5 class="mb-0">PEMERINTAH KABUPATEN KUNINGAN</h5>
                    <h4 class="mb-0 fw-bold">DINAS PERTANIAN</h4>
                    <p class="mb-0" style="font-size:11pt;">Jl. Siliwangi No. 123, Kuningan &nbsp;|&nbsp; Telp. (0232) 123456</p>
                    <p class="mb-0" style="font-size:11pt;">Email: dinas@kuningan.go.id</p>
                </td>
                <td style="width:15%;"></td>
            </tr>
        </table>
        <hr class="separator">

        {{-- =====================================================
             JUDUL SK
        ===================================================== --}}
        <div class="sk-title">
            <h5>SURAT KEPUTUSAN</h5>
            <p class="mb-1">
                <strong>Nomor : {{ $nomorSK }}</strong>
            </p>
            <p class="mb-0"><strong>TENTANG</strong></p>
            <p>
                <strong>
                    PENETAPAN KELOMPOK TANI PENERIMA PROGRAM CPCL<br>
                    KABUPATEN KUNINGAN TAHUN {{ now()->year }}
                </strong>
            </p>
        </div>

        {{-- =====================================================
             BATANG TUBUH SK
        ===================================================== --}}
        <div class="sk-body">

            <p><strong>KEPALA DINAS PERTANIAN KABUPATEN KUNINGAN,</strong></p>

            {{-- MENIMBANG --}}
            <table class="kons-table">
                <tr>
                    <td class="kons-label">Menimbang</td>
                    <td class="kons-titik">:</td>
                    <td>
                        <ol type="a" class="kons-list">
                            <li>
                                bahwa dalam rangka pengembangan kelompok tani dan peningkatan
                                kapasitas produksi pertanian di Kabupaten Kuningan, perlu ditetapkan
                                kelompok tani yang memenuhi kriteria sebagai penerima manfaat
                                Program CPCL (Calon Petani dan Calon Lahan);
                            </li>
                            <li>
                                bahwa berdasarkan hasil penilaian dan evaluasi dengan menggunakan
                                Sistem Pendukung Keputusan (SPK) berbasis metode Fuzzy Sugeno,
                                kelompok tani sebagaimana dimaksud dalam huruf a telah memenuhi
                                kriteria yang ditetapkan;
                            </li>
                            <li>
                                bahwa berdasarkan pertimbangan sebagaimana dimaksud huruf a dan
                                huruf b, perlu menetapkan Keputusan Kepala Dinas Pertanian
                                Kabupaten Kuningan tentang Penetapan Kelompok Tani Penerima
                                Program CPCL;
                            </li>
                        </ol>
                    </td>
                </tr>
            </table>

            {{-- MENGINGAT --}}
            <table class="kons-table">
                <tr>
                    <td class="kons-label">Mengingat</td>
                    <td class="kons-titik">:</td>
                    <td>
                        <ol class="kons-list">
                            <li>Undang-Undang Nomor 12 Tahun 1992 tentang Sistem Budidaya Tanaman;</li>
                            <li>Undang-Undang Nomor 16 Tahun 2006 tentang Sistem Penyuluhan Pertanian, Perikanan dan Kehutanan;</li>
                            <li>Undang-Undang Nomor 19 Tahun 2013 tentang Perlindungan dan Pemberdayaan Petani, sebagaimana telah diubah dengan Undang-Undang Nomor 6 Tahun 2023;</li>
                            <li>Peraturan Pemerintah Nomor 43 Tahun 2009 tentang Pembiayaan, Pembinaan, dan Pengawasan Penyuluhan Pertanian, Perikanan dan Kehutanan;</li>
                            <li>Peraturan Menteri Pertanian Nomor 67/Permentan/SM.050/12/2016 tentang Pembinaan Kelembagaan Petani;</li>
                            <li>Peraturan Daerah Kabupaten Kuningan Nomor 5 Tahun 2022 tentang Pembangunan Pangan Daerah;</li>
                            <li>Peraturan Bupati Kuningan Nomor 73 Tahun 2019 tentang Kedudukan, Susunan Organisasi, Tugas Pokok, Fungsi dan Uraian Tugas serta Tata Kerja Dinas Pertanian Kabupaten Kuningan;</li>
                            <li>Hasil penilaian dari Sistem Pendukung Keputusan (SPK) CPCL Dinas Pertanian Kabupaten Kuningan;</li>
                        </ol>
                    </td>
                </tr>
            </table>

            {{-- MEMUTUSKAN --}}
            <p class="text-center fw-bold mt-3 mb-2" style="font-size:12pt; letter-spacing:1px;">
                MEMUTUSKAN :
            </p>

            <table class="kons-table">
                <tr>
                    <td class="kons-label">Menetapkan</td>
                    <td class="kons-titik">:</td>
                    <td>
                        Keputusan Kepala Dinas Pertanian Kabupaten Kuningan Tentang Penetapan
                        Kelompok Tani Penerima Program CPCL Tahun {{ now()->year }}.
                    </td>
                </tr>
            </table>

            {{-- PASAL 1 — Data kelompok tani dari $cpcl & $hasilFuzzy --}}
            <p class="mt-3 mb-1"><strong>Pasal 1</strong></p>
            <p>
                Menetapkan kelompok tani berikut sebagai penerima manfaat
                Program CPCL berdasarkan hasil penilaian Sistem Pendukung Keputusan:
            </p>

            <table class="data-table">
                <tr>
                    <td>Nama Kelompok Tani</td>
                    <td width="5%">:</td>
                    <td><strong>{{ $cpcl->nama_kelompok }}</strong></td>
                </tr>
                <tr>
                    <td>Ketua Kelompok</td>
                    <td>:</td>
                    <td>{{ $cpcl->nama_ketua }}</td>
                </tr>
                @if($cpcl->nik_ketua)
                <tr>
                    <td>NIK Ketua</td>
                    <td>:</td>
                    <td>{{ $cpcl->nik_ketua }}</td>
                </tr>
                @endif
                <tr>
                    <td>Lokasi / Wilayah</td>
                    <td>:</td>
                    <td>{{ $cpcl->lokasi }}</td>
                </tr>
                <tr>
                    <td>Bidang Sektor</td>
                    <td>:</td>
                    <td>{{ $cpcl->bidang }}</td>
                </tr>
                <tr>
                    <td>Luas Lahan</td>
                    <td>:</td>
                    <td>{{ $cpcl->luas_lahan }} Hektar</td>
                </tr>
                <tr>
                    <td>Skor Penilaian (SPK)</td>
                    <td>:</td>
                    <td>
                        <strong>{{ number_format($hasilFuzzy->skor_akhir, 2) }}%</strong>
                    </td>
                </tr>
                <tr>
                    <td>Skala Prioritas</td>
                    <td>:</td>
                    <td>
                        @php
                            $badgeClass = match($hasilFuzzy->skala_prioritas) {
                                'Prioritas I'   => 'badge-utama',
                                'Prioritas II'  => 'badge-madya',
                                'Prioritas III' => 'badge-lanjut',
                                'Prioritas IV'  => 'badge-pemula',
                                default         => '',
                            };
                        @endphp
                        <span class="kelas-badge {{ $badgeClass }}">
                            {{ $hasilFuzzy->skala_prioritas }}
                        </span>
                    </td>
                </tr>
                <tr>
                    <td>Ranking</td>
                    <td>:</td>
                    <td>{{ $hasilFuzzy->ranking ?? '-' }}</td>
                </tr>
            </table>

            {{-- PASAL 2 --}}
            <p class="mt-3 mb-1"><strong>Pasal 2</strong></p>
            <p>
                Keputusan ini mulai berlaku pada tanggal ditetapkan dan akan dievaluasi
                setiap tahun sesuai dengan perkembangan dan kebutuhan program.
            </p>

            {{-- PASAL 3 --}}
            <p class="mt-3 mb-1"><strong>Pasal 3</strong></p>
            <p>
                Segala biaya yang timbul akibat pelaksanaan keputusan ini dibebankan
                pada anggaran Dinas Pertanian Kabupaten Kuningan.
            </p>

            {{-- PASAL 4 --}}
            <p class="mt-3 mb-1"><strong>Pasal 4</strong></p>
            <p>
                Apabila di kemudian hari terdapat kekeliruan dalam Keputusan ini
                akan diadakan perbaikan sebagaimana mestinya.
            </p>

        </div>{{-- /sk-body --}}

        {{-- =====================================================
             TANDA TANGAN
        ===================================================== --}}
        <div class="signature-section">
            <div class="date-box">
                <p>
                    Ditetapkan di &nbsp;<strong>Kuningan</strong><br>
                    pada tanggal &nbsp;{{ $tanggalSK }}
                </p>
            </div>

            <div class="signature-box">
                
                <div class="sig">
                    <p>Kepala Dinas Pertanian<br><strong>Kabupaten Kuningan</strong></p>
                    <br><br><br>
                    <p><u>________________________</u><br>NIP. ...........................</p>
                </div>
            </div>
        </div>

    </div>{{-- /sk-container --}}

</div>{{-- /printable-content --}}


<style>
/* =====================================================
   TAMPILAN LAYAR
===================================================== */
.sk-container {
    background: #fff;
    padding: 50px;
    color: #000;
    font-family: 'Times New Roman', serif;
    font-size: 12pt;
    max-width: 800px;
    margin: 0 auto 30px;
    line-height: 1.6;
    border: 1px solid #ddd;
    box-shadow: 0 2px 8px rgba(0,0,0,.08);
}

.header-table {
    width: 100%;
    padding-bottom: 8px;
}

.logo { width: 80px; }

.separator {
    border: none;
    border-top: 3px double #000;
    margin: 0 0 16px;
}

.sk-title {
    text-align: center;
    margin: 24px 0 20px;
}

/* Konsideran */
.kons-table {
    width: 100%;
    margin-bottom: 8px;
    border: none;
    border-collapse: collapse;
}

.kons-table td {
    vertical-align: top;
    padding: 2px 0;
    border: none;
}

.kons-label {
    width: 128px;
    font-weight: normal;
}

.kons-titik {
    width: 16px;
    text-align: center;
}

.kons-list {
    padding-left: 18px;
    margin: 0;
}

.kons-list li { margin-bottom: 5px; }

/* Tabel data kelompok */
.data-table {
    width: 100%;
    margin: 10px 0 10px 20px;
    border-collapse: collapse;
}

.data-table td {
    padding: 3px 6px;
    vertical-align: top;
    font-size: 12pt;
}

.data-table tr:nth-child(odd) td {
    background: #f9f9f9;
}

/* Badge prioritas */
.kelas-badge {
    display: inline-block;
    padding: 2px 10px;
    border-radius: 3px;
    font-size: 10.5pt;
    font-weight: bold;
    border: 1px solid #888;
}

.badge-utama  { background: #d4edda; color: #155724; }
.badge-madya  { background: #cce5ff; color: #004085; }
.badge-lanjut { background: #fff3cd; color: #856404; }
.badge-pemula { background: #f8d7da; color: #721c24; }

/* Tanda tangan */
.signature-section { margin-top: 50px; }

.date-box {
    text-align: right;
    margin-bottom: 50px;
}

.signature-box {
    display: flex;
    justify-content: space-between;
}

.sig {
    text-align: center;
    width: 45%;
}


/* =====================================================
   PRINT
===================================================== */
@media print {

    @page {
        size: A4;
        margin: 2cm;
    }

    /* Sembunyikan seluruh layout Sneat */
    body * { visibility: hidden; }

    /* Tampilkan hanya konten SK */
    #printable-content,
    #printable-content * { visibility: visible; }

    #printable-content {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
    }

    .no-print { display: none !important; }

    .sk-container {
        padding: 0;
        max-width: 100%;
        border: none;
        box-shadow: none;
        margin: 0;
    }

    .data-table tr:nth-child(odd) td { background: none; }

    .kons-table   { page-break-inside: avoid; }
    .data-table   { page-break-inside: avoid; }
    .signature-section { page-break-inside: avoid; }
}
</style>

@endsection
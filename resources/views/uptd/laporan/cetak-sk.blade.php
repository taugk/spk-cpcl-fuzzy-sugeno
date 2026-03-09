@extends('admin.layouts.app')

@section('title', 'Cetak Surat Keputusan (SK)')

@section('content')

<div class="container-xxl flex-grow-1 container-p-y no-print">
    <div class="d-flex gap-2 mb-4">
        <button onclick="window.print()" class="btn btn-primary">
            <i class="bx bxs-printer me-1"></i> Cetak Dokumen
        </button>

        <a href="{{ route('uptd.laporan.index') }}" class="btn btn-secondary">
            <i class="bx bx-arrow-back me-1"></i> Kembali
        </a>
    </div>
</div>


<div id="printable-content">

    <div class="sk-container">

        <table class="header-table">
            <tr>
                <td style="width:15%; vertical-align:middle;">
                    <img src="{{ asset('assets/img/icons/brands/logo-dinas-pertanian.png') }}" class="logo">
                </td>

                <td style="text-align:center; vertical-align:middle;">
                    <h5 class="mb-0">PEMERINTAH KABUPATEN KUNINGAN</h5>
                    <h4 class="mb-0 fw-bold">DINAS PERTANIAN</h4>
                    <p class="mb-0" style="font-size:11pt;">
                        Jl. Siliwangi No. 123, Kuningan | Telp. (0232) 123456
                    </p>
                    <p class="mb-0" style="font-size:11pt;">
                        Email: dinas@kuningan.go.id
                    </p>
                </td>

                <td style="width:15%;"></td>
            </tr>
        </table>

        <hr class="separator">


        <div class="sk-title">
            <h5>SURAT KEPUTUSAN</h5>

            <p class="mb-1">
                <strong>Nomor: {{ $nomorSK ?? '006/SK/UPTD/2026' }}</strong>
            </p>

            <p>
                <strong>
                    Tentang Penetapan Kelompok Tani Penerima Program CPCL
                </strong>
            </p>
        </div>


        <div class="sk-body">

            <p><strong>Kepala Dinas Pertanian Kabupaten Kuningan,</strong></p>

            <p><strong>Menimbang bahwa:</strong></p>

            <ol>
                <li>
                    Dalam rangka pengembangan kelompok tani dan peningkatan kapasitas produksi pertanian;
                </li>

                <li>
                    Diperlukan penetapan kelompok tani penerima manfaat Program CPCL
                    (Calon Petani Cerdas Lestari);
                </li>

                <li>
                    Berdasarkan hasil evaluasi dan penilaian dengan Sistem Pendukung
                    Keputusan (SPK), kelompok tani berikut dinyatakan memenuhi kriteria;
                </li>
            </ol>


            <p><strong>Mengingat:</strong></p>

            <ol>
                <li>
                    Peraturan Daerah Kabupaten Kuningan Nomor ... Tahun ...
                    tentang Pertanian;
                </li>

                <li>
                    Keputusan Kepala Dinas Pertanian Kabupaten Kuningan
                    tentang Program CPCL;
                </li>

                <li>
                    Hasil penilaian dari Sistem Pendukung Keputusan (SPK) CPCL;
                </li>
            </ol>


            <p class="text-center fw-bold mt-3">MEMUTUSKAN:</p>


            <p><strong>Pasal 1</strong></p>

            <p>
                Menetapkan kelompok tani sebagai berikut sebagai penerima manfaat
                Program CPCL:
            </p>


            <table class="data-table">

                <tr>
                    <td width="30%">Nama Kelompok Tani</td>
                    <td width="5%">:</td>
                    <td>{{ $cpcl->nama_kelompok ?? 'Cileuleuy' }}</td>
                </tr>

                <tr>
                    <td>Ketua Kelompok</td>
                    <td>:</td>
                    <td>{{ $cpcl->nama_ketua ?? 'Suganda' }}</td>
                </tr>

                <tr>
                    <td>NIK Ketua</td>
                    <td>:</td>
                    <td>{{ $cpcl->nik_ketua ?? '1217162716326130' }}</td>
                </tr>

                <tr>
                    <td>Lokasi Kelompok</td>
                    <td>:</td>
                    <td>{{ $cpcl->lokasi ?? 'Cileuleuy Cigugur' }}</td>
                </tr>

                <tr>
                    <td>Bidang Sektor</td>
                    <td>:</td>
                    <td>{{ $cpcl->bidang ?? 'HARTIBUN' }}</td>
                </tr>

                <tr>
                    <td>Luas Lahan</td>
                    <td>:</td>
                    <td>{{ $cpcl->luas_lahan ?? '0.6' }} Hektar</td>
                </tr>

                <tr>
                    <td>Skor Penilaian</td>
                    <td>:</td>
                    <td>{{ $hasilFuzzy->skor_akhir ?? '52.00' }}%</td>
                </tr>

                <tr>
                    <td>Skala Prioritas</td>
                    <td>:</td>
                    <td>{{ $hasilFuzzy->skala_prioritas ?? 'Prioritas III' }}</td>
                </tr>

            </table>


            <p><strong>Pasal 2</strong></p>

            <p>
                Keputusan ini mulai berlaku pada tanggal ditetapkan dan
                akan dievaluasi setiap tahun sesuai dengan perkembangan
                dan kebutuhan program.
            </p>


            <p><strong>Pasal 3</strong></p>

            <p>
                Keputusan ini dibuat untuk dilaksanakan sebagaimana mestinya.
            </p>


            <p><strong>Pasal 4</strong></p>

            <p>
                Segala biaya yang timbul akibat pelaksanaan keputusan ini
                dibebankan pada anggaran Dinas Pertanian Kabupaten Kuningan.
            </p>

        </div>


        <div class="signature-section">

            <div class="date-box">
                <p>
                    Ditetapkan di Kuningan<br>
                    pada tanggal {{ $tanggalSK ?? '09 Maret 2026' }}
                </p>
            </div>


            <div class="signature-box">

                <div class="sig">
                    <p>
                        Diketahui oleh,<br>
                        Kepala UPTD
                    </p>

                    <br><br><br>

                    <p>
                        <u>________________________</u><br>
                        NIP. ...........................
                    </p>
                </div>


                <div class="sig">
                    <p>
                        Kepala Dinas Pertanian<br>
                        Kabupaten Kuningan
                    </p>

                    <br><br><br>

                    <p>
                        <u>________________________</u><br>
                        NIP. ...........................
                    </p>
                </div>

            </div>

        </div>

    </div>

</div>



<style>

/* Tampilan layar */
.sk-container{
    background:white;
    padding:50px;
    color:#000;
    font-family:'Times New Roman', serif;
    max-width:800px;
    margin:auto;
}

.header-table{
    width:100%;
    border-bottom:3px double #000;
    margin-bottom:20px;
}

.logo{
    width:80px;
}

.sk-title{
    text-align:center;
    margin:30px 0;
}

.data-table{
    width:100%;
    margin-left:20px;
}

.signature-section{
    margin-top:50px;
}

.date-box{
    text-align:right;
    margin-bottom:60px;
}

.signature-box{
    display:flex;
    justify-content:space-between;
}

.sig{
    text-align:center;
    width:45%;
}


/* PRINT SETTING */
@media print {

    @page{
        size:A4;
        margin:2cm;
    }

    /* sembunyikan semua layout */
    body *{
        visibility:hidden;
    }

    /* tampilkan hanya SK */
    #printable-content,
    #printable-content *{
        visibility:visible;
    }

    #printable-content{
        position:absolute;
        left:0;
        top:0;
        width:100%;
    }

    .no-print{
        display:none !important;
    }

    .sk-container{
        padding:0;
        border:none;
        box-shadow:none;
    }

    .data-table{
        page-break-inside:avoid;
    }

}

</style>

@endsection
@extends('admin.layouts.app')

@section('title', 'Laporan Analisis Fuzzy Sugeno')

@section('content')
<div class="content-wrapper">
    <div class="container-xxl container-p-y">

        {{-- HEADER --}}
        <div class="card mb-4 border-0 shadow-sm no-print">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="fw-bold mb-1">
                        <i class="bx bx-file-find text-primary me-2"></i>
                        Laporan Analisis Kelayakan Fuzzy Sugeno
                    </h4>
                    <p class="text-muted mb-0">
                        Subjek:
                        <span class="badge bg-label-primary fs-6">
                            {{ $hasil['cpcl']->nama_kelompok }}
                        </span>

                        <span class="badge bg-label-info ms-2">
                            {{ $hasil['cpcl']->bidang ?? '-' }}
                        </span>
                    </p>
                </div>

                <div class="d-flex gap-2">
                    <a href="{{ route('admin.perhitungan.index') }}" class="btn btn-outline-secondary">
                        <i class="bx bx-arrow-back me-1"></i> Kembali
                    </a>

                    <button onclick="window.print()" class="btn btn-primary">
                        <i class="bx bx-printer me-1"></i> Cetak Laporan
                    </button>
                </div>
            </div>
        </div>


        {{-- ================================================================ --}}
        {{-- STEP 1: FUZZIFIKASI --}}
        {{-- ================================================================ --}}
        <div class="card shadow-sm border-0 mb-4">

            <div class="card-header bg-white border-bottom py-3">
                <h5 class="fw-bold mb-0">
                    <span class="badge bg-primary me-2">Step 1</span>
                    Fuzzifikasi — Derajat Keanggotaan ($\mu$)
                </h5>

                <small class="text-muted d-block mt-2">
                    Menampilkan nilai riil input dan konversinya ke derajat keanggotaan fuzzy.
                </small>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">

                    <thead class="table-light text-center">
                        <tr class="text-uppercase small fw-bold">
                            <th style="width:25%">Kriteria & Nilai Riil</th>
                            <th>Himpunan Fuzzy & Analisis Derajat Keanggotaan</th>
                        </tr>
                    </thead>

                    <tbody>

                    @forelse($hasil['fuzzifikasi'] as $k)

                    <tr>

                        {{-- NILAI RIIL --}}
                        <td class="bg-light">

                            <div class="px-3">

                                <span class="fw-bold d-block text-primary fs-6">
                                    {{ $k['kode'] }}
                                </span>

                                <small class="text-dark d-block mb-2 fw-bold">
                                    {{ $k['nama'] }}
                                </small>

                                <div class="p-3 rounded bg-white border border-primary-subtle shadow-sm">

                                    <small class="text-muted d-block mb-1 text-uppercase"
                                           style="font-size:0.65rem;font-weight:800;">
                                        Nilai Riil Input
                                    </small>

                                    <div class="d-flex align-items-center">

                                        <h4 class="fw-bold mb-0 text-dark me-2">
                                            {{ $k['input'] }}
                                        </h4>

                                        @if($k['kode']=='C1')
                                            <span class="text-muted small">Ha</span>
                                        @elseif($k['kode']=='C3')
                                            <span class="text-muted small">Thn</span>
                                        @elseif($k['kode']=='C4')
                                            <span class="text-muted small">Ton/Ha</span>
                                        @endif

                                    </div>

                                </div>

                            </div>

                        </td>


                        {{-- HIMPUNAN --}}
                        <td class="p-3">

                            <div class="row g-3">

                            @forelse($k['himpunan'] as $s)

                            <div class="col-12 col-md-6">

                                <div class="p-3 rounded border border-success border-2 bg-label-success shadow-sm">

                                    <div class="d-flex justify-content-between align-items-center">

                                        <div>

                                            <span class="fw-bold text-uppercase small d-block mb-1">
                                                {{ $s['nama'] }}
                                            </span>

                                            <div class="small text-success">
                                                <i class="bx bx-check-circle me-1"></i>
                                                <strong>Aktif</strong>
                                            </div>

                                        </div>

                                        <div class="text-end">

                                            <span class="badge bg-success fs-5">
                                                $\mu$ = {{ number_format($s['mu'],4) }}
                                            </span>

                                            <div class="mt-1 small text-muted">
                                                Konsekuen: $k$ = {{ number_format($s['k'],2) }}
                                            </div>

                                        </div>

                                    </div>

                                </div>

                            </div>

                            @empty

                            <div class="col-12">

                                <div class="alert alert-danger d-flex align-items-center mb-0">

                                    <i class="bx bx-error-circle me-2 fs-4"></i>

                                    <div>
                                        <strong>Tidak Aktif:</strong>
                                        Nilai input tidak sesuai range ($\mu = 0$)
                                    </div>

                                </div>

                            </div>

                            @endforelse

                            </div>

                        </td>

                    </tr>

                    @empty
                        <tr>
                            <td colspan="2" class="text-center py-4">
                                Data tidak tersedia
                            </td>
                        </tr>
                    @endforelse

                    </tbody>

                </table>
            </div>
        </div>


        {{-- ================================================================ --}}
        {{-- STEP 2 & 3: RULE BASE --}}
        {{-- ================================================================ --}}
        <div class="card shadow-sm border-0 mb-4">

            <div class="card-header bg-white border-bottom py-3">

                <h5 class="fw-bold mb-0">
                    <span class="badge bg-warning text-dark me-2">Step 2 & 3</span>
                    Rule Base & Firing Strength
                </h5>

            </div>

            <div class="table-responsive">

                <table class="table table-bordered align-middle mb-0">

                    <thead class="table-light text-center small fw-bold">
                        <tr>
                            <th>Rule</th>
                            <th>Anteceden (IF)</th>
                            <th>$\alpha$</th>
                            <th>$z$</th>
                            <th>$\alpha \times z$</th>
                        </tr>
                    </thead>

                    <tbody>

                    @foreach($hasil['rules'] as $rule)

                    <tr class="text-center">

                        <td class="fw-bold text-primary">
                            {{ $rule['rule_id'] }}
                        </td>

                        <td class="text-start">

                        @foreach($rule['anteceden'] as $i=>$ant)

                            @if($i>0)
                                <span class="badge bg-label-dark small mx-1">AND</span>
                            @endif

                            <span class="badge bg-label-primary px-2">
                                {{ $ant['kriteria'] }} = {{ $ant['himpunan'] }}
                            </span>

                        @endforeach

                        </td>

                        <td>
                            <span class="badge bg-success">
                                {{ number_format($rule['alpha'],4) }}
                            </span>
                        </td>

                        <td>
                            {{ number_format($rule['z_rule'],4) }}
                        </td>

                        <td class="fw-bold text-primary">
                            {{ number_format($rule['alpha_x_z'],4) }}
                        </td>

                    </tr>

                    @endforeach

                    </tbody>

                    <tfoot class="table-light fw-bold text-center">

                        <tr>

                            <td colspan="2" class="text-end">
                                Jumlah ($\Sigma$):
                            </td>

                            <td class="text-success">
                                {{ number_format($hasil['sum_alpha'],4) }}
                            </td>

                            <td></td>

                            <td class="text-primary">
                                {{ number_format($hasil['sum_alpha_z'],4) }}
                            </td>

                        </tr>

                    </tfoot>

                </table>

            </div>

        </div>


        {{-- ================================================================ --}}
        {{-- STEP 4: DEFUZZIFIKASI --}}
        {{-- ================================================================ --}}
        <div class="card shadow-sm border-0 mb-4">

            <div class="card-header bg-white border-bottom py-3">

                <h5 class="fw-bold mb-0">
                    <span class="badge bg-danger me-2">Step 4</span>
                    Defuzzifikasi — Hasil Akhir
                </h5>

            </div>

            <div class="card-body">

                <div class="row g-4">

                    <div class="col-md-5">

                        <div class="p-4 bg-light rounded border text-center">

                            <h6 class="text-uppercase fw-bold mb-3 small">
                                Formula Weighted Average
                            </h6>

                            <div class="fs-5">

$$
z^* =
\frac{\sum (\alpha_i z_i)}
{\sum \alpha_i}
=
\frac{ {{ number_format($hasil['sum_alpha_z'],4) }} }
{ {{ number_format($hasil['sum_alpha'],4) }} }
$$

                            </div>

                            <hr>

                            <h4 class="fw-bold text-success mb-0">

$$
z^* = {{ number_format($hasil['z'],4) }}
$$

                            </h4>

                        </div>

                    </div>


                    <div class="col-md-7">

                        <div class="p-3 border rounded border-2
                            {{ $hasil['status_kelayakan']=='Layak'
                                ? 'border-success bg-label-success'
                                : 'border-danger bg-label-danger' }}">

                            <div class="d-flex justify-content-between align-items-center">

                                <div>

                                    <small class="fw-bold text-muted d-block mb-1">
                                        KESIMPULAN ANALISIS
                                    </small>

                                    <h3 class="fw-bold mb-1
                                        {{ $hasil['status_kelayakan']=='Layak'
                                            ? 'text-success'
                                            : 'text-danger' }}">

                                        {{ $hasil['skala_prioritas'] }}

                                    </h3>

                                    <p class="mb-0 italic">
                                        {{ $hasil['interpretasi'] }}
                                    </p>

                                </div>

                                <div class="text-center">

                                    <div class="display-6 fw-bold mb-0">
                                        {{ number_format($hasil['skor_akhir'],2) }}%
                                    </div>

                                    <small class="fw-bold text-uppercase">
                                        Skor Kelayakan
                                    </small>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>
</div>


<style>

mjx-container{
    margin:10px 0 !important;
}

.bg-label-success{
    background:#e8fadf!important;
    color:#71dd37!important;
}

.bg-label-primary{
    background:#e7e7ff!important;
    color:#696cff!important;
}

.italic{
    font-style:italic;
}

@media print{

.no-print,.btn,.layout-navbar,.layout-menu{
display:none!important;
}

.content-wrapper{
margin:0!important;
padding:0!important;
}

.card{
border:1px solid #eee!important;
box-shadow:none!important;
page-break-inside:avoid;
}

}

</style>

@endsection


@push('scripts')

<script>
window.MathJax = {
tex: {
inlineMath: [['$', '$'], ['\\(', '\\)']],
displayMath: [['$$', '$$'], ['\\[', '\\]']]
},
svg: { fontCache: 'global' }
};
</script>

<script async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-svg.js"></script>

@endpush
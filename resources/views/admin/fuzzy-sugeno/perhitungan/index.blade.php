@extends('admin.layouts.app')

@section('title', 'Perhitungan Fuzzy Sugeno')

@section('content')
<div class="content-wrapper">
    <div class="container-xxl container-p-y">
        
        <div class="card mb-4 border-0 shadow-sm">
            <div class="card-body d-flex justify-content-between align-items-center py-3">
                <div>
                    <h4 class="fw-bold mb-1"><i class="bx bx-spreadsheet me-2 text-primary"></i>Audit Transparansi Perhitungan Fuzzy</h4>
                    <p class="text-muted mb-0 small">Menampilkan proses matematis Sugeno Orde Nol untuk Alternatif: <strong>{{ $cpcl->nama_kelompok }}</strong></p>
                </div>
                <a href="{{ route('admin.cpcl.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bx bx-arrow-back me-1"></i> Kembali ke Daftar
                </a>
            </div>
        </div>

        <div id="loadingStepper" class="card mb-4 border-0 shadow-sm border-start border-primary border-5">
            <div class="card-body">
                <h6 class="fw-bold mb-3" id="stepperTitle">
                    <i class="bx bx-loader-alt bx-spin me-2 text-primary"></i>Engine Fuzzy Sugeno sedang memproses...
                </h6>
                <div class="stepper-process">
                    <div class="step mb-2" id="step1"><i class="bx bx-circle me-2"></i>Tahap 1: Fuzzifikasi (Transformasi Input ke Matriks Keanggotaan)</div>
                    <div class="step mb-2" id="step2"><i class="bx bx-circle me-2"></i>Tahap 2: Inferensi Fuzzy (Aplikasi Operator MIN / Firing Strength)</div>
                    <div class="step mb-0" id="step3"><i class="bx bx-circle me-2"></i>Tahap 3: Defuzzifikasi (Perhitungan Skor Akhir & Penentuan Layak)</div>
                </div>
            </div>
        </div>

        <div id="auditArea" class="d-none animate__animated animate__fadeIn">
            <div class="row">
                <div class="col-lg-7 mb-4">
                    <div class="card shadow-none border h-100">
                        <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                            <span class="fw-bold small text-uppercase">Visualisasi Kurva (C1 - Luas Lahan)</span>
                            <span class="badge bg-primary">Input: {{ $cpcl->luas_lahan }} Ha</span>
                        </div>
                        <div class="card-body pt-3">
                            <div style="height: 300px;">
                                <canvas id="fuzzyChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5 mb-4">
                    <div class="card shadow-none border mb-3">
                        <div class="card-header bg-label-primary py-2 small fw-bold text-uppercase">Langkah 1: Fuzzifikasi & Matriks &mu;</div>
                        <div class="card-body py-3">
                            <div id="logFuzzifikasi" class="small">
                                </div>
                        </div>
                    </div>

                    <div class="card shadow-none border">
                        <div class="card-header bg-label-warning py-2 small fw-bold text-uppercase">Langkah 2: Inferensi Fuzzy (Sugeno Orde-0)</div>
                        <div class="card-body py-3">
                            <div id="logInferensi" class="small font-monospace">
                                </div>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="card border-0 bg-primary text-white shadow-lg">
                        <div class="card-body row align-items-center text-center text-md-start">
                            <div class="col-md-4 border-end border-white border-opacity-25 py-3">
                                <span class="small opacity-75 text-uppercase">Skor Kelayakan (Z)</span>
                                <h1 class="display-3 fw-bold text-white mb-0" id="finalZ">0.00</h1>
                            </div>
                            <div class="col-md-8 p-4">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="bx bx-award fs-2 me-2"></i>
                                    <h3 class="fw-bold text-white mb-0" id="finalStatus">-</h3>
                                </div>
                                <p class="mb-0 opacity-75" id="finalKeterangan">Berdasarkan perhitungan sistem pakar menggunakan metode Sugeno Orde Nol.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<audio id="stepSound" src="https://assets.mixkit.co/active_storage/sfx/2568/2568-preview.mp3"></audio>
<audio id="successSound" src="https://assets.mixkit.co/active_storage/sfx/1435/1435-preview.mp3"></audio>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

<script>
    let myChart;

    document.addEventListener('DOMContentLoaded', function() {
        // Jalankan simulasi audit bertahap
        runAuditSimulation();
    });

    async function runAuditSimulation() {
        const delay = (ms) => new Promise(res => setTimeout(res, ms));
        const stepSound = document.getElementById('stepSound');
        const successSound = document.getElementById('successSound');

        // TAHAP 1: FUZZIFIKASI
        updateStepUI('step1', 'active');
        await delay(2000); // Jeda agar admin bisa menjelaskan
        renderFuzzifikasi();
        updateStepUI('step1', 'completed');
        stepSound.play();

        // TAHAP 2: INFERENSI
        updateStepUI('step2', 'active');
        await delay(2000);
        renderInferensi();
        updateStepUI('step2', 'completed');
        stepSound.play();

        // TAHAP 3: DEFUZZIFIKASI
        updateStepUI('step3', 'active');
        await delay(1500);
        renderFinal();
        updateStepUI('step3', 'completed');
        successSound.play();

        // TAMPILKAN AREA AUDIT
        await delay(800);
        document.getElementById('loadingStepper').classList.add('animate__animated', 'animate__fadeOutUp');
        setTimeout(() => {
            document.getElementById('loadingStepper').classList.add('d-none');
            document.getElementById('auditArea').classList.remove('d-none');
            renderChart({{ $cpcl->luas_lahan }});
        }, 500);
    }

    function updateStepUI(stepId, status) {
        const el = document.getElementById(stepId);
        if(status === 'active') {
            el.classList.add('active', 'text-primary', 'fw-bold');
            el.querySelector('i').className = 'bx bx-loader-alt bx-spin me-2';
        } else {
            el.classList.remove('active', 'text-primary');
            el.classList.add('completed', 'text-success');
            el.querySelector('i').className = 'bx bxs-check-circle me-2';
        }
    }

    function renderFuzzifikasi() {
        const data = [
            @foreach($cpcl->penilaian as $p)
            { kode: '{{ $p->kriteria->kode_kriteria }}', label: '{{ $p->nilai }}', mu: {{ $p->nilai_input }} },
            @endforeach
        ];

        let html = `<table class="table table-sm table-borderless mb-0">`;
        data.forEach(item => {
            html += `<tr>
                <td>&mu; ${item.kode} (${item.label})</td>
                <td>:</td>
                <td class="fw-bold text-primary">${item.mu.toFixed(2)}</td>
            </tr>`;
        });
        html += `</table>`;
        document.getElementById('logFuzzifikasi').innerHTML = html;
    }

    function renderInferensi() {
        const alpha = {{ $cpcl->hasilFuzzy->nilai_alpha ?? 0 }};
        const listMu = [@foreach($cpcl->penilaian as $p) {{ $p->nilai_input }}, @endforeach];
        
        document.getElementById('logInferensi').innerHTML = `
            <div class="p-2 bg-light rounded border">
                <p class="mb-1"><strong>Firing Strength (&alpha;):</strong></p>
                &alpha;-predikat = Min(${listMu.join(', ')}) <br>
                &alpha;-predikat = <span class="text-danger fw-bold">${alpha}</span>
                <hr class="my-2">
                <p class="mb-0"><strong>Konsekuen (Ki):</strong><br>
                Ki = (${listMu.join(' + ')}) / ${listMu.length} <br>
                Ki = <span class="text-primary fw-bold">{{ $cpcl->hasilFuzzy->nilai_z ?? 0 }}</span></p>
            </div>
        `;
    }

    function renderFinal() {
        const z = {{ $cpcl->hasilFuzzy->nilai_z ?? 0 }};
        const skor = {{ $cpcl->hasilFuzzy->skor_akhir ?? 0 }};
        const status = "{{ $cpcl->hasilFuzzy->status_kelayakan }}";

        document.getElementById('finalZ').innerText = z.toFixed(2);
        document.getElementById('finalStatus').innerText = (status === 'Layak') ? 'LAYAK MENERIMA BANTUAN' : 'TIDAK LAYAK / CADANGAN';
        document.getElementById('finalKeterangan').innerText = `Hasil akhir perankingan menunjukkan skor ${skor}%. Keputusan ini didasarkan pada perhitungan bobot kriteria terverifikasi.`;
    }

    function renderChart(inputValue) {
        const ctx = document.getElementById('fuzzyChart').getContext('2d');
        if(myChart) myChart.destroy();

        myChart = new Chart(ctx, {
            type: 'scatter',
            data: {
                datasets: [
                    { label: 'Sempit', data: [{x:0,y:1}, {x:0.25,y:0}], showLine: true, borderColor: '#ff3e1d', backgroundColor: 'rgba(255, 62, 29, 0.1)', fill: true, tension: 0 },
                    { label: 'Sedang', data: [{x:0.1,y:0}, {x:0.4,y:1}, {x:0.7,y:0}], showLine: true, borderColor: '#ffab00', backgroundColor: 'rgba(255, 171, 0, 0.1)', fill: true, tension: 0 },
                    { label: 'Luas', data: [{x:0.6,y:0}, {x:1.0,y:1}, {x:1.5,y:1}], showLine: true, borderColor: '#71dd37', backgroundColor: 'rgba(113, 221, 55, 0.1)', fill: true, tension: 0 },
                    { label: 'Posisi Input Admin', data: [{x:inputValue, y:0}, {x:inputValue, y:1}], showLine: true, borderDash: [5, 5], borderColor: '#000', pointRadius: 5, pointBackgroundColor: '#000' }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { min: 0, max: 1.1, title: { display: true, text: 'Derajat Keanggotaan (mu)' } },
                    x: { min: 0, max: 1.5, title: { display: true, text: 'Luas Lahan (Ha)' } }
                }
            }
        });
    }
</script>

<style>
    .stepper-process .step { color: #a1acb8; }
    .bg-label-primary { background-color: #e7e7ff; color: #696cff; }
    .bg-label-warning { background-color: #fff2d6; color: #ffab00; }
    .completed { transition: all 0.5s ease; }
</style>
@endpush
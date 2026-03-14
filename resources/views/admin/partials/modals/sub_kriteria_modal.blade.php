{{-- ===================== MODAL TAMBAH ===================== --}}
<div class="modal fade" id="modalTambahSubKriteria" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <form action="{{ route('admin.sub-kriteria.store') }}" method="POST">
                @csrf
                <div class="modal-header bg-primary">
                    <h5 class="modal-title text-white">Tambah Sub-Kriteria (Himpunan Fuzzy)</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    {{-- TUTORIAL NOTES MODERN --}}
                    <div class="card bg-label-primary border-0 mb-4 shadow-none">
                        <div class="card-body">
                            <h6 class="fw-bold d-flex align-items-center mb-3">
                                <i class="bx bx-book-reader fs-4 me-2"></i> Tutorial Input Parameter
                            </h6>
                            <div class="row g-3 text-dark">
                                <div class="col-md-6">
                                    <div class="p-3 rounded bg-white shadow-sm h-100 border">
                                        <div class="fw-bold text-primary mb-2 small text-uppercase">1. Kriteria Kontinu (Angka)</div>
                                        <p class="small mb-2">Tentukan rentang nilai menggunakan parameter <span class="badge bg-label-dark">a, b, c, d</span> :</p>
                                        <ul class="small ps-3 mb-0 text-muted">
                                            <li><span class="fw-bold">Bahu Kiri:</span> Isi <span class="text-primary">c & d</span> (cth: Pendek/Kecil).</li>
                                            <li><span class="fw-bold">Trapesium:</span> Isi <span class="text-primary">semua</span> (cth: Sedang/Menengah).</li>
                                            <li><span class="fw-bold">Bahu Kanan:</span> Isi <span class="text-primary">a & b</span> (cth: Tinggi/Luas).</li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="p-3 rounded bg-white shadow-sm h-100 border">
                                        <div class="fw-bold text-warning mb-2 small text-uppercase">2. Kriteria Diskrit (Kategori)</div>
                                        <p class="small mb-2">Untuk data pilihan (Dropdown), parameter koordinat tidak digunakan:</p>
                                        <ul class="small ps-3 mb-0 text-muted">
                                            <li>Cukup isi <span class="fw-bold">Nama Himpunan</span> (cth: Milik Sendiri).</li>
                                            <li>Isi <span class="fw-bold">Nilai Konsekuen (k)</span> sebagai bobot skor (0 - 1.0).</li>
                                            <li>Titik koordinat <span class="text-danger">a, b, c, d</span> otomatis dinonaktifkan.</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Pilih Kriteria Induk</label>
                        <select name="kriteria_id" class="form-select select-kriteria border-primary" required onchange="ubahJenisKriteria(this, 'wrapper-sub-tambah')">
                            <option value="" data-jenis="">-- Klik untuk memilih kriteria --</option>
                            @foreach($data as $k)
                                <option value="{{ $k->id }}" data-jenis="{{ $k->jenis_kriteria }}">{{ $k->nama_kriteria }} ({{ ucfirst($k->jenis_kriteria) }})</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- AREA INPUT DINAMIS --}}
                    <div id="wrapper-sub-tambah"></div>

                    <button type="button" class="btn btn-sm btn-outline-primary mt-2 d-none shadow-sm" id="btn-tambah-baris-add" onclick="tambahBaris('wrapper-sub-tambah')">
                        <i class="bx bx-plus-circle me-1"></i> Tambah Baris Himpunan
                    </button>
                </div>

                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary shadow">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ===================== MODAL EDIT ===================== --}}
<div class="modal fade" id="modalEditSubKriteria" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <form id="formEditSubKriteria" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header bg-info">
                    <h5 class="modal-title text-white">Update Sub-Kriteria</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <input type="hidden" name="kriteria_id" id="edit_kriteria_id">
                    
                    <div class="d-flex align-items-center p-3 mb-4 bg-label-warning rounded border border-warning">
                        <i class="bx bx-bulb fs-3 me-3 text-warning"></i>
                        <div>
                            <h6 class="mb-1 fw-bold">Tips Transisi Mulus (Overlap):</h6>
                            <p class="small mb-0 text-dark">Gunakan nilai <span class="fw-bold">Batas d</span> kategori lama sebagai <span class="fw-bold">Batas a</span> kategori selanjutnya.</p>
                        </div>
                    </div>

                    <div class="alert alert-info py-2" id="info_kriteria_edit"></div>

                    <div id="wrapper-sub-edit"></div>

                    <button type="button" class="btn btn-sm btn-outline-primary mt-2 shadow-sm" onclick="tambahBaris('wrapper-sub-edit')">
                        <i class="bx bx-plus-circle me-1"></i> Tambah Baris Himpunan
                    </button>
                </div>

                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-info text-white shadow">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<style>
    .form-label-human { font-size: 0.75rem; font-weight: 600; color: #566a7f; margin-bottom: 0.25rem; display: block; }
    .wrap-input-batas { padding: 0 5px; }
    .item-sub { transition: all 0.2s ease; }
    .bg-label-primary { background-color: #f0f2ff !important; color: #696cff !important; }
</style>

<script>
let currentJenisKriteria = '';

function ubahJenisKriteria(selectElement, wrapperId) {
    const selectedOption = selectElement.options[selectElement.selectedIndex];
    currentJenisKriteria = selectedOption.getAttribute('data-jenis');
    
    const wrapper = document.getElementById(wrapperId);
    const btnTambah = document.getElementById('btn-tambah-baris-add');
    
    wrapper.innerHTML = ''; 
    
    if(currentJenisKriteria) {
        if(btnTambah) btnTambah.classList.remove('d-none');
        tambahBaris(wrapperId); 
    } else {
        if(btnTambah) btnTambah.classList.add('d-none');
    }
}

function tambahBaris(wrapperId, subData = null) {
    const wrapper = document.getElementById(wrapperId);
    wrapper.insertAdjacentHTML('beforeend', templateBaris(currentJenisKriteria, subData));
    
    const lastRow = wrapper.lastElementChild;
    const selectKurva = lastRow.querySelector('.select-kurva');
    sesuaikanInputKurva(selectKurva);
}

function templateBaris(jenisKriteria, sub = null) {
    const nama = sub?.nama_sub_kriteria || '';
    const tipe = sub?.tipe_kurva || (jenisKriteria === 'diskrit' ? 'diskrit' : 'trapesium');
    const b1 = sub?.batas_bawah ?? '';
    const b2 = sub?.batas_tengah_1 ?? '';
    const b3 = sub?.batas_tengah_2 ?? '';
    const b4 = sub?.batas_atas ?? '';
    const n_konsekuen = sub?.nilai_konsekuen ?? '';

    let opsiKurva = (jenisKriteria === 'kontinu') 
        ? `<option value="bahu_kiri" ${tipe === 'bahu_kiri' ? 'selected' : ''}>Bahu Kiri</option>
           <option value="trapesium" ${tipe === 'trapesium' ? 'selected' : ''}>Trapesium / Segitiga</option>
           <option value="bahu_kanan" ${tipe === 'bahu_kanan' ? 'selected' : ''}>Bahu Kanan</option>`
        : `<option value="diskrit" selected>Diskrit (Tetap)</option>`;

    return `
    <div class="row g-2 mb-3 pb-3 border-bottom item-sub align-items-end bg-white p-2 rounded shadow-sm">
        <div class="col-md-2">
            <label class="form-label-human text-dark">Nama Himpunan</label>
            <input type="text" name="nama_sub_kriteria[]" value="${nama}" class="form-control form-control-sm" placeholder="Sempit/Sedang" required>
        </div>
        <div class="col-md-2">
            <label class="form-label-human text-dark">Bentuk Kurva</label>
            <select name="tipe_kurva[]" class="form-select form-select-sm select-kurva" onchange="sesuaikanInputKurva(this)" required>
                ${opsiKurva}
            </select>
        </div>
        <div class="col-md-7 wrapper-kontinu d-flex">
            <div class="flex-fill wrap-input-batas b-a">
                <label class="form-label-human lbl-b1 text-primary">Batas a</label>
                <input type="number" step="0.01" name="batas_bawah[]" value="${b1}" class="form-control form-control-sm">
            </div>
            <div class="flex-fill wrap-input-batas b-b">
                <label class="form-label-human lbl-b2 text-primary">Batas b</label>
                <input type="number" step="0.01" name="batas_tengah_1[]" value="${b2}" class="form-control form-control-sm">
            </div>
            <div class="flex-fill wrap-input-batas b-c">
                <label class="form-label-human lbl-b3 text-primary">Batas c</label>
                <input type="number" step="0.01" name="batas_tengah_2[]" value="${b3}" class="form-control form-control-sm">
            </div>
            <div class="flex-fill wrap-input-batas b-d">
                <label class="form-label-human lbl-b4 text-primary">Batas d</label>
                <input type="number" step="0.01" name="batas_atas[]" value="${b4}" class="form-control form-control-sm">
            </div>
        </div>
        <div class="col-md-7 wrapper-diskrit d-none">
            <div class="col-md-6">
                <label class="form-label-human text-success">Bobot Skor (k) [0 - 1.0]</label>
                <input type="number" step="0.01" max="1" min="0" name="nilai_diskrit[]" value="${n_konsekuen}" class="form-control form-control-sm" placeholder="Contoh: 0.80">
            </div>
        </div>
        <div class="col-md-1">
            <button type="button" class="btn btn-sm btn-outline-danger btn-hapus-baris w-100" title="Hapus"><i class="bx bx-trash"></i></button>
        </div>
    </div>`;
}

function sesuaikanInputKurva(selectElement) {
    const row = selectElement.closest('.item-sub');
    const tipe = selectElement.value;
    const wrapKontinu = row.querySelector('.wrapper-kontinu');
    const wrapDiskrit = row.querySelector('.wrapper-diskrit');
    
    if(tipe === 'diskrit') {
        wrapKontinu.classList.add('d-none');
        wrapDiskrit.classList.remove('d-none');
        return;
    } else {
        wrapKontinu.classList.remove('d-none');
        wrapDiskrit.classList.add('d-none');
    }

    const bA = row.querySelector('.b-a'), bB = row.querySelector('.b-b');
    const bC = row.querySelector('.b-c'), bD = row.querySelector('.b-d');
    const lblA = row.querySelector('.lbl-b1'), lblB = row.querySelector('.lbl-b2');
    const lblC = row.querySelector('.lbl-b3'), lblD = row.querySelector('.lbl-b4');

    [bA, bB, bC, bD].forEach(el => el.style.display = 'block');
    lblA.innerText = 'Batas a'; lblB.innerText = 'Batas b';
    lblC.innerText = 'Batas c'; lblD.innerText = 'Batas d';

    if(tipe === 'bahu_kiri') {
        bA.style.display = 'none'; bB.style.display = 'none';
        lblC.innerText = 'Mulai Turun (c)'; lblD.innerText = 'Titik Habis (d)';
    } else if (tipe === 'bahu_kanan') {
        bC.style.display = 'none'; bD.style.display = 'none';
        lblA.innerText = 'Mulai Naik (a)'; lblB.innerText = 'Titik Full (b)';
    }
}

function isiFormEditSub(kriteriaId, namaKriteria, jenisKriteria, subKriteriaJSON) {
    currentJenisKriteria = jenisKriteria;
    const form = document.getElementById('formEditSubKriteria');
    const wrapper = document.getElementById('wrapper-sub-edit');
    form.action = "{{ url('admin/sub-kriteria') }}/" + kriteriaId;
    document.getElementById('edit_kriteria_id').value = kriteriaId;
    document.getElementById('info_kriteria_edit').innerHTML = `Mengedit: <strong>${namaKriteria}</strong>`;
    wrapper.innerHTML = '';
    const subKriteriaData = typeof subKriteriaJSON === 'string' ? JSON.parse(subKriteriaJSON) : subKriteriaJSON;
    if (subKriteriaData?.length > 0) {
        subKriteriaData.forEach(sub => tambahBaris('wrapper-sub-edit', sub));
    } else {
        tambahBaris('wrapper-sub-edit');
    }
}

document.addEventListener('click', function(e){
    if(e.target.closest('.btn-hapus-baris')){
        const baris = e.target.closest('.item-sub');
        if(baris.parentElement.children.length > 1) baris.remove();
    }
});
</script>
@endpush
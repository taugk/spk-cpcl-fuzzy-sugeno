{{-- ===================== MODAL TAMBAH ===================== --}}
<div class="modal fade" id="modalTambahSubKriteria" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <form action="{{ route('admin.sub-kriteria.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5>Tambah Sub-Kriteria</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label>Kriteria Induk</label>
                        <select name="kriteria_id" class="form-select select-kriteria" required onchange="ubahJenisKriteria(this, 'wrapper-sub-tambah')">
                            <option value="" data-jenis="">-- Pilih Kriteria --</option>
                            @foreach($data as $k)
                                <option value="{{ $k->id }}" data-jenis="{{ $k->jenis_kriteria }}">{{ $k->nama_kriteria }} ({{ ucfirst($k->jenis_kriteria) }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div id="wrapper-sub-tambah">
                        </div>

                    <button type="button" class="btn btn-sm btn-outline-primary mt-2 d-none" id="btn-tambah-baris-add" onclick="tambahBaris('wrapper-sub-tambah')">
                        <i class="bx bx-plus"></i> Tambah Sub Kategori Lainnya
                    </button>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
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
                <div class="modal-header">
                    <h5>Edit Sub-Kriteria</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <input type="hidden" name="kriteria_id" id="edit_kriteria_id">
                    
                    <div class="alert alert-info" id="info_kriteria_edit"></div>

                    <div id="wrapper-sub-edit"></div>

                    <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="tambahBaris('wrapper-sub-edit')">
                        <i class="bx bx-plus"></i> Tambah Sub Kategori Lainnya
                    </button>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<style>
    .form-label-human {
        font-size: 0.75rem;
        font-weight: 600;
        color: #566a7f;
        margin-bottom: 0.25rem;
        display: block;
        min-height: 20px; 
    }
    .wrap-input-batas {
        padding: 0 5px;
    }
</style>

<script>
let currentJenisKriteria = '';

function ubahJenisKriteria(selectElement, wrapperId) {
    currentJenisKriteria = selectElement.options[selectElement.selectedIndex].getAttribute('data-jenis');
    const wrapper = document.getElementById(wrapperId);
    const btnTambah = document.getElementById('btn-tambah-baris-add');
    
    wrapper.innerHTML = ''; 
    
    if(currentJenisKriteria) {
        btnTambah.classList.remove('d-none');
        tambahBaris(wrapperId); 
    } else {
        btnTambah.classList.add('d-none');
    }
}

function tambahBaris(wrapperId, subData = null) {
    const wrapper = document.getElementById(wrapperId);
    wrapper.insertAdjacentHTML('beforeend', templateBaris(currentJenisKriteria, subData));
    
    const selects = wrapper.querySelectorAll('.select-kurva');
    sesuaikanInputKurva(selects[selects.length - 1]);
}

function templateBaris(jenisKriteria, sub = null) {
    const nama = sub?.nama_sub_kriteria || '';
    const tipe = sub?.tipe_kurva || (jenisKriteria === 'diskrit' ? 'diskrit' : 'trapesium');
    const b1 = sub?.batas_bawah != null ? sub.batas_bawah : '';
    const b2 = sub?.batas_tengah_1 != null ? sub.batas_tengah_1 : '';
    const b3 = sub?.batas_tengah_2 != null ? sub.batas_tengah_2 : '';
    const b4 = sub?.batas_atas != null ? sub.batas_atas : '';
    const n_diskrit = sub?.nilai_diskrit != null ? sub.nilai_diskrit : '';

    // MENGGUNAKAN ISTILAH "BAHU"
    let opsiKurva = '';
    if (jenisKriteria === 'kontinu') {
        opsiKurva = `
            <option value="" disabled selected>-- Pilih Bentuk Kurva --</option>
            <option value="bahu_kiri" ${tipe === 'bahu_kiri' ? 'selected' : ''}>Bahu Kiri</option>
            <option value="trapesium" ${tipe === 'trapesium' ? 'selected' : ''}>Trapesium / Segitiga</option>
            <option value="bahu_kanan" ${tipe === 'bahu_kanan' ? 'selected' : ''}>Bahu Kanan</option>
        `;
    } else {
        opsiKurva = `<option value="diskrit" selected>Diskrit (Tetap)</option>`;
    }

    return `
    <div class="row g-2 mb-3 pb-3 border-bottom item-sub align-items-end">
        
        <div class="col-md-2">
            <label class="form-label-human">Nama Himpunan</label>
            <input type="text" name="nama_sub_kriteria[]" value="${nama}" class="form-control" placeholder="Cth: Sempit / Luas" required>
        </div>
        
        <div class="col-md-2">
            <label class="form-label-human">Bentuk Kurva</label>
            <select name="tipe_kurva[]" class="form-select select-kurva" onchange="sesuaikanInputKurva(this)" required>
                ${opsiKurva}
            </select>
        </div>
        
        <div class="col-md-7 wrapper-kontinu d-flex ${jenisKriteria === 'diskrit' ? 'd-none' : ''}">
            
            <div class="flex-fill wrap-input-batas wrap-b1">
                <label class="form-label-human lbl-b1 text-primary">Batas a</label>
                <input type="number" step="0.01" name="batas_bawah[]" value="${b1}" class="form-control input-b1">
            </div>

            <div class="flex-fill wrap-input-batas wrap-b2">
                <label class="form-label-human lbl-b2 text-primary">Batas b</label>
                <input type="number" step="0.01" name="batas_tengah_1[]" value="${b2}" class="form-control input-b2">
            </div>

            <div class="flex-fill wrap-input-batas wrap-b3">
                <label class="form-label-human lbl-b3 text-primary">Batas c</label>
                <input type="number" step="0.01" name="batas_tengah_2[]" value="${b3}" class="form-control input-b3">
            </div>

            <div class="flex-fill wrap-input-batas wrap-b4">
                <label class="form-label-human lbl-b4 text-primary">Batas d</label>
                <input type="number" step="0.01" name="batas_atas[]" value="${b4}" class="form-control input-b4">
            </div>

        </div>

        <div class="col-md-7 wrapper-diskrit ${jenisKriteria === 'kontinu' ? 'd-none' : ''}">
            <div class="col-md-4">
                <label class="form-label-human text-success">Nilai Keanggotaan (μ)</label>
                <input type="number" step="0.01" max="1" min="0" name="nilai_diskrit[]" value="${n_diskrit}" class="form-control" placeholder="Contoh: 0.6">
            </div>
        </div>

        <div class="col-md-1">
            <button type="button" class="btn btn-danger btn-hapus-baris w-100" title="Hapus Baris"><i class="bx bx-trash"></i></button>
        </div>
    </div>`;
}

function sesuaikanInputKurva(selectElement) {
    const row = selectElement.closest('.item-sub');
    const tipe = selectElement.value;
    
    if(tipe === 'diskrit') return; 

    // Ambil Wrapper dan Element
    const wrapB1 = row.querySelector('.wrap-b1');
    const wrapB2 = row.querySelector('.wrap-b2');
    const wrapB3 = row.querySelector('.wrap-b3');
    const wrapB4 = row.querySelector('.wrap-b4');

    const lblB1 = row.querySelector('.lbl-b1');
    const lblB2 = row.querySelector('.lbl-b2');
    const lblB3 = row.querySelector('.lbl-b3');
    const lblB4 = row.querySelector('.lbl-b4');

    const inB1 = row.querySelector('.input-b1');
    const inB2 = row.querySelector('.input-b2');
    const inB3 = row.querySelector('.input-b3');
    const inB4 = row.querySelector('.input-b4');

    // RESET: Tampilkan 4 kotak untuk Trapesium/Segitiga
    wrapB1.style.display = 'block'; lblB1.innerText = 'Batas a (Bawah)';
    wrapB2.style.display = 'block'; lblB2.innerText = 'Batas b (Tengah 1)';
    wrapB3.style.display = 'block'; lblB3.innerText = 'Batas c (Tengah 2)';
    wrapB4.style.display = 'block'; lblB4.innerText = 'Batas d (Atas)';

    // LOGIKA PENAMAAN "BAHU"
    if(tipe === 'bahu_kiri') {
        wrapB1.style.display = 'none'; 
        wrapB2.style.display = 'none'; 
        
        wrapB3.style.display = 'block'; 
        lblB3.innerText = 'Nilai Bahu A';
        inB3.placeholder = 'Titik Mulai Turun';
        
        wrapB4.style.display = 'block'; 
        lblB4.innerText = 'Nilai Bahu B';
        inB4.placeholder = 'Titik Jadi Nol';
    } 
    else if (tipe === 'bahu_kanan') {
        wrapB1.style.display = 'block'; 
        lblB1.innerText = 'Nilai Bahu A';
        inB1.placeholder = 'Titik Awal Naik';
        
        wrapB2.style.display = 'block'; 
        lblB2.innerText = 'Nilai Bahu B';
        inB2.placeholder = 'Titik Mulai Datar';
        
        wrapB3.style.display = 'none'; 
        wrapB4.style.display = 'none'; 
    }
}

function isiFormEditSub(kriteriaId, namaKriteria, jenisKriteria, subKriteriaJSON) {
    currentJenisKriteria = jenisKriteria;
    const form = document.getElementById('formEditSubKriteria');
    const wrapper = document.getElementById('wrapper-sub-edit');
    
    form.action = '/admin/sub-kriteria/' + kriteriaId;
    document.getElementById('edit_kriteria_id').value = kriteriaId;
    document.getElementById('info_kriteria_edit').innerHTML = `Mengedit Sub-Kriteria untuk Kriteria: <strong>${namaKriteria}</strong> (${jenisKriteria})`;

    wrapper.innerHTML = '';

    const subKriteriaData = typeof subKriteriaJSON === 'string' ? JSON.parse(subKriteriaJSON) : subKriteriaJSON;

    if (subKriteriaData && subKriteriaData.length > 0) {
        subKriteriaData.forEach(sub => {
            tambahBaris('wrapper-sub-edit', sub);
        });
    } else {
        tambahBaris('wrapper-sub-edit');
    }
}

document.addEventListener('click', function(e){
    if(e.target.closest('.btn-hapus-baris')){
        const baris = e.target.closest('.item-sub');
        if(baris.parentElement.children.length > 1) {
            baris.remove();
        } else {
            alert('Minimal harus ada 1 himpunan (sub-kriteria)!');
        }
    }
});
</script>
@endpush
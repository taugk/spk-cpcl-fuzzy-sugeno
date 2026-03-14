{{-- ===================== MODAL TAMBAH ===================== --}}
<div class="modal fade" id="modalTambahSubKriteria" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <form action="{{ route('admin.sub-kriteria.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Sub-Kriteria</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Kriteria Induk</label>
                        <select name="kriteria_id" class="form-select select-kriteria" required onchange="ubahJenisKriteria(this, 'wrapper-sub-tambah')">
                            <option value="" data-jenis="">-- Pilih Kriteria --</option>
                            @foreach($data as $k)
                                <option value="{{ $k->id }}" data-jenis="{{ $k->jenis_kriteria }}">{{ $k->nama_kriteria }} ({{ ucfirst($k->jenis_kriteria) }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div id="wrapper-sub-tambah">
                        {{-- Baris akan muncul di sini via JS --}}
                    </div>

                    <button type="button" class="btn btn-sm btn-outline-primary mt-2 d-none" id="btn-tambah-baris-add" onclick="tambahBaris('wrapper-sub-tambah')">
                        <i class="bx bx-plus"></i> Tambah Baris Himpunan
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
                    <h5 class="modal-title">Edit Sub-Kriteria</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <input type="hidden" name="kriteria_id" id="edit_kriteria_id">
                    
                    <div class="alert alert-info" id="info_kriteria_edit"></div>

                    <div id="wrapper-sub-edit"></div>

                    <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="tambahBaris('wrapper-sub-edit')">
                        <i class="bx bx-plus"></i> Tambah Baris Himpunan
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
    .item-sub {
        transition: all 0.2s ease;
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
        if(btnTambah) btnTambah.classList.remove('d-none');
        tambahBaris(wrapperId); 
    } else {
        if(btnTambah) btnTambah.classList.add('d-none');
    }
}

function tambahBaris(wrapperId, subData = null) {
    const wrapper = document.getElementById(wrapperId);
    wrapper.insertAdjacentHTML('beforeend', templateBaris(currentJenisKriteria, subData));
    
    // Ambil baris terakhir yang baru saja ditambah
    const lastRow = wrapper.lastElementChild;
    const selectKurva = lastRow.querySelector('.select-kurva');
    
    // Jalankan penyesuaian tampilan awal
    sesuaikanInputKurva(selectKurva);
}

function templateBaris(jenisKriteria, sub = null) {
    const nama = sub?.nama_sub_kriteria || '';
    const tipe = sub?.tipe_kurva || (jenisKriteria === 'diskrit' ? 'diskrit' : 'trapesium');
    
    // Mapping value dari database (migration) ke variabel JS
    const b1 = sub?.batas_bawah ?? '';
    const b2 = sub?.batas_tengah_1 ?? '';
    const b3 = sub?.batas_tengah_2 ?? '';
    const b4 = sub?.batas_atas ?? '';
    const n_konsekuen = sub?.nilai_konsekuen ?? '';

    let opsiKurva = '';
    if (jenisKriteria === 'kontinu') {
        opsiKurva = `
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
            <input type="text" name="nama_sub_kriteria[]" value="${nama}" class="form-control" placeholder="Cth: Sempit" required>
        </div>
        
        <div class="col-md-2">
            <label class="form-label-human">Bentuk Kurva</label>
            <select name="tipe_kurva[]" class="form-select select-kurva" onchange="sesuaikanInputKurva(this)" required>
                ${opsiKurva}
            </select>
        </div>
        
        <div class="col-md-7 wrapper-kontinu d-flex">
            <div class="flex-fill wrap-input-batas b-a">
                <label class="form-label-human lbl-b1 text-primary">Batas a</label>
                <input type="number" step="0.01" name="batas_bawah[]" value="${b1}" class="form-control">
            </div>
            <div class="flex-fill wrap-input-batas b-b">
                <label class="form-label-human lbl-b2 text-primary">Batas b</label>
                <input type="number" step="0.01" name="batas_tengah_1[]" value="${b2}" class="form-control">
            </div>
            <div class="flex-fill wrap-input-batas b-c">
                <label class="form-label-human lbl-b3 text-primary">Batas c</label>
                <input type="number" step="0.01" name="batas_tengah_2[]" value="${b3}" class="form-control">
            </div>
            <div class="flex-fill wrap-input-batas b-d">
                <label class="form-label-human lbl-b4 text-primary">Batas d</label>
                <input type="number" step="0.01" name="batas_atas[]" value="${b4}" class="form-control">
            </div>
        </div>

        <div class="col-md-7 wrapper-diskrit d-none">
            <div class="col-md-5">
                <label class="form-label-human text-success">Nilai Konsekuen (μ)</label>
                <input type="number" step="0.01" max="1" min="0" name="nilai_diskrit[]" value="${n_konsekuen}" class="form-control" placeholder="0.00 - 1.00">
            </div>
        </div>

        <div class="col-md-1">
            <button type="button" class="btn btn-outline-danger btn-hapus-baris w-100" title="Hapus Baris">
                <i class="bx bx-trash"></i>
            </button>
        </div>
    </div>`;
}

function sesuaikanInputKurva(selectElement) {
    const row = selectElement.closest('.item-sub');
    const tipe = selectElement.value;
    
    const wrapKontinu = row.querySelector('.wrapper-kontinu');
    const wrapDiskrit = row.querySelector('.wrapper-diskrit');
    
    // Tampilan dasar Kontinu vs Diskrit
    if(tipe === 'diskrit') {
        wrapKontinu.classList.add('d-none');
        wrapDiskrit.classList.remove('d-none');
        return;
    } else {
        wrapKontinu.classList.remove('d-none');
        wrapDiskrit.classList.add('d-none');
    }

    // Ambil semua kolom batas
    const bA = row.querySelector('.b-a'), bB = row.querySelector('.b-b');
    const bC = row.querySelector('.b-c'), bD = row.querySelector('.b-d');
    const lblA = row.querySelector('.lbl-b1'), lblB = row.querySelector('.lbl-b2');
    const lblC = row.querySelector('.lbl-b3'), lblD = row.querySelector('.lbl-b4');

    // Reset Default (Trapesium)
    [bA, bB, bC, bD].forEach(el => el.style.display = 'block');
    lblA.innerText = 'Batas a'; lblB.innerText = 'Batas b';
    lblC.innerText = 'Batas c'; lblD.innerText = 'Batas d';

    if(tipe === 'bahu_kiri') {
        bA.style.display = 'none'; 
        bB.style.display = 'none'; 
        lblC.innerText = 'Titik Turun (c)';
        lblD.innerText = 'Titik Nol (d)';
    } 
    else if (tipe === 'bahu_kanan') {
        bC.style.display = 'none'; 
        bD.style.display = 'none'; 
        lblA.innerText = 'Titik Naik (a)';
        lblB.innerText = 'Titik Full (b)';
    }
}

function isiFormEditSub(kriteriaId, namaKriteria, jenisKriteria, subKriteriaJSON) {
    currentJenisKriteria = jenisKriteria;
    const form = document.getElementById('formEditSubKriteria');
    const wrapper = document.getElementById('wrapper-sub-edit');
    
    form.action = "{{ url('admin/sub-kriteria') }}/" + kriteriaId;
    document.getElementById('edit_kriteria_id').value = kriteriaId;
    document.getElementById('info_kriteria_edit').innerHTML = `Mengedit Sub-Kriteria untuk: <strong>${namaKriteria}</strong> (${jenisKriteria})`;

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

// Event delegation untuk tombol hapus
document.addEventListener('click', function(e){
    if(e.target.closest('.btn-hapus-baris')){
        const baris = e.target.closest('.item-sub');
        const container = baris.parentElement;
        if(container.children.length > 1) {
            baris.remove();
        } else {
            alert('Minimal harus ada 1 baris himpunan!');
        }
    }
});
</script>
@endpush
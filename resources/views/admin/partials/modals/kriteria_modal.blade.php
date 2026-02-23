<div class="modal fade" id="modalTambahKriteria" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Kriteria Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('admin.kriteria.store') }}" method="POST" class="form-confirm">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col mb-3">
                            <label for="nama_kriteria" class="form-label">Nama Kriteria</label>
                            <input type="text" name="nama_kriteria" id="nama_kriteria" class="form-control" placeholder="Contoh: Luas Lahan" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col mb-3">
                            <label for="jenis_kriteria" class="form-label">Jenis Kriteria</label>
                            <select name="jenis_kriteria" id="jenis_kriteria" class="form-select" required>
                                <option value="">Pilih Jenis</option>
                                <option value="kontinu">Kontinu</option>
                                <option value="diskrit">Diskrit</option>
                            </select>
                        </div>
                        
                    <div class="alert alert-warning d-flex" style="font-size: 0.85rem;">
                        <i class="bx bx-error me-2 mt-1"></i>
                        <span>Pastikan nama kriteria belum ada sebelumnya untuk menjaga konsistensi data.</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditKriteria" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Kriteria</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formEditKriteria" method="POST" class="form-confirm">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="row">
                        <div class="col mb-3">
                            <label for="edit_nama_kriteria" class="form-label">Nama Kriteria</label>
                            <input type="text" name="nama_kriteria" id="edit_nama_kriteria" class="form-control" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col mb-3">
                            <label for="edit_jenis_kriteria" class="form-label">Jenis Kriteria</label>
                            <select name="jenis_kriteria" id="edit_jenis_kriteria" class="form-select" required>
                                <option value="">Pilih Jenis</option>
                                <option value="kontinu">Kontinu</option>
                                <option value="diskrit">Diskrit</option>
                            </select>
                        </div>
                    </div>
                    
                    <input type="hidden" name="mapping_field" id="edit_mapping_field">
                    
                    <div class="alert alert-info d-flex" style="font-size: 0.85rem;">
                        <i class="bx bx-info-circle me-2 mt-1"></i>
                        <span>Perubahan pada nama kriteria tidak akan mempengaruhi rumus perhitungan Fuzzy yang sudah berjalan.</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>
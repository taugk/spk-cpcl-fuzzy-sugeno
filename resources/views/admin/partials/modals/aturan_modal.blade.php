{{-- ===================== MODAL TAMBAH ATURAN ===================== --}}
<div class="modal fade" id="modalTambahAturan" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">

      <form action="{{ route('admin.rule.store') }}" method="POST">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title">Tambah Aturan Fuzzy Sugeno</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">

          <div class="mb-3">
            <label class="form-label">Kode Rule</label>
            <input type="text" name="kode_rule" class="form-control" placeholder="Contoh: R1" required>
          </div>

          <h6 class="fw-bold text-primary"><i class="bx bx-git-branch"></i> Logika IF (Syarat)</h6>
          <div class="p-3 bg-light border rounded mb-3">
            
            {{-- LOOPING DINAMIS DARI DATABASE --}}
            @foreach($kriteriaList as $kriteria)
            <div class="row mb-2 align-items-center">
              <div class="col-md-4 fw-bold text-secondary">
                {{ $kriteria->kode_kriteria }} - {{ $kriteria->nama_kriteria }}
              </div>
              <div class="col-md-8">
                <select name="detail[{{ $kriteria->id }}]" class="form-select" required>
                  <option value="">-- Pilih Himpunan --</option>
                  @foreach($kriteria->subKriteria as $sub)
                    <option value="{{ $sub->id }}">{{ $sub->nama_sub_kriteria }}</option>
                  @endforeach
                </select>
              </div>
            </div>
            @endforeach

          </div>

          <h6 class="fw-bold text-success"><i class="bx bx-check-double"></i> Konsekuen (THEN / Hasil Sugeno)</h6>
          <div class="row p-3 bg-light border rounded mx-0">
            <div class="col-md-6">
              <label class="form-label">Label Keputusan</label>
              <input type="text" name="keputusan" class="form-control" placeholder="Cth: Sangat Layak" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Nilai Konstanta (Z)</label>
              <input type="number" step="0.01" name="nilai_konstanta" class="form-control" placeholder="Cth: 100" required>
            </div>
          </div>

        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary">Simpan Rule</button>
        </div>

      </form>
    </div>
  </div>
</div>

{{-- ===================== MODAL EDIT ATURAN ===================== --}}
<div class="modal fade" id="modalEditAturan" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">

      <form id="formEditAturan" method="POST">
        @csrf
        @method('PUT')
        
        <div class="modal-header">
          <h5 class="modal-title">Edit Aturan</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">

          <div class="mb-3">
            <label class="form-label">Kode Rule</label>
            <input type="text" id="edit_kode" name="kode_rule" class="form-control bg-lighter" readonly>
          </div>

          <h6 class="fw-bold text-primary"><i class="bx bx-git-branch"></i> Logika IF (Syarat)</h6>
          <div class="p-3 bg-light border rounded mb-3">

            @foreach($kriteriaList as $kriteria)
            <div class="row mb-2 align-items-center">
              <div class="col-md-4 fw-bold text-secondary">
                {{ $kriteria->kode_kriteria }} - {{ $kriteria->nama_kriteria }}
              </div>
              <div class="col-md-8">
                <select name="detail[{{ $kriteria->id }}]" id="edit_kriteria_{{ $kriteria->id }}" class="form-select" required>
                  <option value="">-- Pilih Himpunan --</option>
                  @foreach($kriteria->subKriteria as $sub)
                    <option value="{{ $sub->id }}">{{ $sub->nama_sub_kriteria }}</option>
                  @endforeach
                </select>
              </div>
            </div>
            @endforeach

          </div>

          <h6 class="fw-bold text-success"><i class="bx bx-check-double"></i> Konsekuen (THEN / Hasil Sugeno)</h6>
          <div class="row p-3 bg-light border rounded mx-0">
            <div class="col-md-6">
              <label class="form-label">Label Keputusan</label>
              <input type="text" id="edit_keputusan" name="keputusan" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Nilai Konstanta (Z)</label>
              <input type="number" step="0.01" id="edit_z" name="nilai_konstanta" class="form-control" required>
            </div>
          </div>

        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary">Update Rule</button>
        </div>

      </form>
    </div>
  </div>
</div>

{{-- SCRIPT UNTUK AUTO-FILL DATA KE MODAL EDIT --}}
@push('scripts')
<script>
function isiFormEditRule(ruleId, kodeRule, keputusan, nilaiZ, detailRuleJSON) {
    // 1. Set Action Form
    document.getElementById('formEditAturan').action = '/admin/rule/' + ruleId;
    
    // 2. Set Nilai Dasar
    document.getElementById('edit_kode').value = kodeRule;
    document.getElementById('edit_keputusan').value = keputusan;
    document.getElementById('edit_z').value = nilaiZ;

    // 3. Reset semua select form
    document.querySelectorAll('#formEditAturan select').forEach(select => {
        select.value = '';
    });

    // 4. Set nilai option yang terpilih (Logika IF)
    // detailRuleJSON berisi array dari rule_detail database
    const details = typeof detailRuleJSON === 'string' ? JSON.parse(detailRuleJSON) : detailRuleJSON;
    
    details.forEach(detail => {
        let selectElement = document.getElementById('edit_kriteria_' + detail.kriteria_id);
        if(selectElement) {
            selectElement.value = detail.sub_kriteria_id;
        }
    });
}
</script>
@endpush
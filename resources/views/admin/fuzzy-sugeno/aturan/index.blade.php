@extends('admin.layouts.app')

@section('title', 'Data Aturan Sugeno')

@section('content')
<div class="content-wrapper">
  <div class="container-xxl container-p-y">

    <div class="card">

      <div class="card-header border-bottom d-flex flex-column flex-md-row align-items-center justify-content-between">
        <h5 class="card-title mb-3 mb-md-0">Data Aturan (Fuzzy Sugeno CPCL)</h5>

        <div class="d-flex gap-2">
            <button class="btn btn-outline-secondary">
                <i class="bx bx-refresh me-1"></i> Generate
            </button>

            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambahAturan">
                <i class="bx bx-plus me-1"></i> Tambah Aturan
            </button>
        </div>
      </div>

      <div class="table-responsive">
        <table class="table table-hover">
          <thead class="table-light">
            <tr>
              <th>No</th>
              <th>Kode</th>
              <th>Logika Aturan (IF - THEN)</th>
              <th>Output (z)</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>

            <tr>
              <td>1</td>
              <td><span class="badge bg-label-dark">R1</span></td>
              <td>
                IF C1=Luas AND C2=Milik AND C3=Lama AND C4=Tinggi AND C5=Lengkap
                THEN <b>Sangat Layak</b>
              </td>
              <td><span class="badge bg-label-success">z = 1</span></td>
              <td>
                <button class="btn btn-sm btn-outline-primary"
                  data-bs-toggle="modal" data-bs-target="#modalEditAturan"
                  onclick="isiFormEditAturan('R1','Luas','Milik','Lama','Tinggi','Lengkap','Sangat Layak',1)">
                  Edit
                </button>
              </td>
            </tr>

            <tr>
              <td>2</td>
              <td><span class="badge bg-label-dark">R2</span></td>
              <td>
                IF C1=Sedang AND C2=Sewa AND C3=Baru AND C4=Sedang AND C5=Lengkap
                THEN <b>Cukup Layak</b>
              </td>
              <td><span class="badge bg-label-warning">z = 0.6</span></td>
              <td>
                <button class="btn btn-sm btn-outline-primary"
                  data-bs-toggle="modal" data-bs-target="#modalEditAturan"
                  onclick="isiFormEditAturan('R2','Sedang','Sewa','Baru','Sedang','Lengkap','Cukup Layak',0.6)">
                  Edit
                </button>
              </td>
            </tr>

            <tr>
              <td>3</td>
              <td><span class="badge bg-label-dark">R3</span></td>
              <td>
                IF C1=Sempit AND C2=Tidak AND C3=Baru AND C4=Rendah AND C5=Tidak
                THEN <b>Tidak Layak</b>
              </td>
              <td><span class="badge bg-label-danger">z = 0.2</span></td>
              <td>
                <button class="btn btn-sm btn-outline-primary"
                  data-bs-toggle="modal" data-bs-target="#modalEditAturan"
                  onclick="isiFormEditAturan('R3','Sempit','Tidak','Baru','Rendah','Tidak','Tidak Layak',0.2)">
                  Edit
                </button>
              </td>
            </tr>

          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

@include('admin.partials.modals.aturan_modal')

<script>
function isiFormEditAturan(kode,c1,c2,c3,c4,c5,label,z){
  document.getElementById('edit_kode').value = kode;
  document.getElementById('edit_C1').value = c1;
  document.getElementById('edit_C2').value = c2;
  document.getElementById('edit_C3').value = c3;
  document.getElementById('edit_C4').value = c4;
  document.getElementById('edit_C5').value = c5;
  document.getElementById('edit_label').value = label;
  document.getElementById('edit_z').value = z;
}
</script>
@endsection

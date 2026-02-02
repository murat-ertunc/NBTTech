<?php

?>
<!-- Proje Modal -->
<div class="modal fade" id="projectModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="projectModalTitle">Yeni Proje</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-danger d-none modal-error" id="projectModalError"></div>
        <input type="hidden" id="projectId">
        <div class="row mb-3">
          <label class="col-4 col-form-label">Müşteri <span class="text-danger">*</span></label>
          <div class="col-8">
            <select class="form-select" id="projectMusteriId" required>
              <option value="">Seçiniz...</option>
            </select>
            <small class="form-hint">Zorunlu</small>
            <div class="invalid-feedback"></div>
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-4 col-form-label">Proje Adı <span class="text-danger">*</span></label>
          <div class="col-8">
            <input type="text" class="form-control" id="projectName" required>
            <small class="form-hint">Zorunlu</small>
            <div class="invalid-feedback"></div>
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-4 col-form-label">Durum</label>
          <div class="col-8">
            <select class="form-select" id="projectStatus">
              <!-- Dinamik olarak doldurulacak -->
            </select>
            <div class="invalid-feedback"></div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
        <button type="button" class="btn btn-primary" id="btnSaveProject" data-can-any="projects.create,projects.update">Kaydet</button>
      </div>
    </div>
  </div>
</div>

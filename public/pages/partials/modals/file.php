<?php
/**
 * Dosya Yükleme Modal
 */
?>
<!-- Dosya Yükleme Modal -->
<div class="modal fade" id="fileModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="fileModalTitle"><i class="bi bi-upload me-2"></i>Dosya Yükle</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-danger d-none modal-error" id="fileModalError"></div>
        <input type="hidden" id="fileMusteriId">
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Proje <span class="text-danger">*</span></label>
          <div class="col-12 col-md-8">
            <select class="form-select" id="fileProjeId" required>
              <option value="">Proje Seçiniz...</option>
            </select>
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Dosya Seç <span class="text-danger">*</span></label>
          <div class="col-12 col-md-8">
            <input type="file" class="form-control" id="fileInput" required
              accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,image/jpeg,image/png,image/gif">
            <div class="form-text text-muted">PDF, Word, Excel, Resim. Maks. 10MB.</div>
            <div class="invalid-feedback" id="fileInputError"></div>
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Açıklama</label>
          <div class="col-12 col-md-8">
            <input type="text" class="form-control" id="fileAciklama">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
        <button type="button" class="btn btn-primary" id="btnSaveFile">Yükle</button>
      </div>
    </div>
  </div>
</div>

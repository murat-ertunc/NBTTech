<?php
/**
 * Sözleşme Modal - Ekle/Düzenle
 */
?>
<!-- Sözleşme Modal -->
<div class="modal fade" id="contractModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="contractModalTitle">Yeni Sözleşme</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-danger d-none modal-error" id="contractModalError"></div>
        <input type="hidden" id="contractId">
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Müşteri <span class="text-danger">*</span></label>
          <div class="col-12 col-md-8">
            <select class="form-select" id="contractMusteriId" required>
              <option value="">Seçiniz...</option>
            </select>
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Proje <span class="text-danger">*</span></label>
          <div class="col-12 col-md-8">
            <select class="form-select" id="contractProjeId" required>
              <option value="">Proje Seçiniz...</option>
            </select>
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Sözleşme Tarihi</label>
          <div class="col-12 col-md-8">
            <input type="date" class="form-control" id="contractStart">
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Tutar <span class="text-danger">*</span></label>
          <div class="col-12 col-md-8">
            <div class="input-group">
               <input type="text" class="form-control nbt-money-input" id="contractAmount" placeholder="0,00" required value="0,00">
              <select class="form-select" id="contractCurrency" style="max-width: 90px;">
              </select>
            </div>
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Durum</label>
          <div class="col-12 col-md-8">
            <select class="form-select" id="contractStatus">
              <!-- Dinamik olarak doldurulacak -->
            </select>
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">PDF Dosya</label>
          <div class="col-12 col-md-8">
            <input type="file" class="form-control" id="contractDosya" accept=".pdf,application/pdf">
            <div class="form-text text-muted">Sadece PDF. Maks. 10MB.</div>
            <div class="invalid-feedback" id="contractDosyaError"></div>
            <div class="mt-2 d-none" id="contractCurrentFile">
              <span class="badge bg-secondary me-2"><i class="bi bi-file-pdf me-1"></i><span id="contractCurrentFileName"></span></span>
              <button type="button" class="btn btn-sm btn-outline-danger" id="btnRemoveContractFile"><i class="bi bi-x"></i></button>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
        <button type="button" class="btn btn-primary" id="btnSaveContract" data-can-any="contracts.create,contracts.update">Kaydet</button>
      </div>
    </div>
  </div>
</div>

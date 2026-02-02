<?php



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
          <label class="col-4 col-form-label">Müşteri <span class="text-danger">*</span></label>
          <div class="col-8">
            <select class="form-select" id="contractMusteriId" required>
              <option value="">Seçiniz...</option>
            </select>
            <small class="form-hint">Zorunlu</small>
            <div class="invalid-feedback"></div>
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-4 col-form-label">Proje <span class="text-danger">*</span></label>
          <div class="col-8">
            <select class="form-select" id="contractProjeId" required>
              <option value="">Proje Seçiniz...</option>
            </select>
            <small class="form-hint">Zorunlu</small>
            <div class="invalid-feedback"></div>
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-4 col-form-label">Sözleşme Tarihi</label>
          <div class="col-8">
            <input type="date" class="form-control" id="contractStart">
            <div class="invalid-feedback"></div>
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-4 col-form-label">Tutar <span class="text-danger">*</span></label>
          <div class="col-8">
            <div class="input-group">
               <input type="text" class="form-control price__input nbt-money-input" id="contractAmount" placeholder="0,00" required value="0,00">
              <select class="form-select" id="contractCurrency" style="max-width: 90px;">
              </select>
            </div>
            <small class="form-hint">Zorunlu, Sayısal</small>
            <div class="invalid-feedback"></div>
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-4 col-form-label">Durum</label>
          <div class="col-8">
            <select class="form-select" id="contractStatus">
              <!-- Dinamik olarak doldurulacak -->
            </select>
            <div class="invalid-feedback"></div>
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-4 col-form-label">Sözleşme Dosyası</label>
          <div class="col-8">
            <input type="file" class="form-control" id="contractDosya" accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document">
            <small class="form-hint">PDF veya Word (PDF, DOC, DOCX), Maks 10MB</small>
            <div class="invalid-feedback" id="contractDosyaError"></div>
            <div class="mt-2 d-none" id="contractCurrentFile">
              <span class="badge bg-secondary me-2"><i class="bi bi-file-earmark-text me-1"></i><span id="contractCurrentFileName" class="u-break-anywhere"></span></span>
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

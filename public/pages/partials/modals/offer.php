<?php

?>
<!-- Teklif Modal -->
<div class="modal fade" id="offerModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="offerModalTitle">Yeni Teklif</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-danger d-none modal-error" id="offerModalError"></div>
        <input type="hidden" id="offerId">
        <div class="row mb-3">
          <label class="col-4 col-form-label">Müşteri <span class="text-danger">*</span></label>
          <div class="col-8">
            <select class="form-select" id="offerMusteriId" required>
              <option value="">Seçiniz...</option>
            </select>
            <small class="form-hint">Zorunlu</small>
            <div class="invalid-feedback"></div>
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-4 col-form-label">Proje <span class="text-danger">*</span></label>
          <div class="col-8">
            <select class="form-select" id="offerProjeId" required>
              <option value="">Proje Seçiniz...</option>
            </select>
            <small class="form-hint">Zorunlu</small>
            <div class="invalid-feedback"></div>
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-4 col-form-label">Konu</label>
          <div class="col-8">
            <input type="text" class="form-control" id="offerSubject">
            <div class="invalid-feedback"></div>
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-4 col-form-label">Tutar <span class="text-danger">*</span></label>
          <div class="col-8">
            <div class="input-group">
              <input type="text" class="form-control price__input nbt-money-input" id="offerAmount" placeholder="0,00" required value="0,00">
              <select class="form-select" id="offerCurrency" style="max-width: 90px;">
              </select>
            </div>
            <small class="form-hint">Zorunlu, Sayısal</small>
            <div class="invalid-feedback"></div>
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-4 col-form-label">Tarih <span class="text-danger">*</span></label>
          <div class="col-8">
            <input type="date" class="form-control" id="offerDate" value="<?= date('Y-m-d') ?>" required>
            <small class="form-hint">Zorunlu</small>
            <div class="invalid-feedback"></div>
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-4 col-form-label">Geçerlilik <span class="text-danger">*</span></label>
          <div class="col-8">
            <input type="date" class="form-control" id="offerValidDate" required>
            <small class="form-hint">Zorunlu</small>
            <div class="invalid-feedback"></div>
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-4 col-form-label">Durum</label>
          <div class="col-8">
            <select class="form-select" id="offerStatus">
              <!-- Dinamik olarak doldurulacak -->
            </select>
            <div class="invalid-feedback"></div>
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-4 col-form-label">PDF Dosya</label>
          <div class="col-8">
            <input type="file" class="form-control" id="offerDosya" accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document">
            <small class="form-hint">PDF, Maks 10MB</small>
            <div class="invalid-feedback" id="offerDosyaError"></div>
            <div class="mt-2 d-none" id="offerCurrentFile">
              <span class="badge bg-secondary me-2"><i class="bi bi-file-pdf me-1"></i><span id="offerCurrentFileName" class="u-break-anywhere"></span></span>
              <button type="button" class="btn btn-sm btn-outline-danger" id="btnRemoveOfferFile"><i class="bi bi-x"></i></button>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
        <button type="button" class="btn btn-primary" id="btnSaveOffer" data-can-any="offers.create,offers.update">Kaydet</button>
      </div>
    </div>
  </div>
</div>

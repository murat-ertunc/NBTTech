<?php
/**
 * Teklif Modal - Ekle/Düzenle
 */
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
          <label class="col-12 col-md-4 col-form-label">Müşteri <span class="text-danger">*</span></label>
          <div class="col-12 col-md-8">
            <select class="form-select" id="offerMusteriId" required>
              <option value="">Seçiniz...</option>
            </select>
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Proje <span class="text-danger">*</span></label>
          <div class="col-12 col-md-8">
            <select class="form-select" id="offerProjeId" required>
              <option value="">Proje Seçiniz...</option>
            </select>
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Konu</label>
          <div class="col-12 col-md-8">
            <input type="text" class="form-control" id="offerSubject">
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Tutar <span class="text-danger">*</span></label>
          <div class="col-12 col-md-8">
            <div class="input-group">
              <input type="number" step="0.01" class="form-control nbt-money-input" id="offerAmount" placeholder="0,00" required>
              <select class="form-select" id="offerCurrency" style="max-width: 90px;">
              </select>
            </div>
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Tarih <span class="text-danger">*</span></label>
          <div class="col-12 col-md-8">
            <input type="date" class="form-control" id="offerDate" value="<?= date('Y-m-d') ?>" required>
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Geçerlilik <span class="text-danger">*</span></label>
          <div class="col-12 col-md-8">
            <input type="date" class="form-control" id="offerValidDate" required>
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Durum</label>
          <div class="col-12 col-md-8">
            <select class="form-select" id="offerStatus">
              <!-- Dinamik olarak doldurulacak -->
            </select>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
        <button type="button" class="btn btn-primary" id="btnSaveOffer">Kaydet</button>
      </div>
    </div>
  </div>
</div>

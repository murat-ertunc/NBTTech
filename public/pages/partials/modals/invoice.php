<?php
/**
 * Fatura Modal - Ekle/Düzenle
 */
?>
<!-- Fatura Modal -->
<div class="modal fade" id="invoiceModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="invoiceModalTitle">Yeni Fatura</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-danger d-none modal-error" id="invoiceModalError"></div>
        <input type="hidden" id="invoiceId">
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Müşteri <span class="text-danger">*</span></label>
          <div class="col-12 col-md-8">
            <select class="form-select" id="invoiceMusteriId" required>
              <option value="">Seçiniz...</option>
            </select>
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Proje <span class="text-danger">*</span></label>
          <div class="col-12 col-md-8">
            <select class="form-select" id="invoiceProjeId" required>
              <option value="">Proje Seçiniz...</option>
            </select>
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Tarih</label>
          <div class="col-12 col-md-8">
            <input type="date" class="form-control" id="invoiceTarih" value="<?= date('Y-m-d') ?>">
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Tutar</label>
          <div class="col-12 col-md-8">
            <div class="input-group">
              <input type="number" step="0.01" class="form-control" id="invoiceTutar">
              <select class="form-select" id="invoiceDoviz" style="max-width: 90px;">
                <option value="TRY">TRY</option>
                <option value="USD">USD</option>
                <option value="EUR">EUR</option>
              </select>
            </div>
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Açıklama</label>
          <div class="col-12 col-md-8">
            <textarea class="form-control" id="invoiceAciklama" rows="2"></textarea>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
        <button type="button" class="btn btn-primary" id="btnSaveInvoice">Kaydet</button>
      </div>
    </div>
  </div>
</div>

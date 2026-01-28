<?php
/**
 * Ödeme Modal - Ekle/Düzenle
 */
?>
<!-- Ödeme Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="paymentModalTitle">Yeni Ödeme</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-danger d-none modal-error" id="paymentModalError"></div>
        <input type="hidden" id="paymentId">
        <div class="row mb-3">
          <label class="col-4 col-form-label">Müşteri <span class="text-danger">*</span></label>
          <div class="col-8">
            <select class="form-select" id="paymentMusteriId" required>
              <option value="">Seçiniz...</option>
            </select>
            <small class="form-hint">Zorunlu</small>
            <div class="invalid-feedback"></div>
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-4 col-form-label">Proje <span class="text-danger">*</span></label>
          <div class="col-8">
            <select class="form-select" id="paymentProjeId" required>
              <option value="">Proje Seçiniz...</option>
            </select>
            <small class="form-hint">Zorunlu</small>
            <div class="invalid-feedback"></div>
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-4 col-form-label">Fatura <span class="text-danger">*</span></label>
          <div class="col-8">
            <select class="form-select" id="paymentFaturaId" required>
              <option value="">Fatura Seçiniz...</option>
            </select>
            <small class="form-hint">Zorunlu, Kalan tutar aşılamaz</small>
            <div class="invalid-feedback"></div>
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-4 col-form-label">Tarih <span class="text-danger">*</span></label>
          <div class="col-8">
            <input type="date" class="form-control" id="paymentTarih" value="<?= date('Y-m-d') ?>">
            <small class="form-hint">Zorunlu</small>
            <div class="invalid-feedback"></div>
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-4 col-form-label">Tutar <span class="text-danger">*</span></label>
          <div class="col-8">
            <input type="text" class="form-control nbt-money-input" id="paymentTutar" placeholder="0,00" value="0,00">
            <small class="form-hint">Zorunlu, Sayısal</small>
            <div class="invalid-feedback"></div>
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-4 col-form-label">Açıklama</label>
          <div class="col-8">
            <textarea class="form-control" id="paymentAciklama" rows="2"></textarea>
            <div class="invalid-feedback"></div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
        <button type="button" class="btn btn-primary" id="btnSavePayment" data-can-any="payments.create,payments.update">Kaydet</button>
      </div>
    </div>
  </div>
</div>

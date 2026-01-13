<?php
/**
 * Teminat Modal - Ekle/Düzenle
 */
?>
<!-- Teminat Modal -->
<div class="modal fade" id="guaranteeModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="guaranteeModalTitle">Yeni Teminat</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-danger d-none modal-error" id="guaranteeModalError"></div>
        <input type="hidden" id="guaranteeId">
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Müşteri <span class="text-danger">*</span></label>
          <div class="col-12 col-md-8">
            <select class="form-select" id="guaranteeMusteriId" required>
              <option value="">Seçiniz...</option>
            </select>
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Proje <span class="text-danger">*</span></label>
          <div class="col-12 col-md-8">
            <select class="form-select" id="guaranteeProjeId" required>
              <option value="">Proje Seçiniz...</option>
            </select>
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Belge No</label>
          <div class="col-12 col-md-8">
            <input type="text" class="form-control" id="guaranteeNo">
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Tür</label>
          <div class="col-12 col-md-8">
            <select class="form-select" id="guaranteeType">
              <option value="Nakit">Nakit</option>
              <option value="Teminat Mektubu">Teminat Mektubu</option>
              <option value="Çek">Çek</option>
              <option value="Senet">Senet</option>
            </select>
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Banka</label>
          <div class="col-12 col-md-8">
            <input type="text" class="form-control" id="guaranteeBank">
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Tutar</label>
          <div class="col-12 col-md-8">
            <div class="input-group">
              <input type="number" step="0.01" class="form-control" id="guaranteeAmount">
              <select class="form-select" id="guaranteeCurrency" style="max-width: 90px;">
                <option value="TRY">TRY</option>
                <option value="USD">USD</option>
                <option value="EUR">EUR</option>
              </select>
            </div>
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Vade Tarihi</label>
          <div class="col-12 col-md-8">
            <input type="date" class="form-control" id="guaranteeDate">
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Durum</label>
          <div class="col-12 col-md-8">
            <select class="form-select" id="guaranteeStatus">
              <!-- Dinamik olarak doldurulacak -->
            </select>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
        <button type="button" class="btn btn-primary" id="btnSaveGuarantee">Kaydet</button>
      </div>
    </div>
  </div>
</div>

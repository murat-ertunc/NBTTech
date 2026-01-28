<?php
/**
 * Damga Vergisi Modal - Ekle/Düzenle
 */
?>
<!-- Damga Vergisi Modal -->
<div class="modal fade" id="stampTaxModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="stampTaxModalTitle"><i class="bi bi-percent me-2"></i>Yeni Damga Vergisi</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-danger d-none modal-error" id="stampTaxModalError"></div>
        <input type="hidden" id="stampTaxId">
        <input type="hidden" id="stampTaxMusteriId">
        <div class="row mb-3">
          <label class="col-4 col-form-label">Proje <span class="text-danger">*</span></label>
          <div class="col-8">
            <select class="form-select" id="stampTaxProjeId" required>
              <option value="">Proje Seçiniz...</option>
            </select>
            <small class="form-hint">Zorunlu</small>
            <div class="invalid-feedback"></div>
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-4 col-form-label">Tarih <span class="text-danger">*</span></label>
          <div class="col-8">
            <input type="date" class="form-control" id="stampTaxTarih" required>
            <small class="form-hint">Zorunlu</small>
            <div class="invalid-feedback"></div>
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-4 col-form-label">Tutar <span class="text-danger">*</span></label>
          <div class="col-8">
            <div class="input-group">
              <input type="text" class="form-control nbt-money-input" id="stampTaxTutar" placeholder="0,00" required value="0,00">
              <select class="form-select" id="stampTaxDovizCinsi" style="max-width: 90px;">
              </select>
            </div>
            <small class="form-hint">Zorunlu, Sayısal</small>
            <div class="invalid-feedback"></div>
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-4 col-form-label">Açıklama</label>
          <div class="col-8">
            <textarea class="form-control" id="stampTaxAciklama" rows="2"></textarea>
            <div class="invalid-feedback"></div>
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-4 col-form-label">PDF Dosya</label>
          <div class="col-8">
            <input type="file" class="form-control" id="stampTaxDosya" accept=".pdf,application/pdf">
            <small class="form-hint">PDF, Maks 10MB</small>
            <div class="invalid-feedback" id="stampTaxDosyaError"></div>
            <div class="mt-2 d-none" id="stampTaxCurrentFile">
              <span class="badge bg-secondary me-2"><i class="bi bi-file-pdf me-1"></i><span id="stampTaxCurrentFileName"></span></span>
              <button type="button" class="btn btn-sm btn-outline-danger" id="btnRemoveStampTaxFile"><i class="bi bi-x"></i></button>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
        <button type="button" class="btn btn-primary" id="btnSaveStampTax" data-can-any="stamp_taxes.create,stamp_taxes.update">Kaydet</button>
      </div>
    </div>
  </div>
</div>

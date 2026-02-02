<?php



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
              <input type="text" class="form-control price__input nbt-money-input" id="stampTaxTutar" placeholder="0,00" required value="0,00">
              <select class="form-select" id="stampTaxDovizCinsi" style="max-width: 90px;">
              </select>
            </div>
            <small class="form-hint">Zorunlu, Sayısal</small>
            <div class="invalid-feedback"></div>
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-4 col-form-label">Ödeme Durumu</label>
          <div class="col-8">
            <select class="form-select" id="stampTaxOdemeDurumu">
              <option value="Ödenmedi">Ödenmedi</option>
              <option value="Ödendi">Ödendi</option>
              <option value="Kısmi">Kısmi</option>
            </select>
            <div class="invalid-feedback"></div>
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-4 col-form-label">Notlar</label>
          <div class="col-8">
            <textarea class="form-control" id="stampTaxNotlar" rows="2"></textarea>
            <div class="invalid-feedback"></div>
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-4 col-form-label">Dosya</label>
          <div class="col-8">
            <input type="file" class="form-control" id="stampTaxDosya" accept=".pdf,.doc,.docx">
            <small class="form-hint">PDF veya Word (PDF, DOC, DOCX). Maks 10MB</small>
            <div class="invalid-feedback" id="stampTaxDosyaError"></div>
            <div class="mt-2 d-none" id="stampTaxCurrentFile">
              <span class="badge bg-secondary me-2"><i class="bi bi-file-pdf me-1"></i><span id="stampTaxCurrentFileName" class="u-break-anywhere"></span></span>
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

<!-- Döviz Ekleme/Düzenleme Modalı -->
<div class="modal fade" id="currencyModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-success text-white py-2">
        <h6 class="modal-title" id="currencyModalTitle">Yeni Döviz</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-danger d-none mb-3" id="currencyModalError"></div>
        <input type="hidden" id="currencyId">
        <input type="hidden" id="currencyGrup" value="doviz">
        
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Döviz Kodu <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="currencyKod" placeholder="USD, EUR, TRY..." maxlength="5" style="text-transform:uppercase;">
          </div>
          <div class="col-md-6">
            <label class="form-label">Simge <span class="text-danger">*</span></label>
            <input type="text" class="form-control text-center fw-bold fs-4" id="currencyDeger" placeholder="$, €, ₺..." maxlength="5">
          </div>
          <div class="col-12">
            <label class="form-label">Etiket <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="currencyEtiket" placeholder="Amerikan Doları, Euro, Türk Lirası...">
          </div>
          <div class="col-md-6">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" id="currencyAktif" checked>
              <label class="form-check-label" for="currencyAktif">Aktif</label>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" id="currencyVarsayilan">
              <label class="form-check-label" for="currencyVarsayilan">Varsayılan</label>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer py-2">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">İptal</button>
        <button type="button" class="btn btn-success btn-sm" id="btnSaveCurrency" data-can-any="parameters.create,parameters.update">
          <i class="bi bi-check-lg me-1"></i>Kaydet
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Durum Ekleme/Düzenleme Modalı -->
<div class="modal fade" id="statusModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-info text-white py-2">
        <h6 class="modal-title" id="statusModalTitle">Yeni Durum</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-danger d-none mb-3" id="statusModalError"></div>
        <input type="hidden" id="statusId">
        <input type="hidden" id="statusGrup">
        
        <div class="row g-3">
          <div class="col-12">
            <label class="form-label">Durum Adı <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="statusEtiket" placeholder="Aktif, Pasif, Beklemede...">
          </div>
          
          <div class="col-12">
            <label class="form-label">Badge Rengi <span class="text-danger">*</span></label>
            <div class="row g-2" id="badgeColorOptions">
              <div class="col-6 col-md-3">
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="statusBadgeColor" id="badge_primary" value="primary">
                  <label class="form-check-label w-100" for="badge_primary">
                    <span class="badge bg-primary w-100 py-2">Mavi (Primary)</span>
                  </label>
                </div>
              </div>
              <div class="col-6 col-md-3">
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="statusBadgeColor" id="badge_secondary" value="secondary">
                  <label class="form-check-label w-100" for="badge_secondary">
                    <span class="badge bg-secondary w-100 py-2">Gri (Secondary)</span>
                  </label>
                </div>
              </div>
              <div class="col-6 col-md-3">
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="statusBadgeColor" id="badge_success" value="success" checked>
                  <label class="form-check-label w-100" for="badge_success">
                    <span class="badge bg-success w-100 py-2">Yeşil (Success)</span>
                  </label>
                </div>
              </div>
              <div class="col-6 col-md-3">
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="statusBadgeColor" id="badge_danger" value="danger">
                  <label class="form-check-label w-100" for="badge_danger">
                    <span class="badge bg-danger w-100 py-2">Kırmızı (Danger)</span>
                  </label>
                </div>
              </div>
              <div class="col-6 col-md-3">
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="statusBadgeColor" id="badge_warning" value="warning">
                  <label class="form-check-label w-100" for="badge_warning">
                    <span class="badge bg-warning text-dark w-100 py-2">Sarı (Warning)</span>
                  </label>
                </div>
              </div>
              <div class="col-6 col-md-3">
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="statusBadgeColor" id="badge_info" value="info">
                  <label class="form-check-label w-100" for="badge_info">
                    <span class="badge bg-info w-100 py-2">Açık Mavi (Info)</span>
                  </label>
                </div>
              </div>
              <div class="col-6 col-md-3">
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="statusBadgeColor" id="badge_light" value="light">
                  <label class="form-check-label w-100" for="badge_light">
                    <span class="badge bg-light text-dark border w-100 py-2">Beyaz (Light)</span>
                  </label>
                </div>
              </div>
              <div class="col-6 col-md-3">
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="statusBadgeColor" id="badge_dark" value="dark">
                  <label class="form-check-label w-100" for="badge_dark">
                    <span class="badge bg-dark w-100 py-2">Siyah (Dark)</span>
                  </label>
                </div>
              </div>
            </div>
          </div>
          
          <div class="col-12">
            <label class="form-label">Önizleme</label>
            <div class="p-3 bg-light rounded text-center">
              <span class="badge bg-success fs-6" id="statusBadgePreview">Örnek Durum</span>
            </div>
          </div>
          
          <div class="col-md-6">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" id="statusAktif" checked>
              <label class="form-check-label" for="statusAktif">Aktif</label>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" id="statusVarsayilan">
              <label class="form-check-label" for="statusVarsayilan">Varsayılan</label>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer py-2">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">İptal</button>
        <button type="button" class="btn btn-info btn-sm" id="btnSaveStatus">
          <i class="bi bi-check-lg me-1"></i>Kaydet
        </button>
      </div>
    </div>
  </div>
</div>

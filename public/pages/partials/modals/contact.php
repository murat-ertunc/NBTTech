<?php
/**
 * Kişi Modal - Ekle/Düzenle
 */
?>
<!-- Kişi Modal -->
<div class="modal fade" id="contactModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="contactModalTitle"><i class="bi bi-person me-2"></i>Yeni Kişi</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-danger d-none modal-error" id="contactModalError"></div>
        <input type="hidden" id="contactId">
        <input type="hidden" id="contactMusteriId">
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Proje <span class="text-danger">*</span></label>
          <div class="col-12 col-md-8">
            <select class="form-select" id="contactProjeId" required>
              <option value="">Proje Seçiniz...</option>
            </select>
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Ad Soyad <span class="text-danger">*</span></label>
          <div class="col-12 col-md-8">
            <input type="text" class="form-control" id="contactAdSoyad" required>
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Ünvan / Pozisyon</label>
          <div class="col-12 col-md-8">
            <input type="text" class="form-control" id="contactUnvan">
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Telefon</label>
          <div class="col-12 col-md-8">
            <input type="tel" class="form-control" id="contactTelefon">
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Dahili No</label>
          <div class="col-12 col-md-8">
            <input type="text" class="form-control" id="contactDahiliNo">
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">E-posta</label>
          <div class="col-12 col-md-8">
            <input type="email" class="form-control" id="contactEmail">
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Notlar</label>
          <div class="col-12 col-md-8">
            <textarea class="form-control" id="contactNotlar" rows="2"></textarea>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
        <button type="button" class="btn btn-primary" id="btnSaveContact">Kaydet</button>
      </div>
    </div>
  </div>
</div>

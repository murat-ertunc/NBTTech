<?php
/**
 * Görüşme Modal - Ekle/Düzenle
 */
?>
<!-- Görüşme Modal -->
<div class="modal fade" id="meetingModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="meetingModalTitle"><i class="bi bi-chat-dots me-2"></i>Yeni Görüşme</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-danger d-none modal-error" id="meetingModalError"></div>
        <input type="hidden" id="meetingId">
        <input type="hidden" id="meetingMusteriId">
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Proje <span class="text-danger">*</span></label>
          <div class="col-12 col-md-8">
            <select class="form-select" id="meetingProjeId" required>
              <option value="">Proje Seçiniz...</option>
            </select>
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Tarih <span class="text-danger">*</span></label>
          <div class="col-12 col-md-8">
            <input type="date" class="form-control" id="meetingTarih" required>
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Konu <span class="text-danger">*</span></label>
          <div class="col-12 col-md-8">
            <input type="text" class="form-control" id="meetingKonu" required>
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Görüşülen Kişi</label>
          <div class="col-12 col-md-8">
            <input type="text" class="form-control" id="meetingKisi">
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">E-posta</label>
          <div class="col-12 col-md-8">
            <input type="email" class="form-control" id="meetingEposta" placeholder="ornek@email.com">
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Telefon</label>
          <div class="col-12 col-md-8">
            <input type="text" class="form-control" id="meetingTelefon" placeholder="0532 XXX XX XX">
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Notlar</label>
          <div class="col-12 col-md-8">
            <textarea class="form-control" id="meetingNotlar" rows="3"></textarea>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
        <button type="button" class="btn btn-primary" id="btnSaveMeeting">Kaydet</button>
      </div>
    </div>
  </div>
</div>

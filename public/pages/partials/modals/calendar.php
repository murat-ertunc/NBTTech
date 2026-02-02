<?php
/**
 * Takvim Modal - Ekle/Düzenle
 */
?>
<!-- Takvim Modal -->
<div class="modal fade" id="calendarModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="calendarModalTitle">Yeni Takvim Kaydı</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-danger d-none modal-error" id="calendarModalError"></div>
        <input type="hidden" id="calendarId">
        <input type="hidden" id="calendarMusteriId">
        <div class="row mb-3">
          <label class="col-4 col-form-label">Proje <span class="text-danger">*</span></label>
          <div class="col-8">
            <select class="form-select" id="calendarProjeId" required>
              <option value="">Proje Seçiniz...</option>
            </select>
            <small class="form-hint">Zorunlu</small>
            <div class="invalid-feedback"></div>
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-4 col-form-label">Termin Tarihi <span class="text-danger">*</span></label>
          <div class="col-8">
            <input type="date" class="form-control" id="calendarTerminTarihi" required>
            <small class="form-hint">Zorunlu</small>
            <div class="invalid-feedback"></div>
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-4 col-form-label">Durum</label>
          <div class="col-8">
            <select class="form-select" id="calendarDurum">
              <!-- Dinamik olarak doldurulacak -->
            </select>
            <div class="invalid-feedback"></div>
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-4 col-form-label">İşin Özeti <span class="text-danger">*</span></label>
          <div class="col-8">
            <textarea class="form-control" id="calendarOzet" rows="3" maxlength="255" required></textarea>
            <small class="form-hint">Zorunlu, Maks 255</small>
            <small class="text-muted"><span id="calendarOzetCount">0</span>/255</small>
            <div class="invalid-feedback"></div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
        <button type="button" class="btn btn-primary" id="btnSaveCalendar" data-can-any="calendar.create,calendar.update">Kaydet</button>
      </div>
    </div>
  </div>
</div>

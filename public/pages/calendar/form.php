<?php
/**
 * Takvim Form Sayfası - Ekle/Düzenle
 * URL: /customer/{id}/calendar/new veya /customer/{id}/calendar/{calendarId}/edit
 */

$MusteriId = $MusteriId ?? 0;
$TakvimId = $TakvimId ?? 0;
$IsEdit = $TakvimId > 0;

$pageTitle = $IsEdit ? 'Takvim Düzenle' : 'Yeni Takvim Kaydı';
$activeNav = 'customers';
$currentPage = 'calendar-form';

// Form partial değişkenleri
$FormMusteriId = $MusteriId;
$FormTabKey = 'takvim';
$FormTitle = $pageTitle;
$FormIcon = 'bi-calendar3';
$FormColor = 'warning';
$FormBreadcrumb = $pageTitle;
$FormSaveButtonId = 'btnSaveCalendarPage';
$FormPermission = 'calendar.create,calendar.update';
$FormButtonColor = 'warning';

require __DIR__ . '/../partials/header.php';
?>

<div class="container-fluid py-4">
  <div class="row justify-content-center">
    <div class="col-12 col-lg-8 col-xl-6">
      <?php require __DIR__ . '/../partials/form-header.php'; ?>
      
      <div class="card-body">
        <form id="calendarPageForm">
          <div class="alert alert-danger d-none" id="calendarFormError"></div>
          
          <input type="hidden" id="calendarId" value="<?= (int)$TakvimId ?>">
          <input type="hidden" id="calendarMusteriId" value="<?= (int)$MusteriId ?>">
          
          <div class="row mb-3">
            <label class="col-12 col-md-3 col-form-label">Proje <span class="text-danger">*</span></label>
            <div class="col-12 col-md-9">
              <select class="form-select" id="calendarProjeId" required>
                <option value="">Proje Seçiniz...</option>
              </select>
            </div>
          </div>
          
          <div class="row mb-3">
            <label class="col-12 col-md-3 col-form-label">Termin Tarihi <span class="text-danger">*</span></label>
            <div class="col-12 col-md-9">
              <input type="date" class="form-control" id="calendarTerminTarihi" required>
            </div>
          </div>
          
          <div class="row mb-3">
            <label class="col-12 col-md-3 col-form-label">İşin Özeti <span class="text-danger">*</span></label>
            <div class="col-12 col-md-9">
              <textarea class="form-control" id="calendarOzet" rows="3" maxlength="255" required></textarea>
              <small class="text-muted"><span id="calendarOzetCount">0</span>/255</small>
            </div>
          </div>
        </form>
      </div>
      
      <?php require __DIR__ . '/../partials/form-footer.php'; ?>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  NbtPageForm.init('calendar', <?= (int)$MusteriId ?>, <?= (int)$TakvimId ?>, 'takvim');
});
</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>

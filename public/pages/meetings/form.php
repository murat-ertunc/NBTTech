<?php
/**
 * Görüşme Form Sayfası - Ekle/Düzenle
 * URL: /customer/{id}/meetings/new veya /customer/{id}/meetings/{meetingId}/edit
 */

$MusteriId = $MusteriId ?? 0;
$GorusmeId = $GorusmeId ?? 0;
$IsEdit = $GorusmeId > 0;

$pageTitle = $IsEdit ? 'Görüşme Düzenle' : 'Yeni Görüşme';
$activeNav = 'customers';
$currentPage = 'meeting-form';

// Form partial değişkenleri
$FormMusteriId = $MusteriId;
$FormTabKey = 'gorusme';
$FormTitle = $pageTitle;
$FormIcon = 'bi-chat-dots';
$FormColor = 'primary';
$FormBreadcrumb = $pageTitle;
$FormSaveButtonId = 'btnSaveMeetingPage';
$FormPermission = 'meetings.create,meetings.update';
$FormButtonColor = 'primary';

require __DIR__ . '/../partials/header.php';
?>

<div class="container-fluid py-4">
  <div class="row justify-content-center">
    <div class="col-12 col-lg-8 col-xl-6">
      <?php require __DIR__ . '/../partials/form-header.php'; ?>
      
      <div class="card-body">
        <form id="meetingPageForm">
          <div class="alert alert-danger d-none" id="meetingFormError"></div>
          
          <input type="hidden" id="meetingId" value="<?= (int)$GorusmeId ?>">
          <input type="hidden" id="meetingMusteriId" value="<?= (int)$MusteriId ?>">
          
          <div class="row mb-3">
            <label class="col-12 col-md-3 col-form-label">Proje <span class="text-danger">*</span></label>
            <div class="col-12 col-md-9">
              <select class="form-select" id="meetingProjeId" required>
                <option value="">Proje Seçiniz...</option>
              </select>
            </div>
          </div>
          
          <div class="row mb-3">
            <label class="col-12 col-md-3 col-form-label">Tarih <span class="text-danger">*</span></label>
            <div class="col-12 col-md-9">
              <input type="date" class="form-control" id="meetingTarih" value="<?= date('Y-m-d') ?>" required>
            </div>
          </div>
          
          <div class="row mb-3">
            <label class="col-12 col-md-3 col-form-label">Konu <span class="text-danger">*</span></label>
            <div class="col-12 col-md-9">
              <input type="text" class="form-control" id="meetingKonu" required>
            </div>
          </div>
          
          <div class="row mb-3">
            <label class="col-12 col-md-3 col-form-label">Görüşülen Kişi</label>
            <div class="col-12 col-md-9">
              <input type="text" class="form-control" id="meetingKisi">
            </div>
          </div>
          
          <div class="row mb-3">
            <label class="col-12 col-md-3 col-form-label">E-posta</label>
            <div class="col-12 col-md-9">
              <input type="email" class="form-control" id="meetingEposta" placeholder="ornek@email.com">
            </div>
          </div>
          
          <div class="row mb-3">
            <label class="col-12 col-md-3 col-form-label">Telefon</label>
            <div class="col-12 col-md-9">
              <input type="text" class="form-control" id="meetingTelefon" placeholder="0532 XXX XX XX">
            </div>
          </div>
          
          <div class="row mb-3">
            <label class="col-12 col-md-3 col-form-label">Notlar</label>
            <div class="col-12 col-md-9">
              <textarea class="form-control" id="meetingNotlar" rows="3"></textarea>
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
  NbtPageForm.init('meeting', <?= (int)$MusteriId ?>, <?= (int)$GorusmeId ?>, 'gorusme');
});
</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>

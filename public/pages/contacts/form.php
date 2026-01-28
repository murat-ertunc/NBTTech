<?php
/**
 * Kişi Form Sayfası - Ekle/Düzenle
 * URL: /customer/{id}/contacts/new veya /customer/{id}/contacts/{contactId}/edit
 */

$MusteriId = $MusteriId ?? 0;
$KisiId = $KisiId ?? 0;
$IsEdit = $KisiId > 0;

$pageTitle = $IsEdit ? 'Kişi Düzenle' : 'Yeni Kişi';
$activeNav = 'customers';
$currentPage = 'contact-form';

// Form partial değişkenleri
$FormMusteriId = $MusteriId;
$FormTabKey = 'kisiler';
$FormTitle = $pageTitle;
$FormIcon = 'bi-person';
$FormBreadcrumb = $pageTitle;
$FormSaveButtonId = 'btnSaveContactPage';
$FormPermission = 'contacts.create,contacts.update';
$FormButtonColor = 'primary';

require __DIR__ . '/../partials/header.php';
?>

<div class="container-fluid py-4">
  <div class="row justify-content-center">
    <div class="col-12">
      <?php require __DIR__ . '/../partials/form-header.php'; ?>
      
      <div class="card-body">
        <form id="contactPageForm">
          <div class="alert alert-danger d-none" id="contactFormError"></div>
          
          <input type="hidden" id="contactId" value="<?= (int)$KisiId ?>">
          <input type="hidden" id="contactMusteriId" value="<?= (int)$MusteriId ?>">
          
          <div class="row mb-3">
            <label class="col-4 col-form-label">Proje <span class="text-danger">*</span></label>
            <div class="col-8">
              <select class="form-select" id="contactProjeId" required>
                <option value="">Proje Seçiniz...</option>
              </select>
            </div>
          </div>
          
          <div class="row mb-3">
            <label class="col-4 col-form-label">Ad Soyad <span class="text-danger">*</span></label>
            <div class="col-8">
              <input type="text" class="form-control" id="contactAdSoyad" required>
            </div>
          </div>
          
          <div class="row mb-3">
            <label class="col-4 col-form-label">Ünvan / Pozisyon</label>
            <div class="col-8">
              <input type="text" class="form-control" id="contactUnvan">
            </div>
          </div>
          
          <div class="row mb-3">
            <label class="col-4 col-form-label">Telefon</label>
            <div class="col-8">
              <input type="text" class="form-control number__input" id="contactTelefon">
            </div>
          </div>
          
          <div class="row mb-3">
            <label class="col-4 col-form-label">Dahili No</label>
            <div class="col-8">
              <input type="text" class="form-control" id="contactDahiliNo">
            </div>
          </div>
          
          <div class="row mb-3">
            <label class="col-4 col-form-label">E-posta</label>
            <div class="col-8">
              <input type="email" class="form-control" id="contactEmail">
            </div>
          </div>
          
          <div class="row mb-3">
            <label class="col-4 col-form-label">Notlar</label>
            <div class="col-8">
              <textarea class="form-control" id="contactNotlar" rows="2"></textarea>
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
  NbtPageForm.init('contact', <?= (int)$MusteriId ?>, <?= (int)$KisiId ?>, 'kisiler');
});
</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>

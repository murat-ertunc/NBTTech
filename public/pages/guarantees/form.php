<?php
/**
 * Teminat Form Sayfası - Ekle/Düzenle
 * URL: /customer/{id}/guarantees/new veya /customer/{id}/guarantees/{guaranteeId}/edit
 */

$MusteriId = $MusteriId ?? 0;
$TeminatId = $TeminatId ?? 0;
$IsEdit = $TeminatId > 0;

$pageTitle = $IsEdit ? 'Teminat Düzenle' : 'Yeni Teminat';
$activeNav = 'customers';
$currentPage = 'guarantee-form';

// Form partial değişkenleri
$FormMusteriId = $MusteriId;
$FormTabKey = 'teminatlar';
$FormTitle = $pageTitle;
$FormIcon = 'bi-shield-check';
$FormColor = 'danger';
$FormBreadcrumb = $pageTitle;
$FormSaveButtonId = 'btnSaveGuaranteePage';
$FormPermission = 'guarantees.create,guarantees.update';
$FormButtonColor = 'danger';

require __DIR__ . '/../partials/header.php';
?>

<div class="container-fluid py-4">
  <div class="row justify-content-center">
    <div class="col-12 col-lg-8 col-xl-6">
      <?php require __DIR__ . '/../partials/form-header.php'; ?>
      
      <div class="card-body">
        <form id="guaranteePageForm">
          <div class="alert alert-danger d-none" id="guaranteeFormError"></div>
          
          <input type="hidden" id="guaranteeId" value="<?= (int)$TeminatId ?>">
          <input type="hidden" id="guaranteeMusteriId" value="<?= (int)$MusteriId ?>">
          
          <div class="row mb-3">
            <label class="col-12 col-md-3 col-form-label">Proje <span class="text-danger">*</span></label>
            <div class="col-12 col-md-9">
              <select class="form-select" id="guaranteeProjeId" required>
                <option value="">Proje Seçiniz...</option>
              </select>
            </div>
          </div>
          
          <div class="row mb-3">
            <label class="col-12 col-md-3 col-form-label">Teminat Türü <span class="text-danger">*</span></label>
            <div class="col-12 col-md-9">
              <select class="form-select" id="guaranteeTur" required>
                <option value="">Teminat Türü Seçiniz...</option>
              </select>
            </div>
          </div>
          
          <div class="row mb-3">
            <label class="col-12 col-md-3 col-form-label">Teminat No</label>
            <div class="col-12 col-md-9">
              <input type="text" class="form-control" id="guaranteeNo">
            </div>
          </div>
          
          <div class="row mb-3">
            <label class="col-12 col-md-3 col-form-label">Tutar <span class="text-danger">*</span></label>
            <div class="col-12 col-md-9">
              <div class="input-group">
                <input type="text" class="form-control" id="guaranteeTutar" required>
                <select class="form-select" id="guaranteeDoviz" style="max-width: 80px;">
                  <option value="TL" selected>TL</option>
                  <option value="USD">USD</option>
                  <option value="EUR">EUR</option>
                </select>
              </div>
            </div>
          </div>
          
          <div class="row mb-3">
            <label class="col-12 col-md-3 col-form-label">Başlangıç Tarihi <span class="text-danger">*</span></label>
            <div class="col-12 col-md-9">
              <input type="date" class="form-control" id="guaranteeBaslangicTarihi" required>
            </div>
          </div>
          
          <div class="row mb-3">
            <label class="col-12 col-md-3 col-form-label">Bitiş Tarihi <span class="text-danger">*</span></label>
            <div class="col-12 col-md-9">
              <input type="date" class="form-control" id="guaranteeBitisTarihi" required>
            </div>
          </div>
          
          <div class="row mb-3">
            <label class="col-12 col-md-3 col-form-label">Banka/Kurum</label>
            <div class="col-12 col-md-9">
              <input type="text" class="form-control" id="guaranteeBanka">
            </div>
          </div>
          
          <div class="row mb-3">
            <label class="col-12 col-md-3 col-form-label">Durum</label>
            <div class="col-12 col-md-9">
              <select class="form-select" id="guaranteeDurum">
                <option value="Aktif">Aktif</option>
                <option value="İade Edildi">İade Edildi</option>
                <option value="Nakde Çevrildi">Nakde Çevrildi</option>
                <option value="Süresi Doldu">Süresi Doldu</option>
              </select>
            </div>
          </div>
          
          <div class="row mb-3">
            <label class="col-12 col-md-3 col-form-label">Notlar</label>
            <div class="col-12 col-md-9">
              <textarea class="form-control" id="guaranteeNotlar" rows="2"></textarea>
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
  NbtPageForm.init('guarantee', <?= (int)$MusteriId ?>, <?= (int)$TeminatId ?>, 'teminatlar');
});
</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>

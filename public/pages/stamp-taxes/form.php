<?php
/**
 * Damga Vergisi Form Sayfası - Ekle/Düzenle
 * URL: /customer/{id}/stamp-taxes/new veya /customer/{id}/stamp-taxes/{stampTaxId}/edit
 */

$MusteriId = $MusteriId ?? 0;
$DamgaId = $DamgaId ?? 0;
$IsEdit = $DamgaId > 0;

$pageTitle = $IsEdit ? 'Damga Vergisi Düzenle' : 'Yeni Damga Vergisi';
$activeNav = 'customers';
$currentPage = 'stamp-tax-form';

// Form partial değişkenleri
$FormMusteriId = $MusteriId;
$FormTabKey = 'damgavergisi';
$FormTitle = $pageTitle;
$FormIcon = 'bi-file-earmark-text';
$FormColor = 'secondary';
$FormBreadcrumb = $pageTitle;
$FormSaveButtonId = 'btnSaveStampTaxPage';
$FormPermission = 'stamp_taxes.create,stamp_taxes.update';
$FormButtonColor = 'secondary';

require __DIR__ . '/../partials/header.php';
?>

<div class="container-fluid py-4">
  <div class="row justify-content-center">
    <div class="col-12">
      <?php require __DIR__ . '/../partials/form-header.php'; ?>
      
      <div class="card-body">
        <form id="stampTaxPageForm">
          <div class="alert alert-danger d-none" id="stampTaxFormError"></div>
          
          <input type="hidden" id="stampTaxId" value="<?= (int)$DamgaId ?>">
          <input type="hidden" id="stampTaxMusteriId" value="<?= (int)$MusteriId ?>">
          
          <div class="row mb-3">
            <label class="col-12 col-md-3 col-form-label">Proje <span class="text-danger">*</span></label>
            <div class="col-12 col-md-9">
              <select class="form-select" id="stampTaxProjeId" required>
                <option value="">Proje Seçiniz...</option>
              </select>
            </div>
          </div>
          
          <div class="row mb-3">
            <label class="col-12 col-md-3 col-form-label">Sözleşme</label>
            <div class="col-12 col-md-9">
              <select class="form-select" id="stampTaxSozlesmeId">
                <option value="">Sözleşme Seçiniz (Opsiyonel)...</option>
              </select>
            </div>
          </div>
          
          <div class="row mb-3">
            <label class="col-12 col-md-3 col-form-label">Belge Tarihi <span class="text-danger">*</span></label>
            <div class="col-12 col-md-9">
              <input type="date" class="form-control" id="stampTaxBelgeTarihi" value="<?= date('Y-m-d') ?>" required>
            </div>
          </div>
          
          <div class="row mb-3">
            <label class="col-12 col-md-3 col-form-label">Tutar <span class="text-danger">*</span></label>
            <div class="col-12 col-md-9">
              <div class="input-group">
                <input type="text" class="form-control" id="stampTaxTutar" required>
                <span class="input-group-text">₺</span>
              </div>
            </div>
          </div>
          
          <div class="row mb-3">
            <label class="col-12 col-md-3 col-form-label">Ödeme Durumu</label>
            <div class="col-12 col-md-9">
              <select class="form-select" id="stampTaxOdemeDurumu">
                <option value="Ödenmedi">Ödenmedi</option>
                <option value="Ödendi">Ödendi</option>
                <option value="Kısmi">Kısmi</option>
              </select>
            </div>
          </div>
          
          <div class="row mb-3">
            <label class="col-12 col-md-3 col-form-label">Notlar</label>
            <div class="col-12 col-md-9">
              <textarea class="form-control" id="stampTaxNotlar" rows="2"></textarea>
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
  NbtPageForm.init('stamp-tax', <?= (int)$MusteriId ?>, <?= (int)$DamgaId ?>, 'damgavergisi');
});
</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>

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
$FormBreadcrumb = $pageTitle;
$FormSaveButtonId = 'btnSaveGuaranteePage';
$FormPermission = 'guarantees.create,guarantees.update';
$FormButtonColor = 'primary';

require __DIR__ . '/../partials/header.php';
?>

<div class="container-fluid py-4">
  <div class="row justify-content-center">
    <div class="col-12">
      <?php require __DIR__ . '/../partials/form-header.php'; ?>
      
      <div class="card-body">
        <form id="guaranteePageForm">
          <div class="alert alert-danger d-none" id="guaranteeFormError"></div>
          
          <input type="hidden" id="guaranteeId" value="<?= (int)$TeminatId ?>">
          <input type="hidden" id="guaranteeMusteriId" value="<?= (int)$MusteriId ?>">
          
          <div class="row mb-3">
            <label class="col-4 col-form-label">Proje <span class="text-danger">*</span></label>
            <div class="col-8">
              <select class="form-select" id="guaranteeProjeId" required>
                <option value="">Proje Seçiniz...</option>
              </select>
            </div>
          </div>
          
          <div class="row mb-3">
            <label class="col-4 col-form-label">Teminat Türü <span class="text-danger">*</span></label>
            <div class="col-8">
              <select class="form-select" id="guaranteeTur" required>
                <option value="">Teminat Türü Seçiniz...</option>
              </select>
            </div>
          </div>
          
          <div class="row mb-3">
            <label class="col-4 col-form-label">Tutar <span class="text-danger">*</span></label>
            <div class="col-8">
              <div class="input-group">
                <input type="text" class="form-control price__input nbt-money-input" id="guaranteeTutar" placeholder="0.00" value="0.00" required>
                <select class="form-select" id="guaranteeDoviz" style="max-width: 80px;">
                  <option value="TL" selected>TL</option>
                  <option value="USD">USD</option>
                  <option value="EUR">EUR</option>
                </select>
              </div>
            </div>
          </div>
          
          <div class="row mb-3">
            <label class="col-4 col-form-label">Termin Tarihi <span class="text-danger">*</span></label>
            <div class="col-8">
              <input type="date" class="form-control" id="guaranteeTerminTarihi" required>
            </div>
          </div>
          
          <div class="row mb-3">
            <label class="col-4 col-form-label">Banka/Kurum</label>
            <div class="col-8">
              <input type="text" class="form-control" id="guaranteeBanka">
            </div>
          </div>
          
          <div class="row mb-3">
            <label class="col-4 col-form-label">Durum</label>
            <div class="col-8">
              <select class="form-select" id="guaranteeDurum">
                <option value="Aktif">Aktif</option>
                <option value="İade Edildi">İade Edildi</option>
                <option value="Nakde Çevrildi">Nakde Çevrildi</option>
                <option value="Süresi Doldu">Süresi Doldu</option>
              </select>
            </div>
          </div>
          
          <div class="row mb-3">
            <label class="col-4 col-form-label">Notlar</label>
            <div class="col-8">
              <textarea class="form-control" id="guaranteeNotlar" rows="2"></textarea>
            </div>
          </div>

          <div class="row mb-3">
            <label class="col-4 col-form-label">Dosya</label>
            <div class="col-8">
              <input type="file" class="form-control" id="guaranteeDosyaPage" accept=".pdf,.doc,.docx">
              <div class="form-text text-muted">PDF veya Word (PDF, DOC, DOCX). Maksimum 10MB.</div>
              <div class="invalid-feedback" id="guaranteeDosyaErrorPage"></div>
              <input type="hidden" id="guaranteeRemoveFile" value="0">
              <div class="mt-2 d-none" id="guaranteeCurrentFilePage">
                <span class="badge bg-secondary"><i class="bi bi-file-earmark-text me-1"></i><span id="guaranteeCurrentFileNamePage" class="u-break-anywhere"></span></span>
                <button type="button" class="btn btn-sm btn-outline-danger ms-2" id="btnRemoveGuaranteeFilePage"><i class="bi bi-x"></i></button>
              </div>
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

<?php

$MusteriId = $MusteriId ?? 0;
$DosyaId = $DosyaId ?? 0;
$IsEdit = $DosyaId > 0;

$pageTitle = $IsEdit ? 'Dosya Düzenle' : 'Yeni Dosya Yükle';
$activeNav = 'customers';
$currentPage = 'file-form';

$FormMusteriId = $MusteriId;
$FormTabKey = 'dosyalar';
$FormTitle = $pageTitle;
$FormIcon = 'bi-folder';
$FormBreadcrumb = $pageTitle;
$FormSaveButtonId = 'btnSaveFilePage';
$FormPermission = 'files.create,files.update';
$FormButtonColor = 'primary';

require __DIR__ . '/../partials/header.php';
?>

<div class="container-fluid py-4">
  <div class="row justify-content-center">
    <div class="col-12">
      <?php require __DIR__ . '/../partials/form-header.php'; ?>

      <div class="card-body">
        <form id="filePageForm" enctype="multipart/form-data">
          <div class="alert alert-danger d-none" id="fileFormError"></div>

          <input type="hidden" id="fileId" value="<?= (int)$DosyaId ?>">
          <input type="hidden" id="fileMusteriId" value="<?= (int)$MusteriId ?>">

          <div class="row mb-3">
            <label class="col-4 col-form-label">Proje</label>
            <div class="col-8">
              <select class="form-select" id="fileProjeId">
                <option value="">Proje Seçiniz (Opsiyonel)...</option>
              </select>
            </div>
          </div>

          <?php if (!$IsEdit): ?>
          <div class="row mb-3">
            <label class="col-4 col-form-label">Dosya <span class="text-danger">*</span></label>
            <div class="col-8">
              <input type="file" class="form-control" id="fileUpload" required>
              <small class="text-muted">Max 10MB, tüm dosya türleri kabul edilir.</small>
            </div>
          </div>
          <?php else: ?>
          <div class="row mb-3">
            <label class="col-4 col-form-label">Mevcut Dosya</label>
            <div class="col-8">
              <div class="alert alert-info mb-0" id="fileCurrentInfo">
                <i class="bi bi-file-earmark"></i> <span id="fileCurrentName">Yükleniyor...</span>
              </div>
            </div>
          </div>
          <div class="row mb-3">
            <label class="col-4 col-form-label">Dosya Değiştir</label>
            <div class="col-8">
              <input type="file" class="form-control" id="fileUpload">
              <small class="text-muted">Opsiyonel. Max 10MB, tüm dosya türleri kabul edilir.</small>
            </div>
          </div>
          <?php endif; ?>

          <div class="row mb-3">
            <label class="col-4 col-form-label">Açıklama</label>
            <div class="col-8">
              <input type="text" class="form-control" id="fileAciklama" placeholder="Dosya hakkında kısa açıklama">
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
  NbtPageForm.init('file', <?= (int)$MusteriId ?>, <?= (int)$DosyaId ?>, 'dosyalar');
});
</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>

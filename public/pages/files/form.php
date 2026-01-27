<?php
/**
 * Dosya Form Sayfası - Ekle/Düzenle
 * URL: /customer/{id}/files/new veya /customer/{id}/files/{fileId}/edit
 */

$MusteriId = $MusteriId ?? 0;
$DosyaId = $DosyaId ?? 0;
$IsEdit = $DosyaId > 0;

$pageTitle = $IsEdit ? 'Dosya Düzenle' : 'Yeni Dosya Yükle';
$activeNav = 'customers';
$currentPage = 'file-form';

// Form partial değişkenleri
$FormMusteriId = $MusteriId;
$FormTabKey = 'dosyalar';
$FormTitle = $pageTitle;
$FormIcon = 'bi-folder';
$FormColor = 'dark';
$FormBreadcrumb = $pageTitle;
$FormSaveButtonId = 'btnSaveFilePage';
$FormPermission = 'files.create,files.update';
$FormButtonColor = 'dark';

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
            <label class="col-12 col-md-3 col-form-label">Proje</label>
            <div class="col-12 col-md-9">
              <select class="form-select" id="fileProjeId">
                <option value="">Proje Seçiniz (Opsiyonel)...</option>
              </select>
            </div>
          </div>
          
          <div class="row mb-3">
            <label class="col-12 col-md-3 col-form-label">Dosya Kategorisi</label>
            <div class="col-12 col-md-9">
              <select class="form-select" id="fileKategori">
                <option value="">Kategori Seçiniz...</option>
                <option value="Sözleşme">Sözleşme</option>
                <option value="Teklif">Teklif</option>
                <option value="Fatura">Fatura</option>
                <option value="Teminat">Teminat</option>
                <option value="Teknik Döküman">Teknik Döküman</option>
                <option value="Rapor">Rapor</option>
                <option value="Yazışma">Yazışma</option>
                <option value="Diğer">Diğer</option>
              </select>
            </div>
          </div>
          
          <?php if (!$IsEdit): ?>
          <div class="row mb-3">
            <label class="col-12 col-md-3 col-form-label">Dosya <span class="text-danger">*</span></label>
            <div class="col-12 col-md-9">
              <input type="file" class="form-control" id="fileUpload" required>
              <small class="text-muted">Max 10MB, İzin verilen: PDF, DOC, DOCX, XLS, XLSX, JPG, PNG, ZIP</small>
            </div>
          </div>
          <?php else: ?>
          <div class="row mb-3">
            <label class="col-12 col-md-3 col-form-label">Mevcut Dosya</label>
            <div class="col-12 col-md-9">
              <div class="alert alert-info mb-0" id="fileCurrentInfo">
                <i class="bi bi-file-earmark"></i> <span id="fileCurrentName">Yükleniyor...</span>
              </div>
            </div>
          </div>
          <?php endif; ?>
          
          <div class="row mb-3">
            <label class="col-12 col-md-3 col-form-label">Dosya Adı</label>
            <div class="col-12 col-md-9">
              <input type="text" class="form-control" id="fileName" placeholder="Boş bırakılırsa orijinal dosya adı kullanılır">
            </div>
          </div>
          
          <div class="row mb-3">
            <label class="col-12 col-md-3 col-form-label">Açıklama</label>
            <div class="col-12 col-md-9">
              <textarea class="form-control" id="fileAciklama" rows="2"></textarea>
            </div>
          </div>
          
          <div class="row mb-3">
            <label class="col-12 col-md-3 col-form-label">Etiketler</label>
            <div class="col-12 col-md-9">
              <input type="text" class="form-control" id="fileEtiketler" placeholder="Virgülle ayırarak yazın">
              <small class="text-muted">Örn: sözleşme, imzalı, 2024</small>
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

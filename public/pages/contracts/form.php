<?php
/**
 * Sözleşme Form Sayfası - Ekle/Düzenle
 * URL: /customer/{id}/contracts/new veya /customer/{id}/contracts/{contractId}/edit
 * 
 * Modal yerine tam sayfa form.
 * Kaydetme sonrası müşteri detay sayfasındaki sözleşmeler tabına yönlendirme.
 */

$MusteriId = $MusteriId ?? 0;
$SozlesmeId = $SozlesmeId ?? 0;
$IsEdit = $SozlesmeId > 0;

$pageTitle = $IsEdit ? 'Sözleşme Düzenle' : 'Yeni Sözleşme';
$activeNav = 'customers';
$currentPage = 'contract-form';

require __DIR__ . '/../partials/header.php';
?>

<div class="container-fluid py-4">
  <div class="row justify-content-center">
    <div class="col-12">
      <!-- Breadcrumb ve Geri Butonu -->
      <div class="d-flex justify-content-between align-items-center mb-4">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="/customer/<?= (int)$MusteriId ?>">Müşteri</a></li>
            <li class="breadcrumb-item active"><?= $IsEdit ? 'Sözleşme Düzenle' : 'Yeni Sözleşme' ?></li>
          </ol>
        </nav>
        <a href="/customer/<?= (int)$MusteriId ?>?tab=sozlesmeler" class="btn btn-outline-secondary">
          <i class="bi bi-arrow-left me-1"></i>Listeye Dön
        </a>
      </div>

      <!-- Form Card -->
      <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
          <h5 class="card-title mb-0">
            <i class="bi bi-file-text me-2"></i><?= $IsEdit ? 'Sözleşme Düzenle' : 'Yeni Sözleşme' ?>
          </h5>
        </div>
        <div class="card-body">
          <form id="contractPageForm" enctype="multipart/form-data">
            <div class="alert alert-danger d-none" id="contractFormError"></div>
            
            <input type="hidden" id="contractId" value="<?= (int)$SozlesmeId ?>">
            <input type="hidden" id="contractMusteriId" value="<?= (int)$MusteriId ?>">
            
            <div class="row mb-3">
              <label class="col-4 col-form-label">Proje <span class="text-danger">*</span></label>
              <div class="col-8">
                <select class="form-select" id="contractProjeId" required>
                  <option value="">Proje Seçiniz...</option>
                </select>
                <div class="invalid-feedback"></div>
              </div>
            </div>
            
            <div class="row mb-3">
              <label class="col-4 col-form-label">Sözleşme Tarihi</label>
              <div class="col-8">
                <input type="date" class="form-control" id="contractStart" value="<?= date('Y-m-d') ?>">
              </div>
            </div>
            
            <div class="row mb-3">
              <label class="col-4 col-form-label">Tutar <span class="text-danger">*</span></label>
              <div class="col-8">
                <div class="input-group">
                  <input type="text" class="form-control nbt-money-input" id="contractAmount" placeholder="0,00" required value="0,00">
                  <select class="form-select" id="contractCurrency" style="max-width: 100px;">
                    <!-- Dinamik olarak doldurulacak -->
                  </select>
                </div>
                <div class="invalid-feedback"></div>
              </div>
            </div>
            
            <div class="row mb-3">
              <label class="col-4 col-form-label">Durum</label>
              <div class="col-8">
                <select class="form-select" id="contractStatus">
                  <!-- Dinamik olarak doldurulacak -->
                </select>
              </div>
            </div>
            
            <div class="row mb-3">
              <label class="col-4 col-form-label">Sözleşme Dosyası</label>
              <div class="col-8">
                <input type="file" class="form-control" id="contractDosya" accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document">
                <div class="form-text text-muted">PDF veya Word (PDF, DOC, DOCX). Maksimum 10MB.</div>
                <div class="invalid-feedback" id="contractDosyaError"></div>
                <div class="mt-2 d-none" id="contractCurrentFile">
                  <div class="d-flex flex-wrap align-items-center gap-2">
                    <span class="badge bg-secondary">
                      <i class="bi bi-file-earmark-text me-1"></i>
                      <span id="contractCurrentFileName" class="u-break-anywhere"></span>
                    </span>
                    <a href="#" class="btn btn-sm btn-outline-primary" id="contractCurrentFileDownload" target="_blank" rel="noopener">
                      <i class="bi bi-download"></i> İndir
                    </a>
                    <button type="button" class="btn btn-sm btn-outline-danger" id="btnRemoveContractFile">
                      <i class="bi bi-x"></i> Sil
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </form>
        </div>
        <div class="card-footer d-flex justify-content-between">
          <a href="/customer/<?= (int)$MusteriId ?>?tab=sozlesmeler" class="btn btn-secondary">
            <i class="bi bi-x-lg me-1"></i>İptal
          </a>
          <button type="button" class="btn btn-primary" id="btnSaveContractPage" data-can-any="contracts.create,contracts.update">
            <i class="bi bi-check-lg me-1"></i>Kaydet
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const MusteriId = <?= (int)$MusteriId ?>;
  const SozlesmeId = <?= (int)$SozlesmeId ?>;
  const IsEdit = SozlesmeId > 0;
  
  // Form initialization
  NbtContractPageForm.init(MusteriId, SozlesmeId);
});
</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>

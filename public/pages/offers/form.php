<?php
/**
 * Teklif Form Sayfası - Ekle/Düzenle
 * URL: /customer/{id}/offers/new veya /customer/{id}/offers/{offerId}/edit
 * 
 * Modal yerine tam sayfa form.
 * Kaydetme sonrası müşteri detay sayfasındaki teklifler tabına yönlendirme.
 */

$MusteriId = $MusteriId ?? 0;
$TeklifId = $TeklifId ?? 0;
$IsEdit = $TeklifId > 0;

$pageTitle = $IsEdit ? 'Teklif Düzenle' : 'Yeni Teklif';
$activeNav = 'customers';
$currentPage = 'offer-form';

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
            <li class="breadcrumb-item active"><?= $IsEdit ? 'Teklif Düzenle' : 'Yeni Teklif' ?></li>
          </ol>
        </nav>
        <a href="/customer/<?= (int)$MusteriId ?>?tab=teklifler" class="btn btn-outline-secondary">
          <i class="bi bi-arrow-left me-1"></i>Listeye Dön
        </a>
      </div>

      <!-- Form Card -->
      <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
          <h5 class="card-title mb-0">
            <i class="bi bi-file-earmark-text me-2"></i><?= $IsEdit ? 'Teklif Düzenle' : 'Yeni Teklif' ?>
          </h5>
        </div>
        <div class="card-body">
          <form id="offerPageForm" enctype="multipart/form-data">
            <div class="alert alert-danger d-none" id="offerFormError"></div>
            
            <input type="hidden" id="offerId" value="<?= (int)$TeklifId ?>">
            <input type="hidden" id="offerMusteriId" value="<?= (int)$MusteriId ?>">
            
            <div class="row mb-3">
              <label class="col-4 col-form-label">Proje <span class="text-danger">*</span></label>
              <div class="col-8">
                <select class="form-select" id="offerProjeId" required>
                  <option value="">Proje Seçiniz...</option>
                </select>
                <div class="invalid-feedback"></div>
              </div>
            </div>
            
            <div class="row mb-3">
              <label class="col-4 col-form-label">Konu</label>
              <div class="col-8">
                <input type="text" class="form-control" id="offerSubject" placeholder="Teklif konusu">
              </div>
            </div>
            
            <div class="row mb-3">
              <label class="col-4 col-form-label">Tutar <span class="text-danger">*</span></label>
              <div class="col-8">
                <div class="input-group">
                  <input type="text" class="form-control nbt-money-input" id="offerAmount" placeholder="0,00" required value="0,00">
                  <select class="form-select" id="offerCurrency" style="max-width: 100px;">
                    <!-- Dinamik olarak doldurulacak -->
                  </select>
                </div>
                <div class="invalid-feedback"></div>
              </div>
            </div>
            
            <div class="row mb-3">
              <label class="col-4 col-form-label">Teklif Tarihi <span class="text-danger">*</span></label>
              <div class="col-8">
                <input type="date" class="form-control" id="offerDate" value="<?= date('Y-m-d') ?>" required>
                <div class="invalid-feedback"></div>
              </div>
            </div>
            
            <div class="row mb-3">
              <label class="col-4 col-form-label">Geçerlilik Tarihi <span class="text-danger">*</span></label>
              <div class="col-8">
                <input type="date" class="form-control" id="offerValidDate" required>
                <div class="invalid-feedback"></div>
              </div>
            </div>
            
            <div class="row mb-3">
              <label class="col-4 col-form-label">Durum</label>
              <div class="col-8">
                <select class="form-select" id="offerStatus">
                  <!-- Dinamik olarak doldurulacak -->
                </select>
              </div>
            </div>
            
            <div class="row mb-3">
              <label class="col-4 col-form-label">Dosya</label>
              <div class="col-8">
                <input type="file" class="form-control" id="offerDosya" accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document">
                <div class="form-text text-muted">PDF veya Word dosyası (.pdf, .doc, .docx). Maksimum 10MB.</div>
                <div class="invalid-feedback" id="offerDosyaError"></div>
                <div class="mt-2 d-none" id="offerCurrentFile">
                  <span class="badge bg-secondary me-2">
                    <i class="bi bi-file-earmark me-1" id="offerCurrentFileIcon"></i>
                    <span id="offerCurrentFileName"></span>
                  </span>
                  <button type="button" class="btn btn-sm btn-outline-danger" id="btnRemoveOfferFile">
                    <i class="bi bi-x"></i> Sil
                  </button>
                </div>
              </div>
            </div>
          </form>
        </div>
        <div class="card-footer d-flex justify-content-between">
          <a href="/customer/<?= (int)$MusteriId ?>?tab=teklifler" class="btn btn-secondary">
            <i class="bi bi-x-lg me-1"></i>İptal
          </a>
          <button type="button" class="btn btn-primary" id="btnSaveOfferPage" data-can-any="offers.create,offers.update">
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
  const TeklifId = <?= (int)$TeklifId ?>;
  const IsEdit = TeklifId > 0;
  
  // Form initialization
  NbtOfferPageForm.init(MusteriId, TeklifId);
});
</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>

<?php
/**
 * Müşteri Form Sayfası - Ekle/Düzenle
 * URL: /customer/new veya /customer/{id}/edit
 * 
 * Modal yerine tam sayfa form.
 * files/form.php ile aynı pattern kullanılıyor.
 */

$MusteriId = $MusteriId ?? 0;
$IsEdit = $MusteriId > 0;

$pageTitle = $IsEdit ? 'Müşteri Düzenle' : 'Yeni Müşteri';
$activeNav = 'customers';
$currentPage = 'customer-form';

require __DIR__ . '/../partials/header.php';
?>

<div class="container-fluid py-4">
  <div class="row justify-content-center">
    <div class="col-12">
      <!-- Breadcrumb ve Geri Butonu -->
      <div class="d-flex justify-content-between align-items-center mb-4">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="/dashboard">Ana Sayfa</a></li>
            <li class="breadcrumb-item active"><?= $IsEdit ? 'Müşteri Düzenle' : 'Yeni Müşteri' ?></li>
          </ol>
        </nav>
        <?php if ($IsEdit): ?>
        <a href="/customer/<?= (int)$MusteriId ?>" class="btn btn-outline-secondary">
          <i class="bi bi-arrow-left me-1"></i>Müşteriye Dön
        </a>
        <?php else: ?>
        <a href="/dashboard" class="btn btn-outline-secondary">
          <i class="bi bi-arrow-left me-1"></i>Geri
        </a>
        <?php endif; ?>
      </div>

      <!-- Form Card -->
      <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
          <h5 class="card-title mb-0">
            <i class="bi bi-building me-2"></i><?= $IsEdit ? 'Müşteri Düzenle' : 'Yeni Müşteri' ?>
          </h5>
        </div>
        <div class="card-body">
          <form id="customerPageForm">
            <div class="alert alert-danger d-none" id="customerFormError"></div>
            
            <input type="hidden" id="customerId" value="<?= (int)$MusteriId ?>">
            
            <!-- Temel Bilgiler -->
            <div class="row mb-3">
              <label class="col-4 col-form-label">Ünvan <span class="text-danger">*</span></label>
              <div class="col-8">
                <input type="text" class="form-control" id="customerUnvan" maxlength="150" required>
                <small class="form-hint">Zorunlu, Min 2, Maks 150</small>
                <div class="invalid-feedback"></div>
              </div>
            </div>
            <div class="row mb-3">
              <label class="col-4 col-form-label">Müşteri Kodu</label>
              <div class="col-8">
                <input type="text" class="form-control" id="customerMusteriKodu" maxlength="10" placeholder="Örn: ABC1234567" style="text-transform: uppercase;">
                <small class="form-hint">Maks 10 karakter</small>
                <div class="invalid-feedback"></div>
              </div>
            </div>
            
            <!-- Vergi Bilgileri -->
            <div class="row mb-3">
              <label class="col-4 col-form-label">Vergi Dairesi <span class="text-danger">*</span></label>
              <div class="col-8">
                <input type="text" class="form-control" id="customerVergiDairesi" maxlength="50" required>
                <small class="form-hint">Zorunlu, Maks 50</small>
                <div class="invalid-feedback"></div>
              </div>
            </div>
            <div class="row mb-3">
              <label class="col-4 col-form-label">Vergi Numarası <span class="text-danger">*</span></label>
              <div class="col-8">
                <input type="text" class="form-control number__input" id="customerVergiNo" maxlength="11" placeholder="10 veya 11 haneli" required>
                <small class="form-hint">Zorunlu, 10-11 hane, Sayısal</small>
                <div class="invalid-feedback"></div>
              </div>
            </div>
            <div class="row mb-3">
              <label class="col-4 col-form-label">Mersis No</label>
              <div class="col-8">
                <input type="text" class="form-control number__input" id="customerMersisNo" maxlength="16" placeholder="16 haneli Mersis No">
                <small class="form-hint">Maks 16 hane</small>
                <div class="invalid-feedback"></div>
              </div>
            </div>
            
            <!-- İletişim Bilgileri -->
            <div class="row mb-3">
              <label class="col-4 col-form-label">Telefon</label>
              <div class="col-8">
                <input type="text" class="form-control number__input" id="customerTelefon" maxlength="20" placeholder="5xx xxx xx xx">
                <small class="form-hint">Maks 20 karakter</small>
                <div class="invalid-feedback"></div>
              </div>
            </div>
            <div class="row mb-3">
              <label class="col-4 col-form-label">Faks</label>
              <div class="col-8">
                <input type="text" class="form-control number__input" id="customerFaks" maxlength="20">
                <small class="form-hint">Maks 20 karakter</small>
                <div class="invalid-feedback"></div>
              </div>
            </div>
            <div class="row mb-3">
              <label class="col-4 col-form-label">Web Sitesi</label>
              <div class="col-8">
                <input type="url" class="form-control" id="customerWeb" maxlength="150" placeholder="https://www.example.com">
                <small class="form-hint">Maks 150 karakter</small>
                <div class="invalid-feedback"></div>
              </div>
            </div>
            
            <!-- Adres Bilgileri -->
            <div class="row mb-3">
              <label class="col-4 col-form-label">İl</label>
              <div class="col-8">
                <select class="form-select" id="customerSehirId">
                  <option value="">İl Seçiniz...</option>
                </select>
                <div class="invalid-feedback"></div>
              </div>
            </div>
            <div class="row mb-3">
              <label class="col-4 col-form-label">İlçe</label>
              <div class="col-8">
                <select class="form-select" id="customerIlceId" disabled>
                  <option value="">Önce il seçiniz...</option>
                </select>
                <div class="invalid-feedback"></div>
              </div>
            </div>
            <div class="row mb-3">
              <label class="col-4 col-form-label">Adres</label>
              <div class="col-8">
                <textarea class="form-control" id="customerAdres" rows="2" maxlength="300"></textarea>
                <small class="form-hint">Maks 300 karakter</small>
                <div class="invalid-feedback"></div>
              </div>
            </div>
            <div class="row mb-3">
              <label class="col-4 col-form-label">Açıklama</label>
              <div class="col-8">
                <textarea class="form-control" id="customerAciklama" rows="2" maxlength="500"></textarea>
                <small class="form-hint">Maks 500 karakter</small>
                <div class="invalid-feedback"></div>
              </div>
            </div>
          </form>
        </div>
        <div class="card-footer d-flex justify-content-between">
          <?php if ($IsEdit): ?>
          <a href="/customer/<?= (int)$MusteriId ?>" class="btn btn-secondary">
            <i class="bi bi-x-lg me-1"></i>İptal
          </a>
          <?php else: ?>
          <a href="/dashboard" class="btn btn-secondary">
            <i class="bi bi-x-lg me-1"></i>İptal
          </a>
          <?php endif; ?>
          <button type="button" class="btn btn-primary" id="btnSaveCustomerPage" data-can-any="customers.create,customers.update">
            <i class="bi bi-check-lg me-1"></i>Kaydet
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  NbtCustomerPageForm.init(<?= (int)$MusteriId ?>);
});
</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>

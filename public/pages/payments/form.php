<?php
/**
 * Ödeme Form Sayfası - Ekle/Düzenle
 * URL: /customer/{id}/payments/new veya /customer/{id}/payments/{paymentId}/edit
 */

$MusteriId = $MusteriId ?? 0;
$OdemeId = $OdemeId ?? 0;
$IsEdit = $OdemeId > 0;

$pageTitle = $IsEdit ? 'Ödeme Düzenle' : 'Yeni Ödeme';
$activeNav = 'customers';
$currentPage = 'payment-form';

// Form partial değişkenleri
$FormMusteriId = $MusteriId;
$FormTabKey = 'odemeler';
$FormTitle = $pageTitle;
$FormIcon = 'bi-cash-stack';
$FormBreadcrumb = $pageTitle;
$FormSaveButtonId = 'btnSavePaymentPage';
$FormPermission = 'payments.create,payments.update';
$FormButtonColor = 'primary';

require __DIR__ . '/../partials/header.php';
?>

<div class="container-fluid py-4">
  <div class="row justify-content-center">
    <div class="col-12">
      <?php require __DIR__ . '/../partials/form-header.php'; ?>
      
      <div class="card-body">
        <form id="paymentPageForm">
          <div class="alert alert-danger d-none" id="paymentFormError"></div>
          
          <input type="hidden" id="paymentId" value="<?= (int)$OdemeId ?>">
          <input type="hidden" id="paymentMusteriId" value="<?= (int)$MusteriId ?>">
          
          <div class="row mb-3">
            <label class="col-4 col-form-label">Proje <span class="text-danger">*</span></label>
            <div class="col-8">
              <select class="form-select" id="paymentProjeId" required>
                <option value="">Proje Seçiniz...</option>
              </select>
            </div>
          </div>
          
          <div class="row mb-3">
            <label class="col-4 col-form-label">Fatura <span class="text-danger">*</span></label>
            <div class="col-8">
              <select class="form-select" id="paymentFaturaId" required>
                <option value="">Fatura Seçiniz...</option>
              </select>
              <small class="text-muted">Sadece bakiyesi olan faturalar listelenir</small>
            </div>
          </div>
          
          <div class="row mb-3">
            <label class="col-4 col-form-label">Ödeme Tarihi <span class="text-danger">*</span></label>
            <div class="col-8">
              <input type="date" class="form-control" id="paymentTarih" value="<?= date('Y-m-d') ?>" required>
            </div>
          </div>
          
          <div class="row mb-3">
            <label class="col-4 col-form-label">Tutar <span class="text-danger">*</span></label>
            <div class="col-8">
              <div class="input-group">
                <input type="text" class="form-control price__input nbt-money-input" id="paymentTutar" placeholder="0.00" value="0.00" required>
                <select class="form-select" id="paymentDoviz" style="max-width: 80px;">
                  <option value="TL" selected>TL</option>
                  <option value="USD">USD</option>
                  <option value="EUR">EUR</option>
                </select>
              </div>
            </div>
          </div>
          
          <div class="row mb-3">
            <label class="col-4 col-form-label">Ödeme Türü <span class="text-danger">*</span></label>
            <div class="col-8">
              <select class="form-select" id="paymentTur" required>
                <option value="">Ödeme Türü Seçiniz...</option>
                <option value="Nakit">Nakit</option>
                <option value="Havale/EFT">Havale/EFT</option>
                <option value="Kredi Kartı">Kredi Kartı</option>
                <option value="Çek">Çek</option>
                <option value="Senet">Senet</option>
                <option value="Diğer">Diğer</option>
              </select>
            </div>
          </div>
          
          <div class="row mb-3">
            <label class="col-4 col-form-label">Banka/Hesap</label>
            <div class="col-8">
              <input type="text" class="form-control" id="paymentBanka">
            </div>
          </div>
          
          <div class="row mb-3">
            <label class="col-4 col-form-label">Referans No</label>
            <div class="col-8">
              <input type="text" class="form-control" id="paymentReferans" placeholder="Dekont/Çek/Senet No">
            </div>
          </div>
          
          <div class="row mb-3">
            <label class="col-4 col-form-label">Notlar</label>
            <div class="col-8">
              <textarea class="form-control" id="paymentNotlar" rows="2"></textarea>
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
  NbtPageForm.init('payment', <?= (int)$MusteriId ?>, <?= (int)$OdemeId ?>, 'odemeler');
});
</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>

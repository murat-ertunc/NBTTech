<?php
/**
 * Fatura Form Sayfası - Ekle/Düzenle
 * URL: /customer/{id}/invoices/new veya /customer/{id}/invoices/{invoiceId}/edit
 */

$MusteriId = $MusteriId ?? 0;
$FaturaId = $FaturaId ?? 0;
$IsEdit = $FaturaId > 0;

$pageTitle = $IsEdit ? 'Fatura Düzenle' : 'Yeni Fatura';
$activeNav = 'customers';
$currentPage = 'invoice-form';

// Form partial değişkenleri
$FormMusteriId = $MusteriId;
$FormTabKey = 'faturalar';
$FormTitle = $pageTitle;
$FormIcon = 'bi-receipt';
$FormColor = 'primary';
$FormBreadcrumb = $pageTitle;
$FormSaveButtonId = 'btnSaveInvoicePage';
$FormPermission = 'invoices.create,invoices.update';
$FormButtonColor = 'primary';

require __DIR__ . '/../partials/header.php';
?>

<div class="container-fluid py-4">
  <div class="row justify-content-center">
    <div class="col-12 col-lg-10 col-xl-8">
      <?php require __DIR__ . '/../partials/form-header.php'; ?>
      
      <div class="card-body">
        <form id="invoicePageForm">
          <div class="alert alert-danger d-none" id="invoiceFormError"></div>
          
          <input type="hidden" id="invoiceId" value="<?= (int)$FaturaId ?>">
          <input type="hidden" id="invoiceMusteriId" value="<?= (int)$MusteriId ?>">
          
          <!-- Temel Bilgiler -->
          <div class="row g-3 mb-3">
            <div class="col-12 col-md-4">
              <label class="form-label">Proje <span class="text-danger">*</span></label>
              <select class="form-select" id="invoiceProjeId" required>
                <option value="">Proje Seçiniz...</option>
              </select>
            </div>
            <div class="col-12 col-md-4">
              <label class="form-label">Sözleşme</label>
              <select class="form-select" id="invoiceSozlesmeId">
                <option value="">Sözleşme Seçiniz...</option>
              </select>
            </div>
            <div class="col-12 col-md-4">
              <label class="form-label">Fatura Türü</label>
              <select class="form-select" id="invoiceTur">
                <option value="Satış" selected>Satış</option>
                <option value="İade">İade</option>
                <option value="Hakediş">Hakediş</option>
                <option value="Diğer">Diğer</option>
              </select>
            </div>
          </div>
          
          <!-- Fatura Bilgileri -->
          <div class="row g-3 mb-3">
            <div class="col-12 col-md-6">
              <label class="form-label">Fatura No <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="invoiceFaturaNo" required>
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label">Fatura Tarihi <span class="text-danger">*</span></label>
              <input type="date" class="form-control" id="invoiceFaturaTarihi" value="<?= date('Y-m-d') ?>" required>
            </div>
          </div>
          
          <!-- Tutar Bilgileri -->
          <div class="row g-3 mb-3">
            <div class="col-12 col-md-4">
              <label class="form-label">Toplam Tutar <span class="text-danger">*</span></label>
              <div class="input-group">
                <input type="text" class="form-control" id="invoiceToplamTutar" required>
                <select class="form-select" id="invoiceDoviz" style="max-width: 80px;">
                  <option value="TL" selected>TL</option>
                  <option value="USD">USD</option>
                  <option value="EUR">EUR</option>
                </select>
              </div>
            </div>
            <div class="col-12 col-md-4">
              <label class="form-label">KDV Oranı</label>
              <select class="form-select" id="invoiceKdvOrani">
                <option value="0">%0</option>
                <option value="1">%1</option>
                <option value="8">%8</option>
                <option value="10">%10</option>
                <option value="18">%18</option>
                <option value="20" selected>%20</option>
              </select>
            </div>
            <div class="col-12 col-md-4">
              <label class="form-label">Vade Tarihi</label>
              <input type="date" class="form-control" id="invoiceVadeTarihi">
            </div>
          </div>
          
          <!-- Durum Bilgileri -->
          <div class="row g-3 mb-3">
            <div class="col-12 col-md-6">
              <label class="form-label">Ödeme Durumu</label>
              <select class="form-select" id="invoiceOdemeDurumu">
                <option value="Ödenmedi" selected>Ödenmedi</option>
                <option value="Kısmi">Kısmi Ödendi</option>
                <option value="Ödendi">Ödendi</option>
              </select>
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label">Fatura Durumu</label>
              <select class="form-select" id="invoiceDurum">
                <option value="Aktif" selected>Aktif</option>
                <option value="İptal">İptal</option>
                <option value="İade">İade</option>
              </select>
            </div>
          </div>
          
          <!-- Fatura Kalemleri -->
          <div class="mb-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <label class="form-label mb-0">Fatura Kalemleri</label>
              <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddInvoiceItem">
                <i class="bi bi-plus-lg"></i> Kalem Ekle
              </button>
            </div>
            <div class="table-responsive">
              <table class="table table-bordered table-sm" id="invoiceItemsTable">
                <thead class="table-light">
                  <tr>
                    <th style="width: 40%;">Açıklama</th>
                    <th style="width: 15%;">Miktar</th>
                    <th style="width: 10%;">Birim</th>
                    <th style="width: 15%;">Birim Fiyat</th>
                    <th style="width: 15%;">Tutar</th>
                    <th style="width: 5%;"></th>
                  </tr>
                </thead>
                <tbody id="invoiceItemsBody">
                  <!-- Dinamik kalemler -->
                </tbody>
              </table>
            </div>
          </div>
          
          <!-- Notlar -->
          <div class="mb-3">
            <label class="form-label">Notlar</label>
            <textarea class="form-control" id="invoiceNotlar" rows="2"></textarea>
          </div>
        </form>
      </div>
      
      <?php require __DIR__ . '/../partials/form-footer.php'; ?>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  NbtPageForm.init('invoice', <?= (int)$MusteriId ?>, <?= (int)$FaturaId ?>, 'faturalar');
});
</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>

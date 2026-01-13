<?php
/**
 * Müşteriler Listesi Sayfası - Server-Rendered
 * URL: /customers
 */

$pageTitle = 'Müşteriler';
$activeNav = 'customers';

require __DIR__ . '/partials/header.php';
?>

    <!-- ===== VIEW: MÜŞTERİLER LİSTESİ ===== -->
    <div id="view-customers">
      <div class="card" id="panelCustomersList">
        <div class="card-header bg-primary text-white py-2 d-flex justify-content-between align-items-center">
          <span class="fw-semibold"><i class="bi bi-people-fill me-2"></i>Müşteriler</span>
          <div class="d-flex gap-1">
            <button type="button" class="btn btn-sm btn-light" data-action="add-customer" title="Yeni Müşteri">
              <i class="bi bi-plus-lg"></i>
            </button>
            <button type="button" class="btn btn-sm btn-light" data-panel-fullscreen="panelCustomersList" title="Tam Ekran">
              <i class="bi bi-arrows-fullscreen"></i>
            </button>
          </div>
        </div>
        <div id="customersToolbar" class="d-none"></div>
        <div id="customersFilterPanel" class="bg-light border-bottom p-2 d-none">
          <div class="row g-2 align-items-end">
            <div class="col-auto">
              <label class="form-label small mb-1">Kayıt Tarihi</label>
              <input type="date" class="form-control form-control-sm" id="filterCustomerDate">
            </div>
            <div class="col-auto">
              <button type="button" class="btn btn-primary btn-sm" id="applyCustomerFilter">Uygula</button>
              <button type="button" class="btn btn-outline-secondary btn-sm" id="clearCustomerFilter">Temizle</button>
            </div>
          </div>
        </div>
        <div class="card-body p-0" id="customersTableContainer">
          <div class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div></div>
        </div>
      </div>
    </div>

<?php require __DIR__ . '/partials/modals.php'; ?>
<?php require __DIR__ . '/partials/footer.php'; ?>

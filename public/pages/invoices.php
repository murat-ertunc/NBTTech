<?php
/**
 * Faturalar Listesi Sayfası - Server-Rendered
 * URL: /invoices
 */

$pageTitle = 'Faturalar';
$activeNav = 'islemler';
$currentPage = 'invoices';

require __DIR__ . '/partials/header.php';
?>

    <!-- ===== VIEW: FATURALAR ===== -->
    <div id="view-invoices">
      <div class="card" id="panelInvoices">
        <div class="card-header bg-primary text-white py-2 d-flex justify-content-between align-items-center">
          <span class="fw-semibold"><i class="bi bi-receipt me-2"></i>Faturalar</span>
          <button type="button" class="btn btn-sm btn-light" data-panel-fullscreen="panelInvoices" title="Tam Ekran">
            <i class="bi bi-arrows-fullscreen"></i>
          </button>
        </div>
        <div id="invoicesToolbar"></div>
        <div class="card-body p-0" id="invoicesTableContainer">
          <div class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div></div>
        </div>
      </div>
    </div>

<?php require __DIR__ . '/partials/modals/invoice.php'; ?>
<?php require __DIR__ . '/partials/modals/entity-detail.php'; ?>
<?php require __DIR__ . '/partials/footer.php'; ?>

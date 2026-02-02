<?php

$pageTitle = 'Ödemeler';
$activeNav = 'islemler';
$currentPage = 'payments';

require __DIR__ . '/partials/header.php';
?>

    <!-- ===== VIEW: ÖDEMELER ===== -->
    <div id="view-payments" data-can="payments.read">
      <div class="card" id="panelPayments">
        <div class="card-header bg-primary text-white py-2 d-flex justify-content-between align-items-center">
          <span class="fw-semibold"><i class="bi bi-cash-stack me-2"></i>Ödemeler</span>
          <button type="button" class="btn btn-sm btn-light" data-panel-fullscreen="panelPayments" title="Tam Ekran">
            <i class="bi bi-arrows-fullscreen"></i>
          </button>
        </div>
        <div id="paymentsToolbar"></div>
        <div class="card-body p-0" id="paymentsTableContainer">
          <div class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div></div>
        </div>
      </div>
    </div>

<?php require __DIR__ . '/partials/modals/payment.php'; ?>
<?php require __DIR__ . '/partials/modals/entity-detail.php'; ?>
<?php require __DIR__ . '/partials/footer.php'; ?>

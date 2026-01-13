<?php
/**
 * Sözleşmeler Listesi Sayfası - Server-Rendered
 * URL: /contracts
 */

$pageTitle = 'Sözleşmeler';
$activeNav = 'islemler';
$currentPage = 'contracts';

require __DIR__ . '/partials/header.php';
?>

    <!-- ===== VIEW: SÖZLEŞMELER ===== -->
    <div id="view-contracts">
      <div class="card" id="panelContracts">
        <div class="card-header bg-primary text-white py-2 d-flex justify-content-between align-items-center">
          <span class="fw-semibold"><i class="bi bi-file-earmark-text me-2"></i>Sözleşmeler</span>
          <button type="button" class="btn btn-sm btn-light" data-panel-fullscreen="panelContracts" title="Tam Ekran">
            <i class="bi bi-arrows-fullscreen"></i>
          </button>
        </div>
        <div id="contractsToolbar"></div>
        <div class="card-body p-0" id="contractsTableContainer">
          <div class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div></div>
        </div>
      </div>
    </div>

<?php require __DIR__ . '/partials/modals.php'; ?>
<?php require __DIR__ . '/partials/footer.php'; ?>

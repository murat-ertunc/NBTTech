<?php
/**
 * Teminatlar Listesi Sayfası - Server-Rendered
 * URL: /guarantees
 */

$pageTitle = 'Teminatlar';
$activeNav = 'islemler';
$currentPage = 'guarantees';

require __DIR__ . '/partials/header.php';
?>

    <!-- ===== VIEW: TEMİNATLAR ===== -->
    <div id="view-guarantees" data-can="guarantees.read">
      <div class="card" id="panelGuarantees">
        <div class="card-header bg-primary text-white py-2 d-flex justify-content-between align-items-center">
          <span class="fw-semibold"><i class="bi bi-shield-check me-2"></i>Teminatlar</span>
          <button type="button" class="btn btn-sm btn-light" data-panel-fullscreen="panelGuarantees" title="Tam Ekran">
            <i class="bi bi-arrows-fullscreen"></i>
          </button>
        </div>
        <div id="guaranteesToolbar"></div>
        <div class="card-body p-0" id="guaranteesTableContainer">
          <div class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div></div>
        </div>
      </div>
    </div>

<?php require __DIR__ . '/partials/modals/guarantee.php'; ?>
<?php require __DIR__ . '/partials/modals/entity-detail.php'; ?>
<?php require __DIR__ . '/partials/footer.php'; ?>

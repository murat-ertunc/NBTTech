<?php





$pageTitle = 'Teklifler';
$activeNav = 'islemler';
$currentPage = 'offers';

require __DIR__ . '/partials/header.php';
?>

    <!-- ===== VIEW: TEKLİFLER ===== -->
    <div id="view-offers" data-can="offers.read">
      <div class="card" id="panelOffers">
        <div class="card-header bg-primary text-white py-2 d-flex justify-content-between align-items-center">
          <span class="fw-semibold"><i class="bi bi-file-text me-2"></i>Teklifler</span>
          <button type="button" class="btn btn-sm btn-light" data-panel-fullscreen="panelOffers" title="Tam Ekran">
            <i class="bi bi-arrows-fullscreen"></i>
          </button>
        </div>
        <div id="offersToolbar"></div>
        <div class="card-body p-0" id="offersTableContainer">
          <div class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div></div>
        </div>
      </div>
    </div>

<?php require __DIR__ . '/partials/modals/offer.php'; ?>
<?php require __DIR__ . '/partials/modals/entity-detail.php'; ?>
<?php require __DIR__ . '/partials/footer.php'; ?>

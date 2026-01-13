<?php
/**
 * Parametreler Sayfası - Server-Rendered
 * URL: /parameters
 * Sistem parametrelerinin yönetimi (döviz, durum badge'leri, pagination vb.)
 */

$pageTitle = 'Parametreler';
$activeNav = 'sistem';
$currentPage = 'parameters';

require __DIR__ . '/partials/header.php';
?>

    <!-- ===== VIEW: PARAMETRELER ===== -->
    <div id="view-parameters">
      <div class="row g-3">
        <!-- Sol Panel: Parametre Grupları -->
        <div class="col-lg-3 col-md-4">
          <div class="card shadow-sm h-100">
            <div class="card-header bg-success text-white py-2">
              <span class="fw-semibold"><i class="bi bi-sliders me-2"></i>Parametre Grupları</span>
            </div>
            <div class="card-body p-0" id="parametersSidebar">
              <div class="text-center py-4"><div class="spinner-border spinner-border-sm text-success"></div></div>
            </div>
          </div>
        </div>

        <!-- Sağ Panel: Parametre Detayları -->
        <div class="col-lg-9 col-md-8">
          <div class="card shadow-sm">
            <div class="card-header bg-primary text-white py-2 d-flex justify-content-between align-items-center">
              <span class="fw-semibold" id="parametersTableTitle"><i class="bi bi-gear me-2"></i>Genel Ayarlar</span>
              <button type="button" class="btn btn-sm btn-light" id="btnAddParameter" style="display:none;">
                <i class="bi bi-plus-lg me-1"></i>Yeni Ekle
              </button>
            </div>
            <div class="card-body p-0" id="parametersTableContainer">
              <div class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div></div>
            </div>
          </div>
        </div>
      </div>
    </div>

<?php require __DIR__ . '/partials/footer.php'; ?>

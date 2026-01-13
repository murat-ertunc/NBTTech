<?php
/**
 * Alarmlar Sayfası - Server-Rendered
 * URL: /alarms
 */

$pageTitle = 'Alarmlar';
$activeNav = 'dashboard';
$currentPage = 'alarms';

require __DIR__ . '/partials/header.php';
?>

    <!-- ===== VIEW: ALARMLAR ===== -->
    <div id="view-alarms">
      <div class="row g-3">
        <!-- Sol Panel: Alarm Listesi -->
        <div class="col-lg-3 col-md-4">
          <div class="card shadow-sm h-100">
            <div class="card-header bg-warning text-dark py-2">
              <span class="fw-semibold"><i class="bi bi-bell-fill me-2"></i>Alarm Listeleri</span>
            </div>
            <div class="card-body p-0" id="alarmsSidebar">
              <div class="text-center py-4"><div class="spinner-border spinner-border-sm text-warning"></div></div>
            </div>
          </div>
        </div>

        <!-- Sağ Panel: Alarm Detayları -->
        <div class="col-lg-9 col-md-8">
          <div class="card shadow-sm">
            <div class="card-header bg-danger text-white py-2 d-flex justify-content-between align-items-center">
              <span class="fw-semibold" id="alarmsTableTitle"><i class="bi bi-receipt me-2"></i>Ödenmemiş Faturalar</span>
            </div>
            <div class="card-body p-0" id="alarmsTableContainer">
              <div class="text-center py-4"><div class="spinner-border spinner-border-sm text-danger"></div></div>
            </div>
          </div>
        </div>
      </div>
    </div>

<?php require __DIR__ . '/partials/footer.php'; ?>

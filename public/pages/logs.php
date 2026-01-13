<?php
/**
 * Loglar Listesi Sayfası - Server-Rendered
 * URL: /logs
 */

$pageTitle = 'İşlem Logları';
$activeNav = 'sistem';
$currentPage = 'logs';

require __DIR__ . '/partials/header.php';
?>

    <!-- ===== VIEW: LOGLAR ===== -->
    <div id="view-logs">
      <div class="card" id="panelLogs">
        <div class="card-header bg-primary text-white py-2">
          <span class="fw-semibold"><i class="bi bi-journal-text me-2"></i>İşlem Logları</span>
        </div>
        <div id="logsToolbar"></div>
        <div class="card-body p-0" id="logsTableContainer">
          <div class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div></div>
        </div>
      </div>
    </div>

<?php require __DIR__ . '/partials/modals.php'; ?>
<?php require __DIR__ . '/partials/footer.php'; ?>

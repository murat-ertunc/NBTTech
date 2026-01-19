<?php
/**
 * Dashboard Sayfası - Server-Rendered
 * URL: /dashboard veya /
 */

$pageTitle = 'Ana Sayfa';
$activeNav = 'dashboard';

require __DIR__ . '/partials/header.php';
?>

    <!-- ===== VIEW: DASHBOARD ===== -->
    <div id="view-dashboard">
      <!-- Dashboard Grid: 2 Panel -->
      <div class="row g-2 mb-3">
        
        <!-- PANEL 1: ALARMLAR -->
        <div class="col-lg-4 col-sm-12">
          <div class="card h-100" id="panelAlarms">
            <div class="card-header bg-warning text-dark py-2 d-flex justify-content-between align-items-center">
              <span class="fw-semibold"><i class="bi bi-bell-fill me-2"></i>Alarmlar</span>
              <span class="badge bg-dark" id="alarmCount">0</span>
            </div>
            <div class="card-body p-2 overflow-auto" id="dashAlarmList" style="max-height:320px;">
              <div class="text-center py-4"><div class="spinner-border spinner-border-sm text-warning"></div></div>
            </div>
          </div>
        </div>

        <!-- PANEL 2: TAKVİM -->
        <div class="col-lg-8 col-sm-12">
          <div class="card h-100" id="panelCalendar">
            <div class="card-header bg-success text-white py-2 d-flex justify-content-between align-items-center">
              <span class="fw-semibold"><i class="bi bi-calendar3 me-2"></i>Takvim</span>
              <div class="btn-group btn-group-sm">
                <button type="button" class="btn btn-light btn-sm active" data-view="month">Ay</button>
                <button type="button" class="btn btn-light btn-sm" data-view="week">Hafta</button>
              </div>
            </div>
            <div class="card-body p-2" id="dashCalendar">
              <div class="text-center py-4"><div class="spinner-border spinner-border-sm text-success"></div></div>
            </div>
          </div>
        </div>

      </div>

      <!-- İstatistik Kartları (Alt Kısım) -->
      <div class="row g-2">
        <div class="col-6 col-md-3">
          <div class="card h-100 border-0 shadow-sm">
            <div class="card-body py-3 px-3 text-center">
              <div class="fs-3 fw-bold text-primary" id="statCustomers">0</div>
              <small class="text-muted text-uppercase fw-semibold">Toplam Müşteri</small>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="card h-100 border-0 shadow-sm">
            <div class="card-body py-3 px-3 text-center">
              <div class="fs-3 fw-bold text-info" id="statProjects">0</div>
              <small class="text-muted text-uppercase fw-semibold">Aktif Projeler</small>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="card h-100 border-0 shadow-sm">
            <div class="card-body py-3 px-3 text-center">
              <div class="fs-3 fw-bold text-danger" id="statPending">0 ₺</div>
              <small class="text-muted text-uppercase fw-semibold">Bekleyen Tahsilat</small>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="card h-100 border-0 shadow-sm">
            <div class="card-body py-3 px-3 text-center">
              <div class="fs-3 fw-bold text-success" id="statCollected">0 ₺</div>
              <small class="text-muted text-uppercase fw-semibold">Bu Ay Tahsilat</small>
            </div>
          </div>
        </div>
      </div>
    </div>

<?php require __DIR__ . '/partials/modals/calendar-day.php'; ?>
<?php require __DIR__ . '/partials/footer.php'; ?>

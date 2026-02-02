<?php

$pageTitle = 'Ana Sayfa';
$activeNav = 'dashboard';

require __DIR__ . '/partials/header.php';
?>

    <!-- ===== VIEW: DASHBOARD ===== -->
    <div id="view-dashboard" data-can="dashboard.read">

      <!-- ROW 1: Alarmlar ve Takvim yan yana (Kalan alanı kaplasın) -->
      <div class="row g-3 mb-3 flex-grow-1">

        <!-- PANEL 1: ALARMLAR (Sol - Dar) -->
        <div class="col-lg-3 col-md-4 col-sm-12 d-flex">
          <div class="card flex-grow-1 d-flex flex-column" id="panelAlarms" data-can="alarms.read">
            <div class="card-header bg-warning text-dark py-2 d-flex justify-content-between align-items-center">
              <span class="fw-semibold"><i class="bi bi-bell-fill me-2"></i>Alarmlar</span>
              <span class="badge bg-dark" id="alarmCount">0</span>
            </div>
            <div class="card-body p-2 overflow-auto flex-grow-1" id="dashAlarmList">
              <div class="text-center py-4"><div class="spinner-border spinner-border-sm text-warning"></div></div>
            </div>
          </div>
        </div>

        <!-- PANEL 2: TAKVİM (Sağ - Geniş) -->
        <div class="col-lg-9 col-md-8 col-sm-12 d-flex">
          <div class="card flex-grow-1 d-flex flex-column" id="panelCalendar" data-can="calendar.read">
            <div class="card-header bg-success text-white py-2 d-flex justify-content-between align-items-center">
              <span class="fw-semibold"><i class="bi bi-calendar3 me-2"></i>Takvim</span>
              <div class="btn-group btn-group-sm">
                <button type="button" class="btn btn-light btn-sm active" data-view="month">Ay</button>
                <button type="button" class="btn btn-light btn-sm" data-view="week">Hafta</button>
              </div>
            </div>
            <div class="card-body p-2 flex-grow-1" id="dashCalendar">
              <div class="text-center py-4"><div class="spinner-border spinner-border-sm text-success"></div></div>
            </div>
          </div>
        </div>

      </div>

      <!-- ROW 2: İstatistik Kartları (Sabit - En Alt) -->
      <div class="row g-3 flex-shrink-0">
        <div class="col-6 col-md-3" data-can="customers.read">
          <div class="card h-100 border-0 shadow-sm">
            <div class="card-body py-3 px-3 text-center">
              <div class="fs-3 fw-bold text-primary" id="statCustomers">0</div>
              <small class="text-muted text-uppercase fw-semibold">Toplam Müşteri</small>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-3" data-can="projects.read">
          <div class="card h-100 border-0 shadow-sm">
            <div class="card-body py-3 px-3 text-center">
              <div class="fs-3 fw-bold text-info" id="statProjects">0</div>
              <small class="text-muted text-uppercase fw-semibold">Aktif Projeler</small>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-3" data-can="invoices.read">
          <div class="card h-100 border-0 shadow-sm">
            <div class="card-body py-3 px-3 text-center">
              <div class="fs-3 fw-bold text-danger" id="statPending">0 ₺</div>
              <small class="text-muted text-uppercase fw-semibold">Bekleyen Tahsilat</small>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-3" data-can="payments.read">
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

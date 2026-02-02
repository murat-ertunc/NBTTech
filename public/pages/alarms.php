<?php






$pageTitle = 'Alarmlar';
$activeNav = 'dashboard';
$currentPage = 'alarms';

require __DIR__ . '/partials/header.php';
?>

    <!-- ===== VIEW: ALARMLAR ===== -->
    <div id="view-alarms" data-can="alarms.read">
      <!-- Sayfa Başlığı -->
      <div class="card mb-3 border-0 shadow-sm">
        <div class="card-body py-2">
          <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
              <div class="bg-warning bg-opacity-10 rounded-circle p-2">
                <i class="bi bi-bell-fill text-warning fs-4"></i>
              </div>
              <div>
                <h5 class="mb-0">Alarmlar</h5>
                <small class="text-muted" id="alarmsSummary">Yükleniyor...</small>
              </div>
            </div>
            <button type="button" class="btn btn-outline-secondary btn-sm" id="btnRefreshAlarms" data-can="alarms.read">
              <i class="bi bi-arrow-clockwise me-1"></i>Yenile
            </button>
          </div>
        </div>
      </div>

      <!-- Tab Menüsü -->
      <ul class="nav nav-tabs" id="alarmsTabs" role="tablist">
        <li class="nav-item" role="presentation">
          <button class="nav-link active" data-alarm-tab="invoice" type="button">
            <i class="bi bi-receipt me-1"></i>Ödenmemiş Faturalar
            <span class="badge bg-primary ms-1" id="alarmCountInvoice">0</span>
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" data-alarm-tab="doubtful" type="button">
            <i class="bi bi-exclamation-triangle me-1"></i>Şüpheli Alacaklar
            <span class="badge bg-primary ms-1" id="alarmCountDoubtful">0</span>
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" data-alarm-tab="calendar" type="button">
            <i class="bi bi-calendar-event me-1"></i>Yaklaşan İşler
            <span class="badge bg-primary ms-1" id="alarmCountCalendar">0</span>
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" data-alarm-tab="guarantee" type="button">
            <i class="bi bi-shield-check me-1"></i>Teminat Alarmları
            <span class="badge bg-primary ms-1" id="alarmCountGuarantee">0</span>
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" data-alarm-tab="offer" type="button">
            <i class="bi bi-file-earmark-text me-1"></i>Teklif Geçerliliği
            <span class="badge bg-primary ms-1" id="alarmCountOffer">0</span>
          </button>
        </li>
      </ul>

      <!-- Tab İçerikleri -->
      <div id="alarmsTabContent" class="mt-3">
        <!-- JS ile doldurulacak -->
        <div class="card shadow-sm">
          <div class="card-body">
            <div class="text-center py-4">
              <div class="spinner-border spinner-border-sm text-warning"></div>
              <p class="text-muted mt-2 mb-0">Alarmlar yükleniyor...</p>
            </div>
          </div>
        </div>
      </div>
    </div>

<?php require __DIR__ . '/partials/footer.php'; ?>

<?php








$pageTitle = 'Tanımlamalar';
$activeNav = 'sistem';
$currentPage = 'parameters';

require __DIR__ . '/partials/header.php';
?>

    <!-- ===== VIEW: PARAMETRELER ===== -->
    <div id="view-parameters" data-can="parameters.read">
      <!-- Sayfa Başlığı -->
      <div class="card mb-3 border-0 shadow-sm">
        <div class="card-body py-2">
          <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
              <div class="bg-success bg-opacity-10 rounded-circle p-2">
                <i class="bi bi-sliders text-success fs-4"></i>
              </div>
              <div>
                <h5 class="mb-0">Tanımlamalar</h5>
                <small class="text-muted" id="parametersSummary">Sistem tanımlamaları ve ayarları</small>
              </div>
            </div>
            <button type="button" class="btn btn-outline-secondary btn-sm" id="btnRefreshParameters" data-can="parameters.read">
              <i class="bi bi-arrow-clockwise me-1"></i>Yenile
            </button>
          </div>
        </div>
      </div>

      <!-- Tab Menüsü -->
      <ul class="nav nav-tabs" id="parametersTabs" role="tablist">
        <li class="nav-item" role="presentation">
          <button class="nav-link active" data-param-tab="genel" type="button">
            <i class="bi bi-gear me-1"></i>Genel Ayarlar
            <span class="badge bg-primary ms-1" id="paramCountGenel">0</span>
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" data-param-tab="doviz" type="button">
            <i class="bi bi-currency-exchange me-1"></i>Döviz Türleri
            <span class="badge bg-success ms-1" id="paramCountDoviz">0</span>
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" data-param-tab="sehir" type="button">
            <i class="bi bi-geo-alt me-1"></i>İller
            <span class="badge bg-info ms-1" id="paramCountSehir">0</span>
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" data-param-tab="ilce" type="button">
            <i class="bi bi-pin-map me-1"></i>İlçeler
            <span class="badge bg-secondary ms-1" id="paramCountIlce">0</span>
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" data-param-tab="durum_proje" type="button">
            <i class="bi bi-kanban me-1"></i>Proje Durumları
            <span class="badge bg-info ms-1" id="paramCountDurumProje">0</span>
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" data-param-tab="durum_teklif" type="button">
            <i class="bi bi-file-text me-1"></i>Teklif Durumları
            <span class="badge bg-warning text-dark ms-1" id="paramCountDurumTeklif">0</span>
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" data-param-tab="durum_sozlesme" type="button">
            <i class="bi bi-file-earmark-text me-1"></i>Sözleşme Durumları
            <span class="badge bg-secondary ms-1" id="paramCountDurumSozlesme">0</span>
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" data-param-tab="durum_teminat" type="button">
            <i class="bi bi-shield-check me-1"></i>Teminat Durumları
            <span class="badge bg-danger ms-1" id="paramCountDurumTeminat">0</span>
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" data-param-tab="durum_takvim" type="button">
            <i class="bi bi-calendar3 me-1"></i>Takvim Durumları
            <span class="badge bg-success ms-1" id="paramCountDurumTakvim">0</span>
          </button>
        </li>
      </ul>

      <!-- Tab İçerikleri -->
      <div id="parametersTabContent" class="mt-3">
        <!-- JS ile doldurulacak -->
        <div class="card shadow-sm">
          <div class="card-body">
            <div class="text-center py-4">
              <div class="spinner-border spinner-border-sm text-success"></div>
              <p class="text-muted mt-2 mb-0">Parametreler yükleniyor...</p>
            </div>
          </div>
        </div>
      </div>
    </div>

<?php require __DIR__ . '/partials/footer.php'; ?>

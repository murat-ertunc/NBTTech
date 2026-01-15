<?php
/**
 * Musteri Detay Sayfasi - Server-Rendered
 * URL: /customer/{id}
 * 
 * Tab degistirme URL'yi degistirmiyor, sadece JS state'i degisiyor
 */

// Musteri Id'si URL'den router tarafindan aktariliyor
$MusteriId = $MusteriId ?? 0;

$pageTitle = 'Müşteri Detay';
$activeNav = 'customers';
$currentPage = 'customer';

require __DIR__ . '/partials/header.php';
?>

    <!-- ===== VIEW: MÜŞTERİ DETAY ===== -->
    <div id="view-customer-detail" data-customer-id="<?= (int)$MusteriId ?>">
      <div class="row">
        
        <!-- Sol Sidebar: Müşteri Listesi -->
        <div class="col-lg-4 mb-3 mb-lg-0 order-2 order-lg-1">
          <div class="card h-100 shadow-sm">
            <div class="card-header bg-primary text-white py-2 d-flex justify-content-between align-items-center">
              <span class="fw-semibold"><i class="bi bi-people-fill me-2"></i>Müşteriler</span>
              <span>
                <a href="/customers" class="btn btn-sm btn-light" title="Müşteriler">
                  <i class="bi bi-people-fill"></i>
                </a>
                <button type="button" class="btn btn-sm btn-light" data-action="add-customer" title="Yeni Müşteri">
                  <i class="bi bi-plus-lg"></i>
                </button>
              </span>
            </div>
            <div class="bg-light border-bottom px-2 py-1">
              <input type="text" class="form-control form-control-sm" id="sidebarCustomerSearch" placeholder="Müşteri ara..." autocomplete="off">
            </div>
            <div class="card-body p-2" id="sidebarCustomerList">
              <div class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div></div>
            </div>
          </div>
        </div>
        
        <!-- Sağ İçerik: Müşteri Detay -->
        <div class="col-lg-8 order-1 order-lg-2">
          <!-- Müşteri Başlık -->
          <div class="card mb-3 border-0 shadow-sm">
            <div class="card-body py-2">
              <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-3">
                  <a href="/customers" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left"></i>
                  </a>
                  <div>
                    <h5 class="mb-0" id="customerDetailTitle">Müşteri Adı</h5>
                    <small class="text-muted" id="customerDetailCode"></small>
                  </div>
                </div>
                <button type="button" class="btn btn-primary btn-sm" id="btnEditCustomer">
                  <i class="bi bi-pencil me-1"></i>Düzenle
                </button>
              </div>
            </div>
          </div>

          <!-- Tab Menüsü -->
          <ul class="nav nav-tabs" id="customerTabs" role="tablist">
            <li class="nav-item" role="presentation">
              <button class="nav-link active" data-tab="bilgi" type="button">
                <i class="bi bi-info-circle me-1"></i>Bilgi
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" data-tab="kisiler" type="button">
                <i class="bi bi-people me-1"></i>Kişiler
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" data-tab="gorusme" type="button">
                <i class="bi bi-chat-dots me-1"></i>Görüşme
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" data-tab="projeler" type="button">
                <i class="bi bi-kanban me-1"></i>Projeler
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" data-tab="teklifler" type="button">
                <i class="bi bi-file-earmark-text me-1"></i>Teklif
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" data-tab="sozlesmeler" type="button">
                <i class="bi bi-file-text me-1"></i>Sözleşme
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" data-tab="takvim" type="button">
                <i class="bi bi-calendar3 me-1"></i>Takvim
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" data-tab="damgavergisi" type="button">
                <i class="bi bi-percent me-1"></i>Damga Vergisi
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" data-tab="teminatlar" type="button">
                <i class="bi bi-shield-check me-1"></i>Teminat
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" data-tab="faturalar" type="button">
                <i class="bi bi-receipt me-1"></i>Fatura
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" data-tab="odemeler" type="button">
                <i class="bi bi-cash-stack me-1"></i>Ödeme
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" data-tab="dosyalar" type="button">
                <i class="bi bi-folder me-1"></i>Dosyalar
              </button>
            </li>
          </ul>

          <!-- Tab İçerikleri -->
          <div id="customerTabContent" class="mt-3">
            <!-- JS ile doldurulacak -->
          </div>
        </div>
        
      </div>
    </div>

<?php require __DIR__ . '/partials/modals/customer.php'; ?>
<?php require __DIR__ . '/partials/modals/project.php'; ?>
<?php require __DIR__ . '/partials/modals/invoice.php'; ?>
<?php require __DIR__ . '/partials/modals/payment.php'; ?>
<?php require __DIR__ . '/partials/modals/offer.php'; ?>
<?php require __DIR__ . '/partials/modals/contract.php'; ?>
<?php require __DIR__ . '/partials/modals/guarantee.php'; ?>
<?php require __DIR__ . '/partials/modals/meeting.php'; ?>
<?php require __DIR__ . '/partials/modals/contact.php'; ?>
<?php require __DIR__ . '/partials/modals/stamp-tax.php'; ?>
<?php require __DIR__ . '/partials/modals/file.php'; ?>
<?php require __DIR__ . '/partials/modals/calendar.php'; ?>
<?php require __DIR__ . '/partials/modals/entity-detail.php'; ?>
<?php require __DIR__ . '/partials/footer.php'; ?>

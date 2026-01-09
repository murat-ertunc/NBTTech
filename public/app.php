<?php
require __DIR__ . '/../app/Core/bootstrap.php';

$UygulamaAdi = config('app.name', 'NbtProject');
$Logo = config('app.logo', '/assets/logo.png');
?>
<!doctype html>
<html lang="tr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($UygulamaAdi, ENT_QUOTES, 'UTF-8') ?></title>
  <link rel="stylesheet" href="/assets/bootstrap.min.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
  <style>
    /* Tablo içi dropdown z-index düzeltmesi */
    .table-responsive { overflow: visible !important; }
    .table .dropdown-menu { z-index: 1050 !important; }
    .table .dropdown { position: static; }
    .table td .dropdown { position: relative; }
    .table td .dropdown-menu { position: absolute !important; }
    
    /* Card içi tablolar için */
    .card-body .table-responsive { overflow: visible !important; }
    .card { overflow: visible !important; }
  </style>
</head>
<body class="bg-light">

  <!-- ===== NAVBAR (Sabit Üst) ===== -->
  <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top shadow-sm" id="mainNav">
    <div class="container-fluid">
      <a class="navbar-brand d-flex align-items-center gap-2 fw-semibold" href="#dashboard">
        <img id="brandLogo" src="<?= htmlspecialchars($Logo, ENT_QUOTES, 'UTF-8') ?>" alt="Logo" style="height:32px;" />
        <span id="brandName"><?= htmlspecialchars($UygulamaAdi, ENT_QUOTES, 'UTF-8') ?></span>
      </a>
      
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="mainNavbar">
        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
          
          <!-- 1) DASHBOARD -->
          <li class="nav-item">
            <a class="nav-link" href="#dashboard" data-route="dashboard" data-nav-group="dashboard">
              <i class="bi bi-speedometer2 me-1"></i>Dashboard
            </a>
          </li>

          <!-- 2) MÜŞTERİLER -->
          <li class="nav-item">
            <a class="nav-link" href="#customers" data-route="customers" data-nav-group="customers">
              <i class="bi bi-people me-1"></i>Müşteriler
            </a>
          </li>

          <!-- 3) İŞLEMLER Dropdown -->
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown" data-nav-group="islemler" id="navIslemler">
              <i class="bi bi-folder me-1"></i>İşlemler
            </a>
            <ul class="dropdown-menu">
              <li><a class="dropdown-item" href="#invoices" data-route="invoices" data-nav-group="islemler"><i class="bi bi-receipt me-2"></i>Faturalar</a></li>
              <li><a class="dropdown-item" href="#payments" data-route="payments" data-nav-group="islemler"><i class="bi bi-cash-stack me-2"></i>Ödemeler</a></li>
              <li><a class="dropdown-item" href="#projects" data-route="projects" data-nav-group="islemler"><i class="bi bi-kanban me-2"></i>Projeler</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item" href="#offers" data-route="offers" data-nav-group="islemler"><i class="bi bi-file-text me-2"></i>Teklifler</a></li>
              <li><a class="dropdown-item" href="#contracts" data-route="contracts" data-nav-group="islemler"><i class="bi bi-file-earmark-text me-2"></i>Sözleşmeler</a></li>
              <li><a class="dropdown-item" href="#guarantees" data-route="guarantees" data-nav-group="islemler"><i class="bi bi-shield-check me-2"></i>Teminatlar</a></li>
            </ul>
          </li>



          <!-- 4) SİSTEM Dropdown (Admin) -->
          <li class="nav-item dropdown" id="systemMenu">
            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown" data-nav-group="sistem" id="navSistem">
              <i class="bi bi-gear me-1"></i>Sistem
            </a>
            <ul class="dropdown-menu">
              <li><a class="dropdown-item" href="#logs" data-route="logs" data-nav-group="sistem"><i class="bi bi-journal-text me-2"></i>İşlem Kayıtları</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item" href="#users" data-route="users" data-nav-group="sistem"><i class="bi bi-person-gear me-2"></i>Kullanıcılar</a></li>
            </ul>
          </li>

        </ul>
        
        <!-- Sağ Taraf: Kullanıcı Menüsü -->
        <div class="d-flex align-items-center">
          <div class="dropdown">
            <a class="btn btn-outline-light btn-sm dropdown-toggle" href="#" data-bs-toggle="dropdown">
              <i class="bi bi-person-circle me-1"></i><span id="userNameDisplay">Kullanıcı</span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><h6 class="dropdown-header">Hesap</h6></li>
              <li><a class="dropdown-item" href="#" data-action="change-password"><i class="bi bi-key me-2"></i>Şifre Değiştir</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item text-danger" href="#" id="logoutNav"><i class="bi bi-box-arrow-right me-2"></i>Çıkış</a></li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </nav>

  <!-- ===== ANA İÇERİK ===== -->
  <main class="container-fluid py-3" style="margin-top:56px; margin-bottom:40px; min-height:calc(100vh - 96px);">

    <!-- ===== VIEW: DASHBOARD ===== -->
    <div id="view-dashboard">
      <!-- İstatistik Kartları -->
      <div class="row g-2 mb-3">
        <div class="col-6 col-md-3">
          <div class="card h-100">
            <div class="card-body py-2 px-3">
              <small class="text-muted text-uppercase fw-semibold">Toplam Müşteri</small>
              <div class="fs-4 fw-bold" id="statCustomers">0</div>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="card h-100">
            <div class="card-body py-2 px-3">
              <small class="text-muted text-uppercase fw-semibold">Aktif Projeler</small>
              <div class="fs-4 fw-bold" id="statProjects">0</div>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="card h-100">
            <div class="card-body py-2 px-3">
              <small class="text-muted text-uppercase fw-semibold">Bekleyen Tahsilat</small>
              <div class="fs-4 fw-bold text-danger" id="statPending">0 ₺</div>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="card h-100">
            <div class="card-body py-2 px-3">
              <small class="text-muted text-uppercase fw-semibold">Bu Ay Tahsilat</small>
              <div class="fs-4 fw-bold text-success" id="statCollected">0 ₺</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Dashboard Grid: 3 Panel -->
      <div class="row g-2">
        
        <!-- PANEL 1: MÜŞTERİLER -->
        <div class="col-lg-4">
          <div class="card h-100" id="panelCustomers">
            <div class="card-header bg-primary text-white py-2 d-flex justify-content-between align-items-center">
              <span class="fw-semibold"><i class="bi bi-people-fill me-2"></i>Müşteriler</span>
              <button type="button" class="btn btn-sm btn-light" data-action="add-customer" title="Yeni Müşteri">
                <i class="bi bi-plus-lg"></i>
              </button>
            </div>
            <div class="bg-light border-bottom px-2 py-1">
              <input type="text" class="form-control form-control-sm" id="dashCustomerSearch" placeholder="Müşteri ara...">
            </div>
            <div class="card-body p-2 overflow-auto" id="dashCustomerList" style="max-height:280px;">
              <div class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div></div>
            </div>
          </div>
        </div>

        <!-- PANEL 2: ALARMLAR -->
        <div class="col-lg-4">
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

        <!-- PANEL 3: TAKVİM -->
        <div class="col-lg-4">
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
    </div>

    <!-- ===== VIEW: MÜŞTERİLER LİSTESİ ===== -->
    <div id="view-customers" class="d-none">
      <div class="card">
        <div class="card-header bg-primary text-white py-2">
          <span class="fw-semibold"><i class="bi bi-people-fill me-2"></i>Müşteriler</span>
        </div>
        <div id="customersToolbar"></div>
        <div id="customersFilterPanel" class="bg-light border-bottom p-2 d-none">
          <div class="row g-2 align-items-end">
            <div class="col-auto">
              <label class="form-label small mb-1">Kayıt Tarihi</label>
              <input type="date" class="form-control form-control-sm" id="filterCustomerDate">
            </div>
            <div class="col-auto">
              <button type="button" class="btn btn-primary btn-sm" id="applyCustomerFilter">Uygula</button>
              <button type="button" class="btn btn-outline-secondary btn-sm" id="clearCustomerFilter">Temizle</button>
            </div>
          </div>
        </div>
        <div class="card-body p-0" id="customersTableContainer">
          <div class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div></div>
        </div>
      </div>
    </div>

    <!-- ===== VIEW: MÜŞTERİ DETAY ===== -->
    <div id="view-customer-detail" class="d-none">
      <!-- Müşteri Başlık -->
      <div class="card mb-3 border-0 shadow-sm">
        <div class="card-body py-2">
          <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
              <a href="#customers" class="btn btn-outline-secondary btn-sm">
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

    <!-- ===== VIEW: FATURALAR ===== -->
    <div id="view-invoices" class="d-none">
      <div class="card" id="panelInvoices">
        <div class="card-header bg-primary text-white py-2">
          <span class="fw-semibold"><i class="bi bi-receipt me-2"></i>Faturalar</span>
        </div>
        <div id="invoicesToolbar"></div>
        <div class="card-body p-0" id="invoicesTableContainer">
          <div class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div></div>
        </div>
      </div>
    </div>

    <!-- ===== VIEW: ÖDEMELER ===== -->
    <div id="view-payments" class="d-none">
      <div class="card" id="panelPayments">
        <div class="card-header bg-primary text-white py-2">
          <span class="fw-semibold"><i class="bi bi-cash-stack me-2"></i>Ödemeler</span>
        </div>
        <div id="paymentsToolbar"></div>
        <div class="card-body p-0" id="paymentsTableContainer">
          <div class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div></div>
        </div>
      </div>
    </div>

    <!-- ===== VIEW: PROJELER ===== -->
    <div id="view-projects" class="d-none">
      <div class="card" id="panelProjects">
        <div class="card-header bg-primary text-white py-2">
          <span class="fw-semibold"><i class="bi bi-kanban me-2"></i>Projeler</span>
        </div>
        <div id="projectsToolbar"></div>
        <div class="card-body p-0" id="projectsTableContainer">
          <div class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div></div>
        </div>
      </div>
    </div>

    <!-- ===== VIEW: TEKLİFLER ===== -->
    <div id="view-offers" class="d-none">
      <div class="card" id="panelOffers">
        <div class="card-header bg-primary text-white py-2">
          <span class="fw-semibold"><i class="bi bi-file-text me-2"></i>Teklifler</span>
        </div>
        <div id="offersToolbar"></div>
        <div class="card-body p-0" id="offersTableContainer">
          <div class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div></div>
        </div>
      </div>
    </div>

    <!-- ===== VIEW: SÖZLEŞMELER ===== -->
    <div id="view-contracts" class="d-none">
      <div class="card" id="panelContracts">
        <div class="card-header bg-primary text-white py-2">
          <span class="fw-semibold"><i class="bi bi-file-earmark-text me-2"></i>Sözleşmeler</span>
        </div>
        <div id="contractsToolbar"></div>
        <div class="card-body p-0" id="contractsTableContainer">
          <div class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div></div>
        </div>
      </div>
    </div>

    <!-- ===== VIEW: TEMİNATLAR ===== -->
    <div id="view-guarantees" class="d-none">
      <div class="card" id="panelGuarantees">
        <div class="card-header bg-primary text-white py-2">
          <span class="fw-semibold"><i class="bi bi-shield-check me-2"></i>Teminatlar</span>
        </div>
        <div id="guaranteesToolbar"></div>
        <div class="card-body p-0" id="guaranteesTableContainer">
          <div class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div></div>
        </div>
      </div>
    </div>

    <!-- ===== VIEW: KULLANICILAR ===== -->
    <div id="view-users" class="d-none">
      <div class="card" id="panelUsers">
        <div class="card-header bg-primary text-white py-2">
          <span class="fw-semibold"><i class="bi bi-person-gear me-2"></i>Kullanıcılar</span>
        </div>
        <div id="usersToolbar"></div>
        <div class="card-body p-0" id="usersTableContainer">
          <div class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div></div>
        </div>
      </div>
    </div>

    <!-- ===== VIEW: LOGLAR ===== -->
    <div id="view-logs" class="d-none">
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















  </main>

  <!-- ===== FOOTER ===== -->
  <footer class="fixed-bottom bg-dark text-white py-2 small" style="height:40px;">
    <div class="container-fluid">
      <div class="row align-items-center">
        <div class="col-4">
          <span id="footerIp">IP: -</span>
        </div>
        <div class="col-4 text-center">
          <span id="footerUser">Kullanıcı: -</span>
        </div>
        <div class="col-4 text-end">
          <span id="footerDateTime">-</span>
        </div>
      </div>
    </div>
  </footer>

  <!-- ===== MODALS ===== -->

  <!-- Müşteri Ekle/Düzenle Modal -->
  <div class="modal fade" id="customerModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="customerModalTitle">Yeni Müşteri</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="alert alert-danger d-none modal-error" id="customerModalError"></div>
          <input type="hidden" id="customerId">
          <div class="mb-3">
            <label class="form-label">Unvan <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="customerUnvan" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Açıklama</label>
            <textarea class="form-control" id="customerAciklama" rows="3"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
          <button type="button" class="btn btn-primary" id="btnSaveCustomer">Kaydet</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Fatura Modal -->
  <div class="modal fade" id="invoiceModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="invoiceModalTitle">Yeni Fatura</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="alert alert-danger d-none modal-error" id="invoiceModalError"></div>
          <input type="hidden" id="invoiceId">
          <div class="mb-3">
            <label class="form-label">Müşteri <span class="text-danger">*</span></label>
            <select class="form-select" id="invoiceMusteriId" required>
              <option value="">Seçiniz...</option>
            </select>
          </div>
          <div class="row">
            <div class="col-6 mb-3">
              <label class="form-label">Tarih</label>
              <input type="date" class="form-control" id="invoiceTarih" value="<?= date('Y-m-d') ?>">
            </div>
            <div class="col-6 mb-3">
              <label class="form-label">Tutar</label>
              <input type="number" step="0.01" class="form-control" id="invoiceTutar">
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Döviz</label>
            <select class="form-select" id="invoiceDoviz">
              <option value="TRY">TRY</option>
              <option value="USD">USD</option>
              <option value="EUR">EUR</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Açıklama</label>
            <textarea class="form-control" id="invoiceAciklama" rows="2"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
          <button type="button" class="btn btn-primary" id="btnSaveInvoice">Kaydet</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Ödeme Modal -->
  <div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="paymentModalTitle">Yeni Ödeme</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="alert alert-danger d-none modal-error" id="paymentModalError"></div>
          <input type="hidden" id="paymentId">
          <div class="mb-3">
            <label class="form-label">Müşteri <span class="text-danger">*</span></label>
            <select class="form-select" id="paymentMusteriId" required>
              <option value="">Seçiniz...</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Fatura</label>
            <select class="form-select" id="paymentFaturaId">
              <option value="">Fatura Seçiniz (Opsiyonel)...</option>
            </select>
            <small class="text-muted">Fatura seçerseniz ödeme o faturaya bağlanır.</small>
          </div>
          <div class="row">
            <div class="col-6 mb-3">
              <label class="form-label">Tarih</label>
              <input type="date" class="form-control" id="paymentTarih" value="<?= date('Y-m-d') ?>">
            </div>
            <div class="col-6 mb-3">
              <label class="form-label">Tutar</label>
              <input type="number" step="0.01" class="form-control" id="paymentTutar">
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Açıklama</label>
            <textarea class="form-control" id="paymentAciklama" rows="2"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
          <button type="button" class="btn btn-primary" id="btnSavePayment">Kaydet</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Proje Modal -->
  <div class="modal fade" id="projectModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="projectModalTitle">Yeni Proje</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="alert alert-danger d-none modal-error" id="projectModalError"></div>
          <input type="hidden" id="projectId">
          <div class="mb-3">
            <label class="form-label">Müşteri <span class="text-danger">*</span></label>
            <select class="form-select" id="projectMusteriId" required>
              <option value="">Seçiniz...</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Proje Adı <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="projectName" required>
          </div>
          <div class="row">
            <div class="col-6 mb-3">
              <label class="form-label">Başlangıç</label>
              <input type="date" class="form-control" id="projectStart">
            </div>
            <div class="col-6 mb-3">
              <label class="form-label">Bitiş</label>
              <input type="date" class="form-control" id="projectEnd">
            </div>
          </div>
          <div class="row">
            <div class="col-6 mb-3">
              <label class="form-label">Bütçe</label>
              <input type="number" step="0.01" class="form-control" id="projectBudget">
            </div>
            <div class="col-6 mb-3">
              <label class="form-label">Durum</label>
              <select class="form-select" id="projectStatus">
                <option value="1">Aktif</option>
                <option value="2">Tamamlandı</option>
                <option value="3">İptal</option>
              </select>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
          <button type="button" class="btn btn-primary" id="btnSaveProject">Kaydet</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Teklif Modal -->
  <div class="modal fade" id="offerModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="offerModalTitle">Yeni Teklif</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="alert alert-danger d-none modal-error" id="offerModalError"></div>
          <input type="hidden" id="offerId">
          <div class="mb-3">
            <label class="form-label">Müşteri <span class="text-danger">*</span></label>
            <select class="form-select" id="offerMusteriId" required>
              <option value="">Seçiniz...</option>
            </select>
          </div>
          <div class="row">
            <div class="col-6 mb-3">
              <label class="form-label">Teklif No</label>
              <input type="text" class="form-control" id="offerNo">
            </div>
            <div class="col-6 mb-3">
              <label class="form-label">Konu</label>
              <input type="text" class="form-control" id="offerSubject">
            </div>
          </div>
          <div class="row">
            <div class="col-6 mb-3">
              <label class="form-label">Tutar</label>
              <input type="number" step="0.01" class="form-control" id="offerAmount">
            </div>
            <div class="col-6 mb-3">
              <label class="form-label">Döviz</label>
              <select class="form-select" id="offerCurrency">
                <option value="TRY">TRY</option>
                <option value="USD">USD</option>
                <option value="EUR">EUR</option>
              </select>
            </div>
          </div>
          <div class="row">
            <div class="col-6 mb-3">
              <label class="form-label">Tarih</label>
              <input type="date" class="form-control" id="offerDate" value="<?= date('Y-m-d') ?>">
            </div>
            <div class="col-6 mb-3">
              <label class="form-label">Geçerlilik</label>
              <input type="date" class="form-control" id="offerValidDate">
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Durum</label>
            <select class="form-select" id="offerStatus">
              <option value="0">Taslak</option>
              <option value="1">Gönderildi</option>
              <option value="2">Onaylandı</option>
              <option value="3">Reddedildi</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
          <button type="button" class="btn btn-primary" id="btnSaveOffer">Kaydet</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Sözleşme Modal -->
  <div class="modal fade" id="contractModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="contractModalTitle">Yeni Sözleşme</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="alert alert-danger d-none modal-error" id="contractModalError"></div>
          <input type="hidden" id="contractId">
          <div class="mb-3">
            <label class="form-label">Müşteri <span class="text-danger">*</span></label>
            <select class="form-select" id="contractMusteriId" required>
              <option value="">Seçiniz...</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Sözleşme No</label>
            <input type="text" class="form-control" id="contractNo">
          </div>
          <div class="row">
            <div class="col-6 mb-3">
              <label class="form-label">Başlangıç</label>
              <input type="date" class="form-control" id="contractStart">
            </div>
            <div class="col-6 mb-3">
              <label class="form-label">Bitiş</label>
              <input type="date" class="form-control" id="contractEnd">
            </div>
          </div>
          <div class="row">
            <div class="col-6 mb-3">
              <label class="form-label">Tutar</label>
              <input type="number" step="0.01" class="form-control" id="contractAmount">
            </div>
            <div class="col-6 mb-3">
              <label class="form-label">Döviz</label>
              <select class="form-select" id="contractCurrency">
                <option value="TRY">TRY</option>
                <option value="USD">USD</option>
                <option value="EUR">EUR</option>
              </select>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Durum</label>
            <select class="form-select" id="contractStatus">
              <option value="1">Aktif</option>
              <option value="2">Pasif</option>
              <option value="3">İptal</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
          <button type="button" class="btn btn-primary" id="btnSaveContract">Kaydet</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Teminat Modal -->
  <div class="modal fade" id="guaranteeModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="guaranteeModalTitle">Yeni Teminat</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="alert alert-danger d-none modal-error" id="guaranteeModalError"></div>
          <input type="hidden" id="guaranteeId">
          <div class="mb-3">
            <label class="form-label">Müşteri <span class="text-danger">*</span></label>
            <select class="form-select" id="guaranteeMusteriId" required>
              <option value="">Seçiniz...</option>
            </select>
          </div>
          <div class="row">
            <div class="col-6 mb-3">
              <label class="form-label">Belge No</label>
              <input type="text" class="form-control" id="guaranteeNo">
            </div>
            <div class="col-6 mb-3">
              <label class="form-label">Tür</label>
              <select class="form-select" id="guaranteeType">
                <option value="Nakit">Nakit</option>
                <option value="Teminat Mektubu">Teminat Mektubu</option>
                <option value="Çek">Çek</option>
                <option value="Senet">Senet</option>
              </select>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Banka</label>
            <input type="text" class="form-control" id="guaranteeBank">
          </div>
          <div class="row">
            <div class="col-6 mb-3">
              <label class="form-label">Tutar</label>
              <input type="number" step="0.01" class="form-control" id="guaranteeAmount">
            </div>
            <div class="col-6 mb-3">
              <label class="form-label">Döviz</label>
              <select class="form-select" id="guaranteeCurrency">
                <option value="TRY">TRY</option>
                <option value="USD">USD</option>
                <option value="EUR">EUR</option>
              </select>
            </div>
          </div>
          <div class="row">
            <div class="col-6 mb-3">
              <label class="form-label">Vade Tarihi</label>
              <input type="date" class="form-control" id="guaranteeDate">
            </div>
            <div class="col-6 mb-3">
              <label class="form-label">Durum</label>
              <select class="form-select" id="guaranteeStatus">
                <option value="1">Bekliyor</option>
                <option value="2">İade Edildi</option>
                <option value="3">Tahsil Edildi</option>
                <option value="4">Yandı</option>
              </select>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
          <button type="button" class="btn btn-primary" id="btnSaveGuarantee">Kaydet</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Kullanıcı Ekle Modal -->
  <div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="userModalTitle">Yeni Kullanıcı</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="alert alert-danger d-none modal-error" id="userModalError"></div>
          <input type="hidden" id="userId">
          <div class="mb-3">
            <label class="form-label">Ad Soyad <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="userAdSoyad" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Kullanıcı Adı <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="userKullaniciAdi" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Şifre <span class="text-danger">*</span></label>
            <input type="password" class="form-control" id="userSifre">
            <small class="text-muted">Düzenlemede boş bırakırsanız şifre değişmez.</small>
          </div>
          <div class="mb-3">
            <label class="form-label">Rol</label>
            <select class="form-select" id="userRol">
              <option value="user">Kullanıcı</option>
              <option value="admin">Admin</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
          <button type="button" class="btn btn-primary" id="btnSaveUser">Kaydet</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Şifre Değiştir Modal -->
  <div class="modal fade" id="passwordModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="bi bi-key me-2"></i>Şifre Değiştir</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="alert alert-danger d-none modal-error" id="passwordModalError"></div>
          <div class="mb-3">
            <label class="form-label">Mevcut Şifre</label>
            <input type="password" class="form-control" id="currentPassword" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Yeni Şifre</label>
            <input type="password" class="form-control" id="newPassword" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Yeni Şifre (Tekrar)</label>
            <input type="password" class="form-control" id="confirmPassword" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
          <button type="button" class="btn btn-primary" id="btnChangePassword">Değiştir</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Görüşme Modal -->
  <div class="modal fade" id="meetingModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="meetingModalTitle"><i class="bi bi-chat-dots me-2"></i>Yeni Görüşme</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="alert alert-danger d-none modal-error" id="meetingModalError"></div>
          <input type="hidden" id="meetingId">
          <input type="hidden" id="meetingMusteriId">
          <div class="mb-3">
            <label class="form-label">Tarih <span class="text-danger">*</span></label>
            <input type="date" class="form-control" id="meetingTarih" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Konu <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="meetingKonu" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Görüşülen Kişi</label>
            <input type="text" class="form-control" id="meetingKisi">
          </div>
          <div class="mb-3">
            <label class="form-label">Notlar</label>
            <textarea class="form-control" id="meetingNotlar" rows="3"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
          <button type="button" class="btn btn-primary" id="btnSaveMeeting">Kaydet</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Kişi Modal -->
  <div class="modal fade" id="contactModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="contactModalTitle"><i class="bi bi-person me-2"></i>Yeni Kişi</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="alert alert-danger d-none modal-error" id="contactModalError"></div>
          <input type="hidden" id="contactId">
          <input type="hidden" id="contactMusteriId">
          <div class="mb-3">
            <label class="form-label">Ad Soyad <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="contactAdSoyad" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Ünvan / Pozisyon</label>
            <input type="text" class="form-control" id="contactUnvan">
          </div>
          <div class="mb-3">
            <label class="form-label">Telefon</label>
            <input type="tel" class="form-control" id="contactTelefon">
          </div>
          <div class="mb-3">
            <label class="form-label">E-posta</label>
            <input type="email" class="form-control" id="contactEmail">
          </div>
          <div class="mb-3">
            <label class="form-label">Notlar</label>
            <textarea class="form-control" id="contactNotlar" rows="2"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
          <button type="button" class="btn btn-primary" id="btnSaveContact">Kaydet</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Damga Vergisi Modal -->
  <div class="modal fade" id="stampTaxModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="stampTaxModalTitle"><i class="bi bi-percent me-2"></i>Yeni Damga Vergisi</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="alert alert-danger d-none modal-error" id="stampTaxModalError"></div>
          <input type="hidden" id="stampTaxId">
          <input type="hidden" id="stampTaxMusteriId">
          <div class="mb-3">
            <label class="form-label">Tarih <span class="text-danger">*</span></label>
            <input type="date" class="form-control" id="stampTaxTarih" required>
          </div>
          <div class="row">
            <div class="col-md-8 mb-3">
              <label class="form-label">Tutar <span class="text-danger">*</span></label>
              <input type="number" step="0.01" class="form-control" id="stampTaxTutar" required>
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">Döviz</label>
              <select class="form-select" id="stampTaxDovizCinsi">
                <option value="TRY">TRY</option>
                <option value="USD">USD</option>
                <option value="EUR">EUR</option>
              </select>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Belge No</label>
            <input type="text" class="form-control" id="stampTaxBelgeNo">
          </div>
          <div class="mb-3">
            <label class="form-label">Açıklama</label>
            <textarea class="form-control" id="stampTaxAciklama" rows="2"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
          <button type="button" class="btn btn-primary" id="btnSaveStampTax">Kaydet</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Dosya Yükleme Modal -->
  <div class="modal fade" id="fileModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="fileModalTitle"><i class="bi bi-upload me-2"></i>Dosya Yükle</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="alert alert-danger d-none modal-error" id="fileModalError"></div>
          <input type="hidden" id="fileMusteriId">
          <div class="mb-3">
            <label class="form-label">Dosya Seç <span class="text-danger">*</span></label>
            <input type="file" class="form-control" id="fileInput" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Açıklama</label>
            <input type="text" class="form-control" id="fileAciklama">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
          <button type="button" class="btn btn-primary" id="btnSaveFile">Yükle</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Scripts -->
  <script src="/assets/bootstrap.bundle.min.js"></script>
  <script src="/assets/js/app.js"></script>
  <script src="/assets/js/pages.js"></script>

</body>
</html>

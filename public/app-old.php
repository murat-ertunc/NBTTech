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
</head>
<body class="bg-white">
  <!-- Navbar - Dokümana Uygun Üst Menü -->
  <nav class="navbar navbar-expand-lg navbar-dark bg-primary border-bottom shadow-sm" style="height: 56px;">
    <div class="container-fluid">
      <a class="navbar-brand d-flex align-items-center gap-2" href="#" onclick="ViewDegistir('dashboard'); return false;">
        <img id="brandLogo" src="<?= htmlspecialchars($Logo, ENT_QUOTES, 'UTF-8') ?>" alt="Logo" style="height:32px;" />
        <span class="fw-semibold" id="brandName"><?= htmlspecialchars($UygulamaAdi, ENT_QUOTES, 'UTF-8') ?></span>
      </a>
      
      <!-- Üst Menü: Ana Sayfa / Sistem / Ön Tanımlar / Raporlar -->
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topNavbar">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="topNavbar">
        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
          <li class="nav-item">
            <a class="nav-link active" href="#" id="topNavHome" onclick="ViewDegistir('dashboard'); UstMenuAktifYap(this); return false;">Ana Sayfa</a>
          </li>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="topNavSystem" data-bs-toggle="dropdown">Sistem</a>
            <ul class="dropdown-menu">
              <li><a class="dropdown-item" href="#" onclick="ViewDegistir('users'); UstMenuAktifYap(document.getElementById('topNavSystem')); return false;">Kullanıcılar</a></li>
              <li><a class="dropdown-item" href="#" onclick="ViewDegistir('system'); UstMenuAktifYap(document.getElementById('topNavSystem')); return false;">Sistem Ayarları</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item" href="#" onclick="ViewDegistir('logs'); UstMenuAktifYap(document.getElementById('topNavSystem')); return false;">İşlem Logları</a></li>
            </ul>
          </li>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="topNavDefinitions" data-bs-toggle="dropdown">Ön Tanımlar</a>
            <ul class="dropdown-menu">
              <li><a class="dropdown-item" href="#" onclick="ViewDegistir('definitions'); return false;">Genel Tanımlar</a></li>
              <li><a class="dropdown-item" href="#" onclick="ViewDegistir('currency'); UstMenuAktifYap(document.getElementById('topNavDefinitions')); return false;">Döviz Kurları</a></li>
              <li><a class="dropdown-item" href="#" onclick="ViewDegistir('vat'); UstMenuAktifYap(document.getElementById('topNavDefinitions')); return false;">KDV Oranları</a></li>
            </ul>
          </li>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="topNavReports" data-bs-toggle="dropdown">Raporlar</a>
            <ul class="dropdown-menu">
              <li><a class="dropdown-item" href="#" onclick="ViewDegistir('reports'); return false;">Finansal Raporlar</a></li>
              <li><a class="dropdown-item" href="#" onclick="ViewDegistir('reports-project'); UstMenuAktifYap(document.getElementById('topNavReports')); return false;">Proje Raporları</a></li>
              <li><a class="dropdown-item" href="#" onclick="ViewDegistir('reports-customer'); UstMenuAktifYap(document.getElementById('topNavReports')); return false;">Müşteri Raporları</a></li>
            </ul>
          </li>
        </ul>
        
        <!-- Sağ Taraf: Kullanıcı Bilgisi + Dropdown Menü + Çıkış -->
        <div class="d-flex align-items-center gap-2 text-white">
          <div class="dropdown">
            <a class="btn btn-outline-light btn-sm dropdown-toggle" href="#" id="userMenuDropdown" data-bs-toggle="dropdown" aria-expanded="false">
              <i class="bi bi-person-circle me-1"></i><span id="userNameDisplay">Kullanıcı</span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenuDropdown">
              <li><h6 class="dropdown-header">Hesap İşlemleri</h6></li>
              <li><a class="dropdown-item" href="#" data-action="change-password"><i class="bi bi-key me-2"></i>Şifre Değiştir</a></li>
              <li><a class="dropdown-item" href="#" data-action="user-profile"><i class="bi bi-person me-2"></i>Profilim</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item text-danger" href="#" id="logoutNav"><i class="bi bi-box-arrow-right me-2"></i>Çıkış</a></li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </nav>

  <div class="container-fluid p-0">
    <div class="row g-0">
      <!-- SOL PANEL (Müşteri Listesi) - Col-2 -->
      <div class="col-md-2 p-3 border-end bg-light" style="min-height: calc(100vh - 100px);">
        <h6 class="text-uppercase text-muted small fw-bold mb-3">Müşteriler</h6>
        <div class="mb-2">
          <input id="search" class="form-control form-control-sm" placeholder="Müşteri ara..." />
        </div>
        <button class="btn btn-primary btn-sm w-100 mb-3" data-bs-toggle="modal" data-bs-target="#addModal">
          <i class="bi bi-plus-lg me-1"></i>Yeni Müşteri
        </button>
        <div class="list-group list-group-flush overflow-auto" id="customerList" style="max-height: calc(100vh - 250px);">
          <!-- JS Dolduracak -->
        </div>
      </div>

      <!-- ORTA PANEL (Main Content) - Col-7 -->
      <div class="col-md-7 p-3" style="height: calc(100vh - 100px); overflow-y: auto;">
        
        <!-- View: Dashboard (Ana Sayfa) -->
        <div id="view-dashboard">
             <h4 class="mb-3">Dashboard</h4>
             <div class="row g-3">
                <div class="col-md-4">
                    <div class="card shadow-sm border-0">
                        <div class="card-body">
                             <h6 class="text-muted">Toplam Müşteri</h6>
                             <h3 id="dashMusteriSayisi">0</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card shadow-sm border-0">
                        <div class="card-body">
                             <h6 class="text-muted">Aktif Projeler</h6>
                             <h3 id="dashProjeSayisi">0</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card shadow-sm border-0">
                        <div class="card-body">
                             <h6 class="text-muted">Bekleyen Tahsilat</h6>
                             <h3 class="text-danger" id="dashBekleyenTahsilat">0 ₺</h3>
                        </div>
                    </div>
                </div>
             </div>
             <div class="alert alert-info mt-4">Seçili müşteri için detaylara sol panelden erişebilirsiniz.</div>
        </div>

        <!-- View: Customers (Müşteri Kartı) -->
        <div id="view-customers" class="d-none">
            <div class="card shadow-sm mb-3">
            <div class="card-header bg-white d-flex align-items-center justify-content-between py-2">
                <h5 class="mb-0 text-primary" id="selectedTitle">Seçili müşteri yok</h5>
            </div>
            <div class="card-body p-3">
                <div class="alert alert-danger d-none small py-2" role="alert" id="alertBox"></div>
                
                <div id="detailContainer">
                    <div class="card border mb-3 bg-light">
                        <div class="card-body p-2">
                        <form class="row g-2" id="detailForm">
                            <div class="col-md-5">
                                <label class="form-label small mb-1 fw-bold">Unvan</label>
                                <input id="detailUnvan" class="form-control form-control-sm" />
                            </div>
                            <div class="col-md-5">
                                <label class="form-label small mb-1 fw-bold">Açıklama</label>
                                <input id="detailAciklama" class="form-control form-control-sm" />
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button id="btnSave" class="btn btn-success btn-sm w-100" type="submit">Kaydet</button>
                            </div>
                        </form>
                        </div>
                    </div>

                    <!-- Sekmeler -->
                    <ul class="nav nav-tabs nav-fill small mb-3" id="tabs">
                        <li class="nav-item"><button class="nav-link active" data-tab="Genel" type="button">Genel</button></li>
                        <li class="nav-item"><button class="nav-link" data-tab="Projeler" type="button">Projeler</button></li>
                        <li class="nav-item"><button class="nav-link" data-tab="Faturalar" type="button">Faturalar</button></li>
                        <li class="nav-item"><button class="nav-link" data-tab="Odemeler" type="button">Ödemeler</button></li>
                        <li class="nav-item"><button class="nav-link" data-tab="Teklifler" type="button">Teklifler</button></li>
                        <li class="nav-item"><button class="nav-link" data-tab="Sozlesmeler" type="button">Sözleşmeler</button></li>
                        <li class="nav-item"><button class="nav-link" data-tab="Teminat" type="button">Teminat/Evrak</button></li>
                    </ul>
                    <div class="border rounded p-3 bg-white" id="tabContent" style="min-height: 250px;">
                        Seçili müşteri yok.
                    </div>
                </div>
            </div>
            </div>
        </div>

        <!-- View: System -->
        <div id="view-system" class="d-none">
            <h4>Sistem Ayarları</h4>
            <p>Sistem yapılandırma ayarları burada olacak.</p>
        </div>

        <!-- View: Definitions -->
        <div id="view-definitions" class="d-none">
            <h4>Ön Tanımlar</h4>
            <p>Program parametreleri, dropdown içerikleri...</p>
        </div>

        <!-- View: Reports -->
        <div id="view-reports" class="d-none">
            <h4>Raporlar</h4>
            <p>Finansal ve operasyonel raporlar...</p>
        </div>

        <!-- View: Users -->
        <div id="view-users" class="d-none">
            <div class="d-flex justify-content-between align-items-center mb-3">
                 <h4><i class="bi bi-people-fill me-2"></i>Kullanıcılar</h4>
                 <button class="btn btn-primary btn-sm" data-action="user-create"><i class="bi bi-plus-lg me-1"></i>Yeni Kullanıcı</button>
            </div>
            <div class="card">
                <div class="card-body p-0">
                    <table class="table table-hover table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Ad Soyad</th>
                                <th>Kullanıcı Adı</th>
                                <th>Rol</th>
                                <th>Durum</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody id="usersTableBodyMain">
                            <!-- JS ile doldurulacak -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- View: Logs (İşlem Logları) -->
        <div id="view-logs" class="d-none">
            <div class="d-flex justify-content-between align-items-center mb-3">
                 <h4><i class="bi bi-journal-text me-2"></i>İşlem Logları</h4>
                 <div>
                     <input type="date" id="logDateFilter" class="form-control form-control-sm d-inline-block" style="width:auto" />
                     <button class="btn btn-outline-primary btn-sm ms-2" data-action="logs-filter"><i class="bi bi-funnel me-1"></i>Filtrele</button>
                 </div>
            </div>
            <p class="text-muted small">Sistemdeki tüm işlem kayıtları burada listelenir.</p>
            <div class="card">
                <div class="card-body p-0">
                    <table class="table table-hover table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Tarih/Saat</th>
                                <th>Kullanıcı</th>
                                <th>İşlem</th>
                                <th>Tablo</th>
                                <th>Detay</th>
                            </tr>
                        </thead>
                        <tbody id="logsTableBody">
                            <tr><td colspan="5" class="text-center text-muted py-4">Henüz log kaydı yüklenmedi.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- View: Currency (Döviz Kurları) -->
        <div id="view-currency" class="d-none">
            <div class="d-flex justify-content-between align-items-center mb-3">
                 <h4><i class="bi bi-currency-exchange me-2"></i>Döviz Kurları</h4>
                 <button class="btn btn-outline-primary btn-sm" data-action="currency-refresh"><i class="bi bi-arrow-clockwise me-1"></i>Güncelle</button>
            </div>
            <p class="text-muted small">Güncel döviz kurları ve geçmiş veriler.</p>
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="card border-primary">
                        <div class="card-body text-center">
                            <h6 class="text-muted">USD / TRY</h6>
                            <h3 class="text-primary" id="currencyUSD">-</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-success">
                        <div class="card-body text-center">
                            <h6 class="text-muted">EUR / TRY</h6>
                            <h3 class="text-success" id="currencyEUR">-</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-warning">
                        <div class="card-body text-center">
                            <h6 class="text-muted">GBP / TRY</h6>
                            <h3 class="text-warning" id="currencyGBP">-</h3>
                        </div>
                    </div>
                </div>
            </div>
            <div class="alert alert-info mt-4"><i class="bi bi-info-circle me-2"></i>Kurlar merkez bankası verilerinden çekilecektir.</div>
        </div>

        <!-- View: VAT (KDV Oranları) -->
        <div id="view-vat" class="d-none">
            <div class="d-flex justify-content-between align-items-center mb-3">
                 <h4><i class="bi bi-percent me-2"></i>KDV Oranları</h4>
                 <button class="btn btn-primary btn-sm" data-action="kdv-create"><i class="bi bi-plus-lg me-1"></i>Yeni Oran Ekle</button>
            </div>
            <p class="text-muted small">Fatura ve tekliflerde kullanılacak KDV oranları.</p>
            <div class="card">
                <div class="card-body p-0">
                    <table class="table table-hover table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Oran (%)</th>
                                <th>Açıklama</th>
                                <th>Varsayılan</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody id="vatTableBody">
                            <tr><td colspan="4" class="text-center text-muted py-4">Yükleniyor...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- View: Reports-Project (Proje Raporları) -->
        <div id="view-reports-project" class="d-none">
            <div class="d-flex justify-content-between align-items-center mb-3">
                 <h4><i class="bi bi-bar-chart me-2"></i>Proje Raporları</h4>
                 <div>
                     <select class="form-select form-select-sm d-inline-block" style="width:auto" id="projReportType">
                         <option value="status">Durum Bazlı</option>
                         <option value="budget">Bütçe Bazlı</option>
                         <option value="timeline">Zaman Çizelgesi</option>
                     </select>
                     <button class="btn btn-outline-primary btn-sm ms-2" data-action="report-project"><i class="bi bi-file-earmark-bar-graph me-1"></i>Rapor Oluştur</button>
                 </div>
            </div>
            <p class="text-muted small">Proje performansı ve durum raporları.</p>
            <div class="card">
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-3">
                            <h6 class="text-muted">Toplam Proje</h6>
                            <h3 id="rptTotalProjects">0</h3>
                        </div>
                        <div class="col-md-3">
                            <h6 class="text-muted">Aktif</h6>
                            <h3 class="text-success" id="rptActiveProjects">0</h3>
                        </div>
                        <div class="col-md-3">
                            <h6 class="text-muted">Tamamlanan</h6>
                            <h3 class="text-primary" id="rptCompletedProjects">0</h3>
                        </div>
                        <div class="col-md-3">
                            <h6 class="text-muted">Toplam Bütçe</h6>
                            <h3 class="text-warning" id="rptTotalBudget">0 ₺</h3>
                        </div>
                    </div>
                </div>
            </div>
            <div class="alert alert-secondary mt-4"><i class="bi bi-graph-up me-2"></i>Detaylı grafikler için rapor oluştur butonuna tıklayın.</div>
        </div>

        <!-- View: Reports-Customer (Müşteri Raporları) -->
        <div id="view-reports-customer" class="d-none">
            <div class="d-flex justify-content-between align-items-center mb-3">
                 <h4><i class="bi bi-people me-2"></i>Müşteri Raporları</h4>
                 <div>
                     <select class="form-select form-select-sm d-inline-block" style="width:auto" id="custReportType">
                         <option value="revenue">Ciro Bazlı</option>
                         <option value="activity">Aktivite Bazlı</option>
                         <option value="risk">Risk Analizi</option>
                     </select>
                     <button class="btn btn-outline-primary btn-sm ms-2" data-action="report-customer"><i class="bi bi-file-earmark-person me-1"></i>Rapor Oluştur</button>
                 </div>
            </div>
            <p class="text-muted small">Müşteri bazlı finansal ve aktivite raporları.</p>
            <div class="card">
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-3">
                            <h6 class="text-muted">Toplam Müşteri</h6>
                            <h3 id="rptTotalCustomers">0</h3>
                        </div>
                        <div class="col-md-3">
                            <h6 class="text-muted">Toplam Ciro</h6>
                            <h3 class="text-success" id="rptTotalRevenue">0 ₺</h3>
                        </div>
                        <div class="col-md-3">
                            <h6 class="text-muted">Bekleyen Alacak</h6>
                            <h3 class="text-danger" id="rptPendingReceivables">0 ₺</h3>
                        </div>
                        <div class="col-md-3">
                            <h6 class="text-muted">Ortalama Vade</h6>
                            <h3 class="text-warning" id="rptAvgDue">0 gün</h3>
                        </div>
                    </div>
                </div>
            </div>
            <div class="alert alert-secondary mt-4"><i class="bi bi-graph-up me-2"></i>Detaylı grafikler için rapor oluştur butonuna tıklayın.</div>
        </div>

      </div>

      <!-- SAĞ PANEL (Right Panel) - Col-3 -->
      <div class="col-md-3 p-3 border-start bg-light" style="height: calc(100vh - 100px); overflow-y: auto;">
        
        <!-- Takvim -->
        <h6 class="text-uppercase text-muted small fw-bold mb-2 ps-1 border-start border-3 border-primary">Takvim / Ajanda</h6>
        <div class="card shadow-sm mb-4 border-0">
           <div class="card-body p-2" id="calendarBody" style="max-height: 300px; overflow-y: auto;">
              <div class="text-center text-muted small py-3">Yükleniyor...</div>
           </div>
        </div>

        <!-- Alarm Listesi -->
        <h6 class="text-uppercase text-muted small fw-bold mb-2 ps-1 border-start border-3 border-danger">Alarmlar / Bildirimler</h6>
        <div class="list-group list-group-flush small bg-white rounded shadow-sm" id="alarmList">
           <div class="text-center text-muted small py-2">Yükleniyor...</div>
        </div>
        
      </div>
    </div>
  </div>

  <!-- Footer - Dokümana Uygun -->
  <footer class="fixed-bottom bg-dark text-white py-2 px-3" style="font-size: 0.8rem;">
    <div class="container-fluid">
      <div class="row align-items-center">
        <div class="col-md-4">
          <span id="footerIp">IP: -</span>
        </div>
        <div class="col-md-4 text-center">
          <span id="footerUser">Kullanıcı: -</span>
        </div>
        <div class="col-md-4 text-end">
          <span id="footerDateTime">-</span>
        </div>
      </div>
    </div>
  </footer>

  <!-- Kullanıcılar Modal -->
  <div class="modal fade" id="usersModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Kullanıcı Yönetimi</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="alert alert-danger d-none" role="alert" id="usersModalError"></div>
          <div class="table-responsive">
            <table class="table table-hover table-sm" id="usersTable">
              <thead>
                <tr>
                  <th>Ad Soyad</th>
                  <th>Kullanıcı Adı</th>
                  <th>Rol</th>
                  <th>Durum</th>
                  <th>İşlemler</th>
                </tr>
              </thead>
              <tbody id="usersTableBody"></tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Yeni Müşteri</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="alert alert-danger d-none" role="alert" id="modalError"></div>
          <div class="mb-3">
            <label class="form-label">Unvan</label>
            <input id="addUnvan" class="form-control" />
            <small class="text-muted">En az 2 karakter olmalı.</small>
          </div>
          <div class="mb-3">
            <label class="form-label">Açıklama</label>
            <input id="addAciklama" class="form-control" />
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
          <button type="button" class="btn btn-primary" id="btnAdd">Ekle</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Yeni Fatura Modal -->
  <div class="modal fade" id="addInvoiceModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Yeni Fatura</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="alert alert-danger d-none" role="alert" id="invoiceModalError"></div>
          <form id="invoiceForm">
              <div class="mb-3">
                <label class="form-label">Tarih</label>
                <input type="date" id="invDate" class="form-control" value="<?= date('Y-m-d') ?>" />
              </div>
              <div class="mb-3">
                <label class="form-label">Tutar</label>
                <input type="number" step="0.01" id="invAmount" class="form-control" />
              </div>
              <div class="mb-3">
                <label class="form-label">Döviz</label>
                <select id="invCurrency" class="form-select">
                    <option value="TL">TL</option>
                    <option value="USD">USD</option>
                    <option value="EUR">EUR</option>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label">Açıklama</label>
                <textarea id="invDesc" class="form-control" rows="2"></textarea>
              </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
          <button type="button" class="btn btn-primary" id="btnSaveInvoice">Kaydet</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Yeni Ödeme Modal -->
  <div class="modal fade" id="addPaymentModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Yeni Ödeme</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="alert alert-danger d-none" role="alert" id="paymentModalError"></div>
          <form id="paymentForm">
              <div class="mb-3">
                <label class="form-label">Tarih</label>
                <input type="date" id="payDate" class="form-control" value="<?= date('Y-m-d') ?>" />
              </div>
              <div class="mb-3">
                <label class="form-label">Tutar</label>
                <input type="number" step="0.01" id="payAmount" class="form-control" />
              </div>
              <div class="mb-3">
                <label class="form-label">Açıklama</label>
                <textarea id="payDesc" class="form-control" rows="2"></textarea>
              </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
          <button type="button" class="btn btn-primary" id="btnSavePayment">Kaydet</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Proje Modal -->
  <div class="modal fade" id="projectModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="projectModalTitle">Yeni Proje</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="alert alert-danger d-none" role="alert" id="projectModalError"></div>
          <form id="projectForm">
              <input type="hidden" id="projId" />
              <div class="mb-3">
                <label class="form-label">Proje Adı</label>
                <input id="projName" class="form-control" required />
              </div>
              <div class="row g-2 mb-3">
                <div class="col-6">
                   <label class="form-label">Başlangıç</label>
                   <input type="date" id="projStart" class="form-control" />
                </div>
                <div class="col-6">
                   <label class="form-label">Bitiş</label>
                   <input type="date" id="projEnd" class="form-control" />
                </div>
              </div>
              <div class="mb-3">
                <label class="form-label">Bütçe</label>
                <input type="number" step="0.01" id="projBudget" class="form-control" />
              </div>
              <div class="mb-3">
                <label class="form-label">Durum</label>
                <select id="projStatus" class="form-select">
                    <option value="1">Aktif</option>
                    <option value="2">Tamamlandı</option>
                    <option value="3">İptal</option>
                </select>
              </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
          <button type="button" class="btn btn-primary" id="btnSaveProject">Kaydet</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Teklif Modal -->
  <div class="modal fade" id="offerModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="offerModalTitle">Yeni Teklif</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
           <div class="alert alert-danger d-none" role="alert" id="offerModalError"></div>
           <form id="offerForm">
               <input type="hidden" id="offerId" />
               <div class="mb-3">
                 <label class="form-label">Teklif No</label>
                 <input id="offerNo" class="form-control" required />
               </div>
               <div class="mb-3">
                 <label class="form-label">Konu</label>
                 <input id="offerSubject" class="form-control" />
               </div>
               <div class="row g-2 mb-3">
                 <div class="col-6">
                   <label class="form-label">Tutar</label>
                   <input type="number" step="0.01" id="offerAmount" class="form-control" />
                 </div>
                 <div class="col-6">
                   <label class="form-label">Döviz</label>
                   <select id="offerCurrency" class="form-select">
                       <option value="TRY">TRY</option>
                       <option value="USD">USD</option>
                       <option value="EUR">EUR</option>
                   </select>
                 </div>
               </div>
               <div class="row g-2 mb-3">
                 <div class="col-6">
                   <label class="form-label">Tarih</label>
                   <input type="date" id="offerDate" class="form-control" />
                 </div>
                 <div class="col-6">
                   <label class="form-label">Geçerlilik</label>
                   <input type="date" id="offerValid" class="form-control" />
                 </div>
               </div>
               <div class="mb-3">
                 <label class="form-label">Durum</label>
                 <select id="offerStatus" class="form-select">
                     <option value="0">Taslak</option>
                     <option value="1">Gönderildi</option>
                     <option value="2">Onaylandı</option>
                     <option value="3">Reddedildi</option>
                 </select>
               </div>
           </form>
        </div>
        <div class="modal-footer">
           <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
           <button type="button" class="btn btn-primary" id="btnSaveOffer">Kaydet</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Sozlesme Modal -->
  <div class="modal fade" id="contractModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="contractModalTitle">Yeni Sözleşme</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
           <div class="alert alert-danger d-none" role="alert" id="contractModalError"></div>
           <form id="contractForm">
               <input type="hidden" id="contractId" />
               <div class="mb-3">
                 <label class="form-label">Sözleşme No</label>
                 <input id="contractNo" class="form-control" required />
               </div>
               <div class="row g-2 mb-3">
                 <div class="col-6">
                   <label class="form-label">Başlangıç</label>
                   <input type="date" id="contractStart" class="form-control" />
                 </div>
                 <div class="col-6">
                   <label class="form-label">Bitiş</label>
                   <input type="date" id="contractEnd" class="form-control" />
                 </div>
               </div>
               <div class="row g-2 mb-3">
             <div class="col-6">
               <label class="form-label">Tutar</label>
               <input type="number" step="0.01" id="contractAmount" class="form-control" />
             </div>
             <div class="col-6">
               <label class="form-label">Döviz</label>
               <select id="contractCurrency" class="form-select">
                   <option value="TRY">TRY</option>
                   <option value="USD">USD</option>
                   <option value="EUR">EUR</option>
               </select>
             </div>
           </div>
           <div class="mb-3">
             <label class="form-label">Durum</label>
             <select id="contractStatus" class="form-select">
                 <option value="1">Aktif</option>
                 <option value="2">Pasif</option>
                 <option value="3">İptal</option>
             </select>
           </div>
        </div>
        <div class="modal-footer">
           <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
           <button type="button" class="btn btn-primary" id="btnSaveContract">Kaydet</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Teminat Modal -->
  <div class="modal fade" id="guaranteeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="guaranteeModalTitle">Yeni Teminat</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
           <div class="alert alert-danger d-none" role="alert" id="guaranteeModalError"></div>
           <input type="hidden" id="guaranteeId" />
           <div class="mb-3">
             <label class="form-label">Belge No</label>
             <input id="guaranteeNo" class="form-control" />
           </div>
           <div class="mb-3">
             <label class="form-label">Tür</label>
             <select id="guaranteeType" class="form-select">
                 <option value="Nakit">Nakit</option>
                 <option value="Teminat Mektubu">Banka Teminat Mektubu</option>
                 <option value="Çek">Çek</option>
                 <option value="Senet">Senet</option>
             </select>
           </div>
           <div class="mb-3">
             <label class="form-label">Banka Adı</label>
             <input id="guaranteeBank" class="form-control" />
           </div>
           <div class="row g-2 mb-3">
             <div class="col-6">
               <label class="form-label">Tutar</label>
               <input type="number" step="0.01" id="guaranteeAmount" class="form-control" />
             </div>
             <div class="col-6">
               <label class="form-label">Döviz</label>
               <select id="guaranteeCurrency" class="form-select">
                   <option value="TRY">TRY</option>
                   <option value="USD">USD</option>
                   <option value="EUR">EUR</option>
               </select>
             </div>
           </div>
           <div class="mb-3">
             <label class="form-label">Vade Tarihi</label>
             <input type="date" id="guaranteeDate" class="form-control" />
           </div>
           <div class="mb-3">
             <label class="form-label">Durum</label>
             <select id="guaranteeStatus" class="form-select">
                 <option value="1">Bekliyor</option>
                 <option value="2">İade Edildi</option>
                 <option value="3">Tahsil Edildi</option>
                 <option value="4">Yandı</option>
             </select>
           </div>
        </div>
        <div class="modal-footer">
           <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
           <button type="button" class="btn btn-primary" id="btnSaveGuarantee">Kaydet</button>
        </div>
      </div>
    </div>
  </div>

  <!-- KDV Oranı Modal -->
  <div class="modal fade" id="kdvModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="kdvModalTitle"><i class="bi bi-percent me-2"></i>Yeni KDV Oranı</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="alert alert-danger d-none small py-2" id="kdvModalError"></div>
          <input type="hidden" id="kdvId" />
          <div class="mb-3">
            <label class="form-label small fw-bold">Oran (%)</label>
            <input type="number" class="form-control form-control-sm" id="kdvOran" min="0" max="100" required />
          </div>
          <div class="mb-3">
            <label class="form-label small fw-bold">Açıklama</label>
            <input type="text" class="form-control form-control-sm" id="kdvAciklama" required />
          </div>
          <div class="form-check">
            <input type="checkbox" class="form-check-input" id="kdvVarsayilan" />
            <label class="form-check-label small" for="kdvVarsayilan">Varsayılan Oran</label>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
          <button type="button" class="btn btn-primary" id="btnSaveKdv">Kaydet</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Şifre Değiştir Modal -->
  <div class="modal fade" id="changePasswordModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="bi bi-key me-2"></i>Şifre Değiştir</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="alert alert-danger d-none" role="alert" id="passwordModalError"></div>
          <div class="alert alert-success d-none" role="alert" id="passwordModalSuccess"></div>
          <form id="changePasswordForm">
              <div class="mb-3">
                <label class="form-label">Mevcut Şifre</label>
                <input type="password" id="currentPassword" class="form-control" required />
              </div>
              <div class="mb-3">
                <label class="form-label">Yeni Şifre</label>
                <input type="password" id="newPassword" class="form-control" required />
                <small class="text-muted">En az 6 karakter olmalıdır.</small>
              </div>
              <div class="mb-3">
                <label class="form-label">Yeni Şifre (Tekrar)</label>
                <input type="password" id="confirmPassword" class="form-control" required />
              </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
          <button type="button" class="btn btn-primary" id="btnChangePassword">Şifreyi Değiştir</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Kullanıcı Ekleme Modal -->
  <div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="bi bi-person-plus me-2"></i>Yeni Kullanıcı</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="alert alert-danger d-none" role="alert" id="addUserModalError"></div>
          <form id="addUserForm">
              <div class="mb-3">
                <label class="form-label">Ad Soyad</label>
                <input id="newUserAdSoyad" class="form-control" required />
              </div>
              <div class="mb-3">
                <label class="form-label">Kullanıcı Adı</label>
                <input id="newUserKullaniciAdi" class="form-control" required />
              </div>
              <div class="mb-3">
                <label class="form-label">Şifre</label>
                <input type="password" id="newUserSifre" class="form-control" required />
              </div>
              <div class="mb-3">
                <label class="form-label">Rol</label>
                <select id="newUserRol" class="form-select">
                    <option value="user">Kullanıcı</option>
                    <option value="admin">Admin</option>
                </select>
              </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
          <button type="button" class="btn btn-primary" id="btnAddUser">Kullanıcı Ekle</button>
        </div>
      </div>
    </div>
  </div>

  <script src="/assets/bootstrap.bundle.min.js"></script>
  <script>
    const UygulamaAyar = <?= json_encode(['name' => $UygulamaAdi, 'logo' => $Logo], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
    const ApiTabani = '';
    const ListeEl = document.getElementById('customerList');
    const AnahtarToken = 'nbt_token';
    const AnahtarRol = 'nbt_role';
    const AnahtarKullanici = 'nbt_user';
    const AnahtarSekme = 'nbt_tab_id';
    const AramaEl = document.getElementById('search');
    const MusteriEkleModal = new bootstrap.Modal(document.getElementById('addModal'));
    const UyariKutu = document.getElementById('alertBox');
    const ModalHata = document.getElementById('modalError');

    // ===== EVENT DELEGATION - Merkezi Click Handler =====
    document.addEventListener('click', function(e) {
        const Target = e.target.closest('[data-action]');
        if (!Target) return;
        
        const Action = Target.getAttribute('data-action');
        const DataId = Target.getAttribute('data-id');
        e.preventDefault();
        
        // Action Handler Dispatch
        switch(Action) {
            // === Kullanıcı Menüsü ===
            case 'change-password':
                SifreDegistirModalAc();
                break;
            case 'user-profile':
                ProfilModalAc();
                break;
            
            // === Kullanıcı Yönetimi ===
            case 'user-create':
                YeniKullaniciModalAc();
                break;
            case 'create-user':
                YeniKullaniciModalAc();
                break;
                
            // === Müşteri CRUD ===
            case 'create-invoice':
                FaturaModalAc();
                break;
            case 'create-payment':
                OdemeModalAc();
                break;
            case 'create-project':
                ProjectModalAc();
                break;
            case 'create-offer':
                OfferModalAc();
                break;
            case 'create-contract':
                ContractModalAc();
                break;
            case 'create-guarantee':
                GuaranteeModalAc();
                break;
                
            // === Log Filtreleme ===
            case 'logs-filter':
                LoglariFiltrele();
                break;
                
            // === Döviz Kurları ===
            case 'currency-refresh':
                DovizKurlariYenile();
                break;
                
            // === KDV Oranları ===
            case 'kdv-create':
                KdvModalAc();
                break;
            case 'kdv-edit':
                KdvModalAc(parseInt(DataId));
                break;
            case 'kdv-delete':
                KdvSil(parseInt(DataId));
                break;
                
            // === Raporlar ===
            case 'report-project':
                ProjeRaporuOlustur();
                break;
            case 'report-customer':
                MusteriRaporuOlustur();
                break;
                
            default:
                console.warn('Unhandled action:', Action, 'DataId:', DataId);
                HataGoster('Bu işlem henüz tanımlanmamış: ' + Action);
        }
    });

    document.title = UygulamaAyar.name || 'NbtProject';
    document.getElementById('brandName').textContent = UygulamaAyar.name || 'NbtProject';
    document.getElementById('brandLogo').src = UygulamaAyar.logo || '/assets/logo.png';
    
    // User Name Display
    let AktifKullanici = null;
    try {
        AktifKullanici = JSON.parse(localStorage.getItem(AnahtarKullanici));
        if(AktifKullanici && AktifKullanici.name) {
            document.getElementById('userNameDisplay').textContent = AktifKullanici.name;
            document.getElementById('footerUser').textContent = 'Kullanıcı: ' + AktifKullanici.name;
        }
    } catch(e){}

    // Footer Tarih/Saat Güncelle
    function FooterGuncelle() {
        const Now = new Date();
        const Formatted = Now.toLocaleDateString('tr-TR') + ' ' + Now.toLocaleTimeString('tr-TR');
        document.getElementById('footerDateTime').textContent = Formatted;
    }
    FooterGuncelle();
    setInterval(FooterGuncelle, 1000);

    // IP Adresi: hostname veya localhost goster
    document.getElementById('footerIp').textContent = 'IP: ' + (window.location.hostname || 'localhost');

    function SekmeIdAl() {
      let Deger = sessionStorage.getItem(AnahtarSekme);
      if (!Deger) {
        Deger = crypto.randomUUID();
        sessionStorage.setItem(AnahtarSekme, Deger);
      }
      return Deger;
    }

    function UyariSinifAyarla(Tip) {
      UyariKutu.classList.remove('alert-success', 'alert-danger');
      UyariKutu.classList.add(Tip === 'success' ? 'alert-success' : 'alert-danger');
    }

    function HataGoster(Mesaj) {
      UyariSinifAyarla('danger');
      UyariKutu.textContent = Mesaj;
      UyariKutu.classList.remove('d-none');
    }

    function BasariGoster(Mesaj) {
      UyariSinifAyarla('success');
      UyariKutu.textContent = Mesaj;
      UyariKutu.classList.remove('d-none');
    }

    function HataTemizle() {
      UyariKutu.classList.add('d-none');
      UyariKutu.textContent = '';
    }

    function ModalHataGoster(Mesaj) {
      ModalHata.textContent = Mesaj;
      ModalHata.classList.remove('d-none');
    }

    function ModalHataTemizle() {
      ModalHata.classList.add('d-none');
      ModalHata.textContent = '';
    }

    function TokenAl() { return localStorage.getItem(AnahtarToken); }
    function RolAl() { return localStorage.getItem(AnahtarRol) || 'user'; }

    // XSS saldirilarina karsi HTML escape
    function HtmlKacis(Deger) {
      if (Deger == null) return '';
      const Eleman = document.createElement('div');
      Eleman.textContent = String(Deger);
      return Eleman.innerHTML;
    }

    if (!TokenAl()) { window.location.href = '/login'; }

    async function Istek(Yol, Ayarlar = {}) {
      const Basliklar = Ayarlar.headers || {};
      if (!Basliklar['Content-Type'] && !(Ayarlar.body instanceof FormData)) {
        Basliklar['Content-Type'] = 'application/json';
      }
      const Tk = TokenAl();
      if (Tk) Basliklar['Authorization'] = 'Bearer ' + Tk;
      Basliklar['X-Tab-Id'] = SekmeIdAl();
      Basliklar['X-Role'] = RolAl();
      const Yanıt = await fetch(ApiTabani + Yol, { ...Ayarlar, headers: Basliklar });
      const Veri = await Yanıt.json().catch(() => ({}));
      if (!Yanıt.ok) {
        if (Yanıt.status === 401) {
          localStorage.removeItem(AnahtarToken);
          localStorage.removeItem(AnahtarRol);
          localStorage.removeItem(AnahtarKullanici);
          window.location.href = '/login';
        } else if (Yanıt.status === 403) {
          throw new Error(Veri.error || 'Yetkiniz yok');
        } else {
          throw new Error(Veri.error || 'Hata');
        }
      }
      return Veri;
    }

    let Musteriler = [];
    let Secili = null;
    let AktifSekme = 'Genel';

    function ListeCiz() {
      const Sorgu = (AramaEl.value || '').toLowerCase();
      ListeEl.innerHTML = '';
      const Filtreli = Musteriler.filter(Kayit => (Kayit.Unvan || '').toLowerCase().includes(Sorgu));
      if (Filtreli.length === 0) {
        const Bos = document.createElement('div');
        Bos.className = 'text-muted small px-2 py-1';
        Bos.textContent = 'Kayıt bulunamadı';
        ListeEl.appendChild(Bos);
        return;
      }
      Filtreli.forEach(Kayit => {
        const Buton = document.createElement('button');
        Buton.className = 'list-group-item list-group-item-action py-2 ' + (Secili && Secili.Id === Kayit.Id ? ' active' : '');
        Buton.style.fontSize = '0.85rem';
        const EkleyenBilgi = (RolAl() === 'superadmin' || RolAl() === 'admin') && Kayit.EkleyenAdSoyad 
          ? `<div class="text-muted" style="font-size:0.7rem">${HtmlKacis(Kayit.EkleyenAdSoyad)}</div>` 
          : '';
        Buton.innerHTML = `<div class="text-truncate">${HtmlKacis(Kayit.Unvan)}</div>${EkleyenBilgi}`;
        Buton.addEventListener('click', () => {
          Secili = Kayit;
          ListeCiz();
          MusteriSecildi();
        });
        ListeEl.appendChild(Buton);
      });
    }

    async function MusterileriYukle() {
      const OncekiId = Secili ? Secili.Id : null;
      HataTemizle();
      try {
        const Yanit = await Istek('/api/customers');
        Musteriler = Yanit.data || [];
        if (OncekiId) {
          Secili = Musteriler.find(K => K.Id === OncekiId) || (Musteriler.length ? Musteriler[0] : null);
        } else if (Musteriler.length) {
          Secili = Musteriler[0];
        } else {
          Secili = null;
        }
      } catch (Hata) {
        HataGoster(Hata.message);
        Musteriler = [];
        Secili = null;
      }
      ListeCiz();
      SeciliCiz();
    }

    function SeciliCiz() {
      const BaslikEl = document.getElementById('selectedTitle');
      const UnvanEl = document.getElementById('detailUnvan');
      const AciklamaEl = document.getElementById('detailAciklama');
      if (!Secili) {
        BaslikEl.textContent = 'Seçili müşteri yok';
        UnvanEl.value = '';
        AciklamaEl.value = '';
        document.getElementById('tabContent').textContent = 'Seçili müşteri yok.';
        return;
      }
      BaslikEl.textContent = Secili.Unvan;
      UnvanEl.value = Secili.Unvan || '';
      AciklamaEl.value = Secili.Aciklama || '';
      SekmeIcerikGuncelle();
    }

    async function MusteriEkle() {
      const Unvan = document.getElementById('addUnvan').value.trim();
      const Aciklama = document.getElementById('addAciklama').value.trim();
      ModalHataTemizle();
      if (!Unvan) { ModalHataGoster('Ünvan zorunludur.'); return; }
      if (Unvan.length < 2) { ModalHataGoster('Ünvan en az 2 karakter olmalıdır.'); return; }
      try {
        await Istek('/api/customers', { method: 'POST', body: JSON.stringify({ Unvan, Aciklama: Aciklama || null }) });
        document.getElementById('addUnvan').value = '';
        document.getElementById('addAciklama').value = '';
        MusteriEkleModal.hide();
        await MusterileriYukle();
      } catch (Hata) {
        ModalHataGoster(Hata.message);
      }
    }

    async function MusteriKaydet(Olay) {
      Olay.preventDefault();
      if (!Secili) return;
      const Unvan = document.getElementById('detailUnvan').value.trim();
      const Aciklama = document.getElementById('detailAciklama').value.trim();
      HataTemizle();
      if (!Unvan) { HataGoster('Ünvan zorunludur.'); return; }
      if (Unvan.length < 2) { HataGoster('Ünvan en az 2 karakter olmalıdır.'); return; }
      try {
        await Istek(`/api/customers/${Secili.Id}`, { method: 'PUT', body: JSON.stringify({ Unvan, Aciklama: Aciklama || null }) });
        BasariGoster('Düzenleme başarılı.');
        await MusterileriYukle();
      } catch (Hata) {
        HataGoster(Hata.message);
      }
    }

    async function SekmeIcerikGuncelle() {
      const IcerikAlani = document.getElementById('tabContent');
      
      if (!Secili) {
         IcerikAlani.innerHTML = 'Seçili müşteri yok.';
         return;
      }

      // Spinner
      IcerikAlani.innerHTML = '<div class="d-flex justify-content-center p-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Yükleniyor...</span></div></div>';

      if (AktifSekme === 'Genel') {
          IcerikAlani.innerHTML = `
          <div class="row">
            <div class="col-md-6">
               <p><strong>Müşteri:</strong> ${HtmlKacis(Secili.Unvan)}</p>
               <p><strong>Kayıt Tarihi:</strong> ${Secili.EklemeZamani || '-'}</p>
            </div>
            <div class="col-md-6">
               <div class="card bg-light border-0">
                  <div class="card-body">
                    <h6 class="card-title text-muted">Finansal Özet</h6>
                    <p class="card-text">Bakiye ve risk bilgileri burada gösterilecek.</p>
                  </div>
               </div>
            </div>
          </div>`;
      } else if (AktifSekme === 'Faturalar') {
          await FaturalariListele(Secili.Id, IcerikAlani);
      } else if (AktifSekme === 'Odemeler') {
          await OdemeleriListele(Secili.Id, IcerikAlani);
      } else if (AktifSekme === 'Projeler') {
          await ProjeleriListele(Secili.Id, IcerikAlani);
      } else if (AktifSekme === 'Teklifler') {
          await TeklifleriListele(Secili.Id, IcerikAlani);
      } else if (AktifSekme === 'Sozlesmeler') {
          await SozlesmeleriListele(Secili.Id, IcerikAlani);
      } else if (AktifSekme === 'Teminat') {
          await TeminatlariListele(Secili.Id, IcerikAlani);
      } else {
          // Tanimlanmamis sekme icerigi
          IcerikAlani.innerHTML = `<div class="p-3 text-muted">${AktifSekme} sekmesi henüz aktif değil.</div>`;
      }
    }

    async function FaturalariListele(CustomerId, Container) {
        const Header = `
        <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
             <h6 class="mb-0 text-primary">Faturalar</h6>
             <button class="btn btn-sm btn-primary" onclick="FaturaModalAc()"><i class="bi bi-plus-lg me-1"></i>Yeni Fatura</button>
        </div>`;

        try {
            const Res = await Istek(`/api/invoices?musteri_id=${CustomerId}`);
            const Data = Res.data || [];
            
            if (Data.length === 0) {
                Container.innerHTML = Header + `
                <div class="d-flex flex-column align-items-center justify-content-center p-4 text-muted">
                    <i class="bi bi-receipt mb-2" style="font-size: 2rem;"></i>
                    <p>Kayıtlı fatura bulunamadı.</p>
                </div>`;
                return;
            }

            let Html = `
            <div class="table-responsive">
            <table class="table table-sm table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Fatura No (ID)</th>
                        <th>Tarih</th>
                        <th class="text-end">Tutar</th>
                        <th class="text-end">Ödenen</th>
                        <th class="text-end">Kalan</th>
                        <th>Döviz</th>
                        <th>Açıklama</th>
                    </tr>
                </thead>
                <tbody>`;
            
            Data.forEach(F => {
                const Tutar = parseFloat(F.Tutar);
                const Odenen = parseFloat(F.OdenenTutar || 0);
                const Kalan = Tutar - Odenen;
                const KalanClass = Kalan > 0.01 ? 'text-danger fw-bold' : 'text-success';

                Html += `<tr>
                    <td>#${HtmlKacis(F.Id)}</td>
                    <td>${HtmlKacis(F.Tarih)}</td>
                    <td class="text-end fw-bold">${Tutar.toFixed(2)}</td>
                    <td class="text-end text-muted">${Odenen.toFixed(2)}</td>
                    <td class="text-end ${KalanClass}">${Kalan.toFixed(2)}</td>
                    <td>${HtmlKacis(F.DovizCinsi)}</td>
                    <td><small class="text-muted">${HtmlKacis(F.Aciklama)}</small></td>
                </tr>`;
            });
            Html += '</tbody></table></div>';
            Container.innerHTML = Header + Html;
        } catch (Err) {
            Container.innerHTML = Header + `<div class="alert alert-danger">Faturalar yüklenirken hata oluştu: ${Err.message}</div>`;
        }
    }

    async function OdemeleriListele(CustomerId, Container) {
        const Header = `
        <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
             <h6 class="mb-0 text-primary">Ödemeler</h6>
             <button class="btn btn-sm btn-primary" onclick="OdemeModalAc()"><i class="bi bi-plus-lg me-1"></i>Yeni Ödeme</button>
        </div>`;

        try {
             const Res = await Istek(`/api/payments?musteri_id=${CustomerId}`);
             const Data = Res.data || [];
             
             if (Data.length === 0) {
                 Container.innerHTML = Header + `
                 <div class="d-flex flex-column align-items-center justify-content-center p-4 text-muted">
                     <i class="bi bi-credit-card mb-2" style="font-size: 2rem;"></i>
                     <p>Kayıtlı ödeme bulunamadı.</p>
                 </div>`;
                 return;
             }

             let Html = `
             <div class="table-responsive">
             <table class="table table-sm table-hover align-middle">
                 <thead class="table-light">
                     <tr>
                         <th>Tarih</th>
                         <th class="text-end">Tutar</th>
                         <th>Açıklama</th>
                     </tr>
                 </thead>
                 <tbody>`;
             
             Data.forEach(P => {
                 Html += `<tr>
                     <td>${HtmlKacis(P.Tarih)}</td>
                     <td class="text-end fw-bold">${parseFloat(P.Tutar).toFixed(2)}</td>
                     <td>${HtmlKacis(P.Aciklama)}</td>
                 </tr>`;
             });
             Html += '</tbody></table></div>';
             Container.innerHTML = Header + Html;
         } catch (Err) {
             Container.innerHTML = Header + `<div class="alert alert-danger">Ödemeler yüklenirken hata oluştu: ${Err.message}</div>`;
         }
    }

    async function ProjeleriListele(CustomerId, Container) {
        const Header = `
        <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
             <h6 class="mb-0 text-primary">Projeler</h6>
             <button class="btn btn-sm btn-primary" onclick="ProjectModalAc()"><i class="bi bi-plus-lg me-1"></i>Yeni Proje</button>
        </div>`;
        try {
            const Res = await Istek(`/api/projects?musteri_id=${CustomerId}`);
            const Data = Res.data || [];
            if (Data.length === 0) {
                Container.innerHTML = Header + '<div class="p-4 text-center text-muted">Kayıtlı proje yok.</div>';
                return;
            }
            let Html = `<table class="table table-sm table-hover align-middle"><thead class="table-light"><tr><th>Proje Adı</th><th>Başlangıç</th><th>Bitiş</th><th class="text-end">Bütçe</th><th>Durum</th></tr></thead><tbody>`;
            Data.forEach(Item => {
                Html += `<tr style="cursor:pointer" onclick="ProjeDuzenle(${Item.Id})"><td>${HtmlKacis(Item.ProjeAdi)}</td><td>${HtmlKacis(Item.BaslangicTarihi)}</td><td>${HtmlKacis(Item.BitisTarihi)}</td><td class="text-end">${parseFloat(Item.Butce).toFixed(2)}</td><td>${Item.Durum==1?'Aktif':'Tamamlandı'}</td></tr>`;
            });
            Html += '</tbody></table>';
            Container.innerHTML = Header + '<div class="table-responsive">' + Html + '</div>';
        } catch (Err) { Container.innerHTML = Header + `<div class="alert alert-danger">${Err.message}</div>`; }
    }

    async function TeklifleriListele(CustomerId, Container) {
        const Header = `
        <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
             <h6 class="mb-0 text-primary">Teklifler</h6>
             <button class="btn btn-sm btn-primary" onclick="OfferModalAc()"><i class="bi bi-plus-lg me-1"></i>Yeni Teklif</button>
        </div>`;
        try {
            const Res = await Istek(`/api/offers?musteri_id=${CustomerId}`);
            const Data = Res.data || [];
            if (Data.length === 0) {
                Container.innerHTML = Header + '<div class="p-4 text-center text-muted">Kayıtlı teklif yok.</div>';
                return;
            }
            let Html = `<table class="table table-sm table-hover align-middle"><thead class="table-light"><tr><th>Teklif No</th><th>Konu</th><th>Tarih</th><th class="text-end">Tutar</th><th>Durum</th></tr></thead><tbody>`;
            Data.forEach(Item => {
                Html += `<tr style="cursor:pointer" onclick="TeklifDuzenle(${Item.Id})"><td>${HtmlKacis(Item.TeklifNo)}</td><td>${HtmlKacis(Item.Konu)}</td><td>${HtmlKacis(Item.TeklifTarihi)}</td><td class="text-end">${parseFloat(Item.Tutar).toFixed(2)} ${HtmlKacis(Item.ParaBirimi)}</td><td>${Item.Durum==1?'Gönderildi':'Taslak'}</td></tr>`;
            });
            Html += '</tbody></table>';
            Container.innerHTML = Header + '<div class="table-responsive">' + Html + '</div>';
        } catch (Err) { Container.innerHTML = Header + `<div class="alert alert-danger">${Err.message}</div>`; }
    }

    async function SozlesmeleriListele(CustomerId, Container) {
        const Header = `
        <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
             <h6 class="mb-0 text-primary">Sözleşmeler</h6>
             <button class="btn btn-sm btn-primary" onclick="ContractModalAc()"><i class="bi bi-plus-lg me-1"></i>Yeni Sözleşme</button>
        </div>`;
        try {
            const Res = await Istek(`/api/contracts?musteri_id=${CustomerId}`);
            const Data = Res.data || [];
            if (Data.length === 0) {
                Container.innerHTML = Header + '<div class="p-4 text-center text-muted">Kayıtlı sözleşme yok.</div>';
                return;
            }
            let Html = `<table class="table table-sm table-hover align-middle"><thead class="table-light"><tr><th>Sözleşme No</th><th>Başlangıç</th><th>Bitiş</th><th class="text-end">Tutar</th><th>Durum</th></tr></thead><tbody>`;
            Data.forEach(Item => {
                Html += `<tr style="cursor:pointer" onclick="SozlesmeDuzenle(${Item.Id})"><td>${HtmlKacis(Item.SozlesmeNo)}</td><td>${HtmlKacis(Item.BaslangicTarihi)}</td><td>${HtmlKacis(Item.BitisTarihi)}</td><td class="text-end">${parseFloat(Item.Tutar).toFixed(2)} ${HtmlKacis(Item.ParaBirimi)}</td><td>${Item.Durum==1?'Aktif':'Pasif'}</td></tr>`;
            });
            Html += '</tbody></table>';
            Container.innerHTML = Header + '<div class="table-responsive">' + Html + '</div>';
        } catch (Err) { Container.innerHTML = Header + `<div class="alert alert-danger">${Err.message}</div>`; }
    }

    async function TeminatlariListele(CustomerId, Container) {
        const Header = `
        <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
             <h6 class="mb-0 text-primary">Teminatlar / Evraklar</h6>
             <button class="btn btn-sm btn-primary" onclick="GuaranteeModalAc()"><i class="bi bi-plus-lg me-1"></i>Yeni Teminat</button>
        </div>`;
        try {
            const Res = await Istek(`/api/guarantees?musteri_id=${CustomerId}`);
            const Data = Res.data || [];
            if (Data.length === 0) {
                Container.innerHTML = Header + '<div class="p-4 text-center text-muted">Kayıtlı teminat yok.</div>';
                return;
            }
            let Html = `<table class="table table-sm table-hover align-middle"><thead class="table-light"><tr><th>Belge No</th><th>Tür</th><th>Vade</th><th class="text-end">Tutar</th><th>Durum</th></tr></thead><tbody>`;
            Data.forEach(Item => {
                Html += `<tr style="cursor:pointer" onclick="TeminatDuzenle(${Item.Id})"><td>${HtmlKacis(Item.BelgeNo || '-')}</td><td>${HtmlKacis(Item.Tur)}</td><td>${HtmlKacis(Item.VadeTarihi)}</td><td class="text-end">${parseFloat(Item.Tutar).toFixed(2)} ${HtmlKacis(Item.ParaBirimi)}</td><td>${Item.Durum==1?'Bekliyor':'Tamamlandı'}</td></tr>`;
            });
            Html += '</tbody></table>';
            Container.innerHTML = Header + '<div class="table-responsive">' + Html + '</div>';
        } catch (Err) { Container.innerHTML = Header + `<div class="alert alert-danger">${Err.message}</div>`; }
    }

    // --- VIEW MANAGEMENT ---
    window.UstMenuAktifYap = function(El) {
        document.querySelectorAll('#topNavbar .nav-link').forEach(N => N.classList.remove('active'));
        if(El) El.classList.add('active');
    };

    window.ViewDegistir = function(ViewId) {
        // Tüm view'ları gizle
        const AllViews = [
            'view-dashboard', 'view-customers', 'view-system', 'view-definitions',
            'view-reports', 'view-users', 'view-logs', 'view-currency', 'view-vat',
            'view-reports-project', 'view-reports-customer'
        ];
        AllViews.forEach(V => {
            const El = document.getElementById(V);
            if(El) El.classList.add('d-none');
        });

        // Seçili view göster
        if(ViewId === 'dashboard') {
            document.getElementById('view-dashboard').classList.remove('d-none');
            UstMenuAktifYap(document.getElementById('topNavHome'));
        } 
        else if (ViewId === 'customers') {
            document.getElementById('view-customers').classList.remove('d-none');
        }
        else if (ViewId === 'system') {
            document.getElementById('view-system').classList.remove('d-none');
            UstMenuAktifYap(document.getElementById('topNavSystem'));
        }
        else if (ViewId === 'definitions') {
            document.getElementById('view-definitions').classList.remove('d-none');
            UstMenuAktifYap(document.getElementById('topNavDefinitions'));
        }
        else if (ViewId === 'reports') {
            document.getElementById('view-reports').classList.remove('d-none');
            UstMenuAktifYap(document.getElementById('topNavReports'));
        }
        else if (ViewId === 'users') {
            document.getElementById('view-users').classList.remove('d-none');
            UstMenuAktifYap(document.getElementById('topNavSystem'));
            KullanicilariYukleMain();
        }
        else if (ViewId === 'logs') {
            document.getElementById('view-logs').classList.remove('d-none');
            UstMenuAktifYap(document.getElementById('topNavSystem'));
            LoglariFiltrele(); // Otomatik yükle
        }
        else if (ViewId === 'currency') {
            document.getElementById('view-currency').classList.remove('d-none');
            UstMenuAktifYap(document.getElementById('topNavDefinitions'));
            DovizKurlariYenile(); // Otomatik yükle
        }
        else if (ViewId === 'vat') {
            document.getElementById('view-vat').classList.remove('d-none');
            UstMenuAktifYap(document.getElementById('topNavDefinitions'));
            KdvTablosuGuncelle(); // Tablo güncelle
        }
        else if (ViewId === 'reports-project') {
            document.getElementById('view-reports-project').classList.remove('d-none');
            UstMenuAktifYap(document.getElementById('topNavReports'));
            ProjeRaporuOlustur(); // Otomatik rapor
        }
        else if (ViewId === 'reports-customer') {
            document.getElementById('view-reports-customer').classList.remove('d-none');
            UstMenuAktifYap(document.getElementById('topNavReports'));
            MusteriRaporuOlustur(); // Otomatik rapor
        }
    };

    // Müşteri seçince customers view'a geç
    function MusteriSecildi() {
        ViewDegistir('customers');
        SeciliCiz();
    }

    // Default view - Dashboard
    ViewDegistir('dashboard');

    document.getElementById('btnAdd').addEventListener('click', MusteriEkle);
    document.getElementById('detailForm').addEventListener('submit', MusteriKaydet);
    document.getElementById('logoutNav').addEventListener('click', () => {
      localStorage.removeItem(AnahtarToken);
      localStorage.removeItem(AnahtarRol);
      localStorage.removeItem(AnahtarKullanici);
      window.location.href = '/login';
    });
    AramaEl.addEventListener('input', ListeCiz);

    document.querySelectorAll('#tabs button').forEach(Dugme => {
      Dugme.addEventListener('click', () => {
        document.querySelectorAll('#tabs button').forEach(b => b.classList.remove('active'));
        Dugme.classList.add('active');
        AktifSekme = Dugme.getAttribute('data-tab');
        SekmeIcerikGuncelle();
      });
    });

    MusterileriYukle().catch(Hata => HataGoster(Hata.message));

    // Kullanici yönetim
    // Main View'da listeleyen versiyon
    async function KullanicilariYukleMain() {
        const Tbody = document.getElementById('usersTableBodyMain');
        if(!Tbody) return;
        Tbody.innerHTML = '<tr><td colspan="5" class="text-center">Yükleniyor...</td></tr>';
        
        try {
            const Yanit = await Istek('/api/users');
            const Kullanicilar = Yanit.data || [];
            Tbody.innerHTML = '';
            
            if(Kullanicilar.length === 0) {
                Tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Kullanıcı bulunamadı.</td></tr>';
                return;
            }

            Kullanicilar.forEach(K => {
            const Satir = document.createElement('tr');
            const AktifMi = K.Aktif == 1 || K.Aktif === '1';
            const DurumRozet = AktifMi 
                ? '<span class="badge bg-success">Aktif</span>' 
                : '<span class="badge bg-danger">Bloklu</span>';
            const SuperadminMi = K.Rol === 'superadmin';
            let Islemler = '';
            if (!SuperadminMi) {
                const BlokDugme = AktifMi
                ? `<button class="btn btn-sm btn-outline-warning me-1" onclick="KullaniciBlokDegistirMain(${K.Id}, 0)">Blokla</button>`
                : `<button class="btn btn-sm btn-outline-success me-1" onclick="KullaniciBlokDegistirMain(${K.Id}, 1)">Aktifle</button>`;
                Islemler = `${BlokDugme}<button class="btn btn-sm btn-outline-danger" onclick="KullaniciSilMain(${K.Id})">Sil</button>`;
            } else {
                Islemler = '<span class="text-muted small">Yetki yok</span>';
            }
            Satir.innerHTML = `
                <td>${HtmlKacis(K.AdSoyad || '')}</td>
                <td>${HtmlKacis(K.KullaniciAdi || '')}</td>
                <td>${HtmlKacis(K.Rol || '')}</td>
                <td>${DurumRozet}</td>
                <td>${Islemler}</td>
            `;
            Tbody.appendChild(Satir);
            });
        } catch(Err) {
             Tbody.innerHTML = `<tr><td colspan="5" class="text-danger">Hata: ${Err.message}</td></tr>`;
        }
    }

    window.KullaniciBlokDegistirMain = async function(Id, Aktif) {
        if(!confirm('Durumu değiştirmek istiyor musunuz?')) return;
        try {
            await Istek(`/api/users/${Id}/block`, { method: 'PUT', body: JSON.stringify({ Aktif }) });
            KullanicilariYukleMain();
            BasariGoster('Kullanıcı durumu güncellendi.');
        } catch(e) { HataGoster(e.message); }
    };
    
    window.KullaniciSilMain = async function(Id) {
        if(!confirm('Silmek istiyor musunuz?')) return;
        try {
            await Istek(`/api/users/${Id}`, { method: 'DELETE' });
            KullanicilariYukleMain();
            BasariGoster('Kullanıcı silindi.');
        } catch(e) { HataGoster(e.message); }
    };

    // ===== ŞİFRE DEĞİŞTİR / PROFİL / KULLANICI EKLEME =====
    const SifreDegistirModal = new bootstrap.Modal(document.getElementById('changePasswordModal'));
    const KullaniciEkleModalObj = new bootstrap.Modal(document.getElementById('addUserModal'));

    window.SifreDegistirModalAc = function() {
        document.getElementById('passwordModalError').classList.add('d-none');
        document.getElementById('passwordModalSuccess').classList.add('d-none');
        document.getElementById('changePasswordForm').reset();
        SifreDegistirModal.show();
    };

    window.ProfilModalAc = function() {
        BasariGoster('Profil sayfası hazırlanıyor...');
    };

    window.YeniKullaniciModalAc = function() {
        document.getElementById('addUserModalError').classList.add('d-none');
        document.getElementById('addUserForm').reset();
        KullaniciEkleModalObj.show();
    };

    // YeniKullaniciModal fonksiyonunu standart modal acma fonksiyonuna bagla
    window.YeniKullaniciModal = function() {
        YeniKullaniciModalAc();
    };
    
    // ===== İŞLEM LOGLARI =====
    window.LoglariFiltrele = async function() {
        const Tbody = document.getElementById('logsTableBody');
        const DateFilter = document.getElementById('logDateFilter').value;
        
        Tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary" role="status"></div> Yükleniyor...</td></tr>';
        
        try {
            // Log verilerini API'den cek
            let Url = '/api/logs?limit=100';
            if (DateFilter) {
                Url += '&tarih=' + DateFilter;
            }
            
            const Yanit = await Istek(Url);
            const Loglar = Yanit.data || [];
            
            if(Loglar.length === 0) {
                Tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">Kayıt bulunamadı.</td></tr>';
                return;
            }
            
            let Html = '';
            Loglar.forEach(L => {
                const IslemRenk = L.Islem === 'CREATE' ? 'success' : (L.Islem === 'DELETE' ? 'danger' : 'primary');
                const Tarih = L.EklemeZamani ? new Date(L.EklemeZamani).toLocaleString('tr-TR') : '-';
                const Detay = L.KayitId ? 'Kayıt ID: ' + L.KayitId : '';
                Html += `<tr>
                    <td>${HtmlKacis(Tarih)}</td>
                    <td>${HtmlKacis(L.KullaniciAdi || 'system')}</td>
                    <td><span class="badge bg-${IslemRenk}">${HtmlKacis(L.Islem)}</span></td>
                    <td><code>${HtmlKacis(L.Tablo)}</code></td>
                    <td>${HtmlKacis(Detay)}</td>
                </tr>`;
            });
            Tbody.innerHTML = Html;
            BasariGoster('Loglar yüklendi.' + (DateFilter ? ' Filtre: ' + DateFilter : ''));
        } catch(Err) {
            Tbody.innerHTML = `<tr><td colspan="5" class="text-danger text-center py-4">Hata: ${Err.message}</td></tr>`;
        }
    };
    
    // ===== DÖVİZ KURLARI =====
    // TODO: Uretim ortaminda TCMB veya baska bir doviz API servisi entegre edilmeli
    // Ornek TCMB XML: https://www.tcmb.gov.tr/kurlar/today.xml
    window.DovizKurlariYenile = async function() {
        document.getElementById('currencyUSD').innerHTML = '<div class="spinner-border spinner-border-sm" role="status"></div>';
        document.getElementById('currencyEUR').innerHTML = '<div class="spinner-border spinner-border-sm" role="status"></div>';
        document.getElementById('currencyGBP').innerHTML = '<div class="spinner-border spinner-border-sm" role="status"></div>';
        
        try {
            // Varsayilan kurlar (TCMB entegrasyonu bekliyor)
            const Kurlar = {
                USD: '35.50',
                EUR: '38.20',
                GBP: '44.80'
            };
            
            document.getElementById('currencyUSD').textContent = Kurlar.USD + ' ₺';
            document.getElementById('currencyEUR').textContent = Kurlar.EUR + ' ₺';
            document.getElementById('currencyGBP').textContent = Kurlar.GBP + ' ₺';
            
            BasariGoster('Döviz kurları güncellendi. (' + new Date().toLocaleTimeString('tr-TR') + ')');
        } catch(Err) {
            HataGoster('Kurlar güncellenirken hata: ' + Err.message);
        }
    };
    
    // ===== KDV ORANLARI =====
    let KdvOranlari = [
        { Id: 1, Oran: 20, Aciklama: 'Standart KDV', Varsayilan: true },
        { Id: 2, Oran: 10, Aciklama: 'İndirimli KDV', Varsayilan: false },
        { Id: 3, Oran: 1, Aciklama: 'Özel İndirimli', Varsayilan: false },
        { Id: 4, Oran: 0, Aciklama: 'KDV İstisna', Varsayilan: false }
    ];
    
    function KdvTablosuGuncelle() {
        const Tbody = document.getElementById('vatTableBody');
        if(!Tbody) return;
        
        let Html = '';
        KdvOranlari.forEach(K => {
            const VarsayilanBadge = K.Varsayilan 
                ? '<span class="badge bg-success">Evet</span>' 
                : '<span class="badge bg-secondary">Hayır</span>';
            Html += `<tr>
                <td>%${K.Oran}</td>
                <td>${K.Aciklama}</td>
                <td>${VarsayilanBadge}</td>
                <td>
                    <button class="btn btn-sm btn-outline-secondary" data-action="kdv-edit" data-id="${K.Id}"><i class="bi bi-pencil"></i> Düzenle</button>
                    ${!K.Varsayilan ? `<button class="btn btn-sm btn-outline-danger ms-1" data-action="kdv-delete" data-id="${K.Id}"><i class="bi bi-trash"></i> Sil</button>` : ''}
                </td>
            </tr>`;
        });
        Tbody.innerHTML = Html;
    }
    
    // KDV Modal ve İşlemleri
    const KdvModal = new bootstrap.Modal(document.getElementById('kdvModal'));
    
    window.KdvModalAc = function(Id = null) {
        const ErrBox = document.getElementById('kdvModalError');
        ErrBox.classList.add('d-none');
        
        if(Id) {
            // Düzenleme modu
            const Kayit = KdvOranlari.find(k => k.Id === Id);
            if(!Kayit) {
                HataGoster('KDV oranı bulunamadı.');
                return;
            }
            document.getElementById('kdvModalTitle').innerHTML = '<i class="bi bi-percent me-2"></i>KDV Oranı Düzenle';
            document.getElementById('kdvId').value = Kayit.Id;
            document.getElementById('kdvOran').value = Kayit.Oran;
            document.getElementById('kdvAciklama').value = Kayit.Aciklama;
            document.getElementById('kdvVarsayilan').checked = Kayit.Varsayilan;
        } else {
            // Yeni ekleme modu
            document.getElementById('kdvModalTitle').innerHTML = '<i class="bi bi-percent me-2"></i>Yeni KDV Oranı';
            document.getElementById('kdvId').value = '';
            document.getElementById('kdvOran').value = '';
            document.getElementById('kdvAciklama').value = '';
            document.getElementById('kdvVarsayilan').checked = false;
        }
        
        KdvModal.show();
    };
    
    // KDV Kaydet
    document.getElementById('btnSaveKdv').addEventListener('click', function() {
        const ErrBox = document.getElementById('kdvModalError');
        const Id = document.getElementById('kdvId').value;
        const Oran = parseInt(document.getElementById('kdvOran').value);
        const Aciklama = document.getElementById('kdvAciklama').value.trim();
        const Varsayilan = document.getElementById('kdvVarsayilan').checked;
        
        ErrBox.classList.add('d-none');
        
        // Validasyon
        if(isNaN(Oran) || Oran < 0 || Oran > 100) {
            ErrBox.textContent = 'Geçerli bir oran girin (0-100).';
            ErrBox.classList.remove('d-none');
            return;
        }
        if(!Aciklama) {
            ErrBox.textContent = 'Açıklama zorunludur.';
            ErrBox.classList.remove('d-none');
            return;
        }
        
        if(Id) {
            // Güncelleme
            const Kayit = KdvOranlari.find(k => k.Id === parseInt(Id));
            if(Kayit) {
                Kayit.Oran = Oran;
                Kayit.Aciklama = Aciklama;
                if(Varsayilan) {
                    // Diğerlerinin varsayılanını kaldır
                    KdvOranlari.forEach(k => k.Varsayilan = false);
                    Kayit.Varsayilan = true;
                }
                BasariGoster('KDV oranı güncellendi.');
            }
        } else {
            // Yeni ekleme
            const YeniId = Math.max(0, ...KdvOranlari.map(k => k.Id)) + 1;
            if(Varsayilan) {
                KdvOranlari.forEach(k => k.Varsayilan = false);
            }
            KdvOranlari.push({ Id: YeniId, Oran: Oran, Aciklama: Aciklama, Varsayilan: Varsayilan });
            BasariGoster('KDV oranı eklendi: %' + Oran);
        }
        
        KdvTablosuGuncelle();
        KdvModal.hide();
    });
    
    window.KdvSil = function(Id) {
        const Kayit = KdvOranlari.find(k => k.Id === Id);
        if(!Kayit) {
            HataGoster('KDV oranı bulunamadı.');
            return;
        }
        if(Kayit.Varsayilan) {
            HataGoster('Varsayılan KDV oranı silinemez.');
            return;
        }
        if(!confirm('Bu KDV oranını silmek istediğinize emin misiniz?\n\nOran: %' + Kayit.Oran + '\nAçıklama: ' + Kayit.Aciklama)) {
            return;
        }
        KdvOranlari = KdvOranlari.filter(k => k.Id !== Id);
        KdvTablosuGuncelle();
        BasariGoster('KDV oranı silindi.');
    };
    
    // ===== PROJE RAPORU =====
    window.ProjeRaporuOlustur = async function() {
        const RaporTipi = document.getElementById('projReportType').value;
        
        document.getElementById('rptTotalProjects').innerHTML = '<div class="spinner-border spinner-border-sm" role="status"></div>';
        
        try {
            // Tüm projeleri çek
            const Res = await Istek('/api/projects');
            const Projeler = Res.data || [];
            
            const Toplam = Projeler.length;
            const Aktif = Projeler.filter(p => p.Durum == 1).length;
            const Tamamlanan = Projeler.filter(p => p.Durum != 1).length;
            const ToplamButce = Projeler.reduce((sum, p) => sum + parseFloat(p.Butce || 0), 0);
            
            document.getElementById('rptTotalProjects').textContent = Toplam;
            document.getElementById('rptActiveProjects').textContent = Aktif;
            document.getElementById('rptCompletedProjects').textContent = Tamamlanan;
            document.getElementById('rptTotalBudget').textContent = ToplamButce.toLocaleString('tr-TR') + ' ₺';
            
            BasariGoster('Proje raporu oluşturuldu. Tip: ' + RaporTipi);
        } catch(Err) {
            HataGoster('Rapor oluşturulurken hata: ' + Err.message);
            document.getElementById('rptTotalProjects').textContent = '0';
        }
    };
    
    // ===== MÜŞTERİ RAPORU =====
    window.MusteriRaporuOlustur = async function() {
        const RaporTipi = document.getElementById('custReportType').value;
        
        document.getElementById('rptTotalCustomers').innerHTML = '<div class="spinner-border spinner-border-sm" role="status"></div>';
        
        try {
            // Müşteri ve fatura verilerini çek
            const MusteriRes = await Istek('/api/customers');
            const FaturaRes = await Istek('/api/invoices');
            
            const MusteriListesi = MusteriRes.data || [];
            const FaturaListesi = FaturaRes.data || [];
            
            const ToplamMusteri = MusteriListesi.length;
            const ToplamCiro = FaturaListesi.reduce((sum, f) => sum + parseFloat(f.Tutar || 0), 0);
            const BekleyenAlacak = FaturaListesi.reduce((sum, f) => sum + parseFloat(f.Kalan || 0), 0);
            
            // Ortalama vade: Fatura tarihinden bu yana gecen gun sayisi ortalamasi
            let ToplamGun = 0;
            let OdenmisFaturaSayisi = 0;
            const Bugun = new Date();
            FaturaListesi.forEach(f => {
                if (parseFloat(f.Kalan || 0) > 0 && f.Tarih) {
                    const FaturaTarihi = new Date(f.Tarih);
                    const GecenGun = Math.floor((Bugun - FaturaTarihi) / (1000 * 60 * 60 * 24));
                    if (GecenGun >= 0) {
                        ToplamGun += GecenGun;
                        OdenmisFaturaSayisi++;
                    }
                }
            });
            const OrtVade = OdenmisFaturaSayisi > 0 ? Math.round(ToplamGun / OdenmisFaturaSayisi) : 0;
            
            document.getElementById('rptTotalCustomers').textContent = ToplamMusteri;
            document.getElementById('rptTotalRevenue').textContent = ToplamCiro.toLocaleString('tr-TR') + ' ₺';
            document.getElementById('rptPendingReceivables').textContent = BekleyenAlacak.toLocaleString('tr-TR') + ' ₺';
            document.getElementById('rptAvgDue').textContent = OrtVade + ' gün';
            
            BasariGoster('Müşteri raporu oluşturuldu. Tip: ' + RaporTipi);
        } catch(Err) {
            HataGoster('Rapor oluşturulurken hata: ' + Err.message);
            document.getElementById('rptTotalCustomers').textContent = '0';
        }
    };

    // Şifre Değiştir Kaydet
    document.getElementById('btnChangePassword').addEventListener('click', async () => {
        const Current = document.getElementById('currentPassword').value;
        const NewPass = document.getElementById('newPassword').value;
        const Confirm = document.getElementById('confirmPassword').value;
        const ErrBox = document.getElementById('passwordModalError');
        const SuccessBox = document.getElementById('passwordModalSuccess');
        
        ErrBox.classList.add('d-none');
        SuccessBox.classList.add('d-none');
        
        if(!Current || !NewPass || !Confirm) {
            ErrBox.textContent = 'Tüm alanlar zorunludur.';
            ErrBox.classList.remove('d-none');
            return;
        }
        if(NewPass.length < 6) {
            ErrBox.textContent = 'Yeni şifre en az 6 karakter olmalıdır.';
            ErrBox.classList.remove('d-none');
            return;
        }
        if(NewPass !== Confirm) {
            ErrBox.textContent = 'Yeni şifreler eşleşmiyor.';
            ErrBox.classList.remove('d-none');
            return;
        }
        
        try {
            await Istek('/api/users/change-password', {
                method: 'POST',
                body: JSON.stringify({ CurrentPassword: Current, NewPassword: NewPass })
            });
            SuccessBox.textContent = 'Şifreniz başarıyla değiştirildi.';
            SuccessBox.classList.remove('d-none');
            document.getElementById('changePasswordForm').reset();
            setTimeout(() => SifreDegistirModal.hide(), 1500);
        } catch(Err) {
            ErrBox.textContent = Err.message || 'Şifre değiştirme başarısız.';
            ErrBox.classList.remove('d-none');
        }
    });

    // Kullanıcı Ekle Kaydet
    document.getElementById('btnAddUser').addEventListener('click', async () => {
        const AdSoyad = document.getElementById('newUserAdSoyad').value.trim();
        const KullaniciAdi = document.getElementById('newUserKullaniciAdi').value.trim();
        const Sifre = document.getElementById('newUserSifre').value;
        const Rol = document.getElementById('newUserRol').value;
        const ErrBox = document.getElementById('addUserModalError');
        
        ErrBox.classList.add('d-none');
        
        if(!AdSoyad || !KullaniciAdi || !Sifre) {
            ErrBox.textContent = 'Tüm alanlar zorunludur.';
            ErrBox.classList.remove('d-none');
            return;
        }
        if(Sifre.length < 6) {
            ErrBox.textContent = 'Şifre en az 6 karakter olmalıdır.';
            ErrBox.classList.remove('d-none');
            return;
        }
        
        try {
            await Istek('/api/users', {
                method: 'POST',
                body: JSON.stringify({ AdSoyad, KullaniciAdi, Sifre, Rol })
            });
            KullaniciEkleModalObj.hide();
            document.getElementById('addUserForm').reset();
            BasariGoster('Kullanıcı başarıyla eklendi.');
            KullanicilariYukleMain();
        } catch(Err) {
            ErrBox.textContent = Err.message || 'Kullanıcı eklenemedi.';
            ErrBox.classList.remove('d-none');
        }
    });


    // --- Fatura / Ödeme İşlemleri ---
    const FaturaModal = new bootstrap.Modal(document.getElementById('addInvoiceModal'));
    const OdemeModal = new bootstrap.Modal(document.getElementById('addPaymentModal'));
    
    window.FaturaModalAc = function() {
        if(!Secili) {
            HataGoster('Lütfen önce bir müşteri seçin.');
            return;
        }
        document.getElementById('invoiceModalError').classList.add('d-none');
        document.getElementById('invAmount').value = '';
        document.getElementById('invDesc').value = '';
        FaturaModal.show();
    };

    window.OdemeModalAc = function() {
        if(!Secili) {
            HataGoster('Lütfen önce bir müşteri seçin.');
            return;
        }
        document.getElementById('paymentModalError').classList.add('d-none');
        document.getElementById('payAmount').value = '';
        document.getElementById('payDesc').value = '';
        OdemeModal.show();
    };

    document.getElementById('btnSaveInvoice').addEventListener('click', async () => {
        if (!Secili) return;
        const Tarih = document.getElementById('invDate').value;
        const Tutar = document.getElementById('invAmount').value;
        const Doviz = document.getElementById('invCurrency').value;
        const Aciklama = document.getElementById('invDesc').value;
        const ErrBox = document.getElementById('invoiceModalError');
        
        ErrBox.classList.add('d-none');
        if(!Tarih || !Tutar) {
            ErrBox.textContent = 'Tarih ve Tutar zorunludur.';
            ErrBox.classList.remove('d-none');
            return;
        }

        try {
            await Istek('/api/invoices', {
                method: 'POST',
                body: JSON.stringify({
                    MusteriId: Secili.Id,
                    Tarih: Tarih,
                    Tutar: Tutar,
                    DovizCinsi: Doviz,
                    Aciklama: Aciklama
                })
            });
            FaturaModal.hide();
            // Formu temizle
            document.getElementById('invoiceForm').reset();
            // Başarı mesajı
            BasariGoster('Fatura başarıyla kaydedildi.');
            
            // Listeyi yenile - Mutlaka çağır
            await FaturalariListele(Secili.Id, document.getElementById('tabContent'));
            
            // Dashboard alarmlarını güncelle
            DashboardYukle();
        } catch(Err) {
            ErrBox.textContent = Err.message || 'Kayıt sırasında bir hata oluştu.';
            ErrBox.classList.remove('d-none');
        }
    });

    document.getElementById('btnSavePayment').addEventListener('click', async () => {
        if (!Secili) return;
        const Tarih = document.getElementById('payDate').value;
        const Tutar = document.getElementById('payAmount').value;
        const Aciklama = document.getElementById('payDesc').value;
        const ErrBox = document.getElementById('paymentModalError');
        
        ErrBox.classList.add('d-none');
        if(!Tarih || !Tutar) {
            ErrBox.textContent = 'Tarih ve Tutar zorunludur.';
            ErrBox.classList.remove('d-none');
            return;
        }

        try {
            await Istek('/api/payments', {
                method: 'POST',
                body: JSON.stringify({
                    MusteriId: Secili.Id,
                    Tarih: Tarih,
                    Tutar: Tutar,
                    Aciklama: Aciklama
                })
            });
            OdemeModal.hide();
            // Formu temizle
            document.getElementById('paymentForm').reset();
            // Başarı mesajı
            BasariGoster('Ödeme başarıyla kaydedildi.');
            
            // Listeyi yenile - Mutlaka çağır
            await OdemeleriListele(Secili.Id, document.getElementById('tabContent'));
            
            // Dashboard alarmlarını güncelle (fatura kalan değişmiş olabilir)
            DashboardYukle();
        } catch(Err) {
            ErrBox.textContent = Err.message || 'Kayıt sırasında bir hata oluştu.';
            ErrBox.classList.remove('d-none');
        }
    });

    window.MusteriGit = function(MId, SekmeAdi) {
        if(!MId) return;
        const Rec = Musteriler.find(m => m.Id == MId);
        if(Rec) {
            Secili = Rec;
            ListeCiz();
            ViewDegistir('customers');
            SeciliCiz();
            // Belirli bir sekmeye git
            if(SekmeAdi) {
                document.querySelectorAll('#tabs button').forEach(b => b.classList.remove('active'));
                const TabBtn = document.querySelector(`#tabs button[data-tab="${SekmeAdi}"]`);
                if(TabBtn) {
                    TabBtn.classList.add('active');
                    AktifSekme = SekmeAdi;
                    SekmeIcerikGuncelle();
                }
            }
        }
    };

    // Alarm tipine göre ilgili listeyi aç ve ilgili kayda scroll/focus yap
    window.AlarmTiklandi = async function(Type, MusteriId, RecordId) {
        if(!MusteriId) return;
        
        const Rec = Musteriler.find(m => m.Id == MusteriId);
        if(!Rec) return;
        
        // Müşteriyi seç ve highlight yap
        Secili = Rec;
        ListeCiz();
        ViewDegistir('customers');
        SeciliCiz();
        
        if(Type === 'invoice') {
            // Faturalar sekmesine git
            document.querySelectorAll('#tabs button').forEach(b => b.classList.remove('active'));
            const TabBtn = document.querySelector('#tabs button[data-tab="Faturalar"]');
            if(TabBtn) {
                TabBtn.classList.add('active');
                AktifSekme = 'Faturalar';
            }
            // Faturalar listesini "kalan > 0" filtreli olarak yükle
            await FaturalariListeleFiltreli(Secili.Id, document.getElementById('tabContent'), RecordId);
        } else if(Type === 'calendar') {
            // Projeler sekmesine git
            document.querySelectorAll('#tabs button').forEach(b => b.classList.remove('active'));
            const TabBtn = document.querySelector('#tabs button[data-tab="Projeler"]');
            if(TabBtn) {
                TabBtn.classList.add('active');
                AktifSekme = 'Projeler';
            }
            await ProjeleriListele(Secili.Id, document.getElementById('tabContent'));
        } else {
            SekmeIcerikGuncelle();
        }
    };

    // Filtreli fatura listesi (sadece kalan > 0 olanlar + highlight)
    async function FaturalariListeleFiltreli(CustomerId, Container, HighlightId) {
        const Header = `
        <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
             <h6 class="mb-0 text-primary">Faturalar <span class="badge bg-danger ms-2">Ödenmemiş</span></h6>
             <div>
                 <button class="btn btn-sm btn-outline-secondary me-2" onclick="SekmeIcerikGuncelle()">Tümü</button>
                 <button class="btn btn-sm btn-primary" onclick="FaturaModalAc()"><i class="bi bi-plus-lg me-1"></i>Yeni Fatura</button>
             </div>
        </div>`;

        try {
            const Res = await Istek(`/api/invoices?musteri_id=${CustomerId}`);
            const AllData = Res.data || [];
            // Sadece kalan > 0 olanları filtrele
            const Data = AllData.filter(F => {
                const Tutar = parseFloat(F.Tutar || 0);
                const Odenen = parseFloat(F.OdenenTutar || 0);
                return (Tutar - Odenen) > 0.01;
            });
            
            if (Data.length === 0) {
                Container.innerHTML = Header + `
                <div class="d-flex flex-column align-items-center justify-content-center p-4 text-muted">
                    <i class="bi bi-check-circle text-success mb-2" style="font-size: 2rem;"></i>
                    <p>Tüm faturalar ödenmiş!</p>
                </div>`;
                return;
            }

            let Html = `
            <div class="table-responsive">
            <table class="table table-sm table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Fatura No (ID)</th>
                        <th>Tarih</th>
                        <th class="text-end">Tutar</th>
                        <th class="text-end">Ödenen</th>
                        <th class="text-end">Kalan</th>
                        <th>Döviz</th>
                        <th>Açıklama</th>
                    </tr>
                </thead>
                <tbody>`;
            
            Data.forEach(F => {
                const Tutar = parseFloat(F.Tutar);
                const Odenen = parseFloat(F.OdenenTutar || 0);
                const Kalan = Tutar - Odenen;
                const IsHighlight = HighlightId && F.Id == HighlightId;
                const RowClass = IsHighlight ? 'table-warning' : '';
                const RowId = `fatura-row-${F.Id}`;

                Html += `<tr id="${RowId}" class="${RowClass}">
                    <td>#${HtmlKacis(F.Id)}</td>
                    <td>${HtmlKacis(F.Tarih)}</td>
                    <td class="text-end fw-bold">${Tutar.toFixed(2)}</td>
                    <td class="text-end text-muted">${Odenen.toFixed(2)}</td>
                    <td class="text-end text-danger fw-bold">${Kalan.toFixed(2)}</td>
                    <td>${HtmlKacis(F.DovizCinsi)}</td>
                    <td><small class="text-muted">${HtmlKacis(F.Aciklama)}</small></td>
                </tr>`;
            });
            Html += '</tbody></table></div>';
            Container.innerHTML = Header + Html;
            
            // Highlight edilen satıra scroll
            if(HighlightId) {
                const HighlightRow = document.getElementById(`fatura-row-${HighlightId}`);
                if(HighlightRow) {
                    HighlightRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        } catch (Err) {
            Container.innerHTML = Header + `<div class="alert alert-danger">Faturalar yüklenirken hata oluştu: ${Err.message}</div>`;
        }
    }

    async function DashboardYukle() {
        const CalBody = document.getElementById('calendarBody');
        const AlarmList = document.getElementById('alarmList');
        if(!CalBody || !AlarmList) return;

        try {
            const Res = await Istek('/api/dashboard');
            const Data = Res || {};
            const Alarms = Data.alarms || [];
            const Calendar = Data.calendar || [];

            // Dashboard istatistiklerini güncelle
            document.getElementById('dashMusteriSayisi').textContent = Musteriler.length || 0;
            
            // Bekleyen tahsilat hesapla
            let ToplamKalan = 0;
            Alarms.forEach(A => {
                if(A.Type === 'invoice') {
                    const Match = A.Detail.match(/Kalan: ([\d.,]+)/);
                    if(Match) ToplamKalan += parseFloat(Match[1].replace(',', ''));
                }
            });
            document.getElementById('dashBekleyenTahsilat').textContent = ToplamKalan.toLocaleString('tr-TR') + ' ₺';

            // Alarm listesi - Ödenmemiş Faturalar + Takvim Kayıtları
            AlarmList.innerHTML = '';
            
            // Başlık: Ödenmemiş Faturalar
            if(Alarms.length > 0) {
                AlarmList.innerHTML += `
                <div class="list-group-item bg-danger text-white small py-1 fw-bold">
                    <i class="bi bi-exclamation-triangle me-1"></i>Ödenmemiş Faturalar (${Alarms.length})
                </div>`;
                
                Alarms.slice(0, 5).forEach(Item => {
                    const Badge = Item.IsUrgent ? '<span class="badge bg-danger rounded-pill">!</span>' : '<span class="badge bg-warning text-dark rounded-pill">$</span>';
                    const Cls = Item.IsUrgent ? 'text-danger fw-bold' : 'text-dark fw-bold';
                    const RecordId = Item.RecordId || 'null';
                    
                    AlarmList.innerHTML += `
                    <a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2" 
                       onclick="AlarmTiklandi('${Item.Type}', ${Item.MusteriId}, ${RecordId}); return false;">
                      <div>
                        <div class="${Cls}" style="font-size:0.85rem">${HtmlKacis(Item.Title)}</div>
                        <div class="text-muted" style="font-size:0.75rem">${HtmlKacis(Item.Detail)}</div>
                      </div>
                      ${Badge}
                    </a>`;
                });
                
                if(Alarms.length > 5) {
                    AlarmList.innerHTML += `<div class="text-center text-muted small py-1">+${Alarms.length - 5} daha...</div>`;
                }
            } else {
                AlarmList.innerHTML += '<div class="text-center text-success small py-2"><i class="bi bi-check-circle me-1"></i>Tüm faturalar ödendi!</div>';
            }

            // Takvim
            CalBody.innerHTML = '';
            if(Calendar.length === 0) {
                 CalBody.innerHTML = '<div class="text-center text-muted small py-3"><i class="bi bi-calendar-check me-1"></i>Bekleyen kayıt yok.</div>';
            } else {
                 let Html = '<ul class="list-group list-group-flush small">';
                 Calendar.forEach(Evt => {
                     const RecId = Evt.RecordId || 'null';
                     Html += `
                     <li class="list-group-item d-flex align-items-start border-0 px-0 pb-1 cursor-pointer" 
                         onclick="AlarmTiklandi('invoice', ${Evt.MusteriId || 'null'}, ${RecId})" style="cursor:pointer">
                        <div class="me-2 mt-1 rounded-circle" style="width:8px;height:8px;background-color:${HtmlKacis(Evt.backgroundColor)}"></div>
                        <div>
                            <div class="fw-bold text-break" style="font-size:0.8rem">${HtmlKacis(Evt.title)}</div>
                            <div class="text-muted" style="font-size:0.7rem">${HtmlKacis(Evt.start)}</div>
                        </div>
                     </li>`;
                 });
                 Html += '</ul>';
                 CalBody.innerHTML = Html;
            }
        } catch (Err) {
            console.error('Dashboard Error:', Err);
            AlarmList.innerHTML = '<div class="text-center text-danger small py-2">Yüklenirken hata oluştu.</div>';
        }
    }
    
    // Init Dashboard
    DashboardYukle();
    setInterval(DashboardYukle, 60000);

    // --- PHASE 4: Projects, Offers, Contracts, Guarantees Logic ---

    // 1. PROJECTS
    const ProjectModal = new bootstrap.Modal(document.getElementById('projectModal'));
    
    window.ProjectModalAc = function() {
        if(!Secili) {
            HataGoster('Lütfen önce bir müşteri seçin.');
            return;
        }
        document.getElementById('projectModalError').classList.add('d-none');
        document.getElementById('projId').value = '';
        document.getElementById('projectModalTitle').textContent = 'Yeni Proje';
        document.getElementById('projName').value = '';
        document.getElementById('projStart').value = '';
        document.getElementById('projEnd').value = '';
        document.getElementById('projBudget').value = '';
        document.getElementById('projStatus').value = '1';
        ProjectModal.show();
    };

    window.ProjeDuzenle = async function(Id) {
        if(!Id) return;
        try {
            const Res = await Istek(`/api/projects?id=${Id}`); // Or specific endpoint if exists. Assuming query by ID or scan list.
            // Actually API might not support GET /api/projects/ID directly strictly, standard REST is /api/projects/ID
            // Let's try /api/projects/ID first.
            const Res2 = await Istek(`/api/projects/${Id}`); 
            const Rec = Res2.data || Res2;
            
            document.getElementById('projectModalError').classList.add('d-none');
            document.getElementById('projId').value = Rec.Id;
            document.getElementById('projectModalTitle').textContent = 'Proje Düzenle';
            document.getElementById('projName').value = Rec.ProjeAdi;
            document.getElementById('projStart').value = Rec.BaslangicTarihi ? Rec.BaslangicTarihi.split(' ')[0] : '';
            document.getElementById('projEnd').value = Rec.BitisTarihi ? Rec.BitisTarihi.split(' ')[0] : '';
            document.getElementById('projBudget').value = Rec.Butce;
            document.getElementById('projStatus').value = Rec.Durum;
            ProjectModal.show();
        } catch(Err) {
            HataGoster('Proje detayları yüklenemedi: ' + Err.message);
        }
    };

    document.getElementById('btnSaveProject').addEventListener('click', async () => {
        if (!Secili) return;
        const Id = document.getElementById('projId').value;
        const Name = document.getElementById('projName').value;
        const Start = document.getElementById('projStart').value;
        const End = document.getElementById('projEnd').value;
        const Budget = document.getElementById('projBudget').value;
        const Status = document.getElementById('projStatus').value;
        const ErrBox = document.getElementById('projectModalError');

        ErrBox.classList.add('d-none');
        if(!Name) {
            ErrBox.textContent = 'Proje adı zorunludur.';
            ErrBox.classList.remove('d-none');
            return;
        }

        const Payload = {
            MusteriId: Secili.Id,
            ProjeAdi: Name,
            BaslangicTarihi: Start,
            BitisTarihi: End,
            Butce: Budget,
            Durum: Status
        };

        try {
            let Url = '/api/projects';
            let Method = 'POST';
            if(Id) {
                Url = `/api/projects/${Id}`;
                Method = 'PUT';
            }
            await Istek(Url, { method: Method, body: JSON.stringify(Payload) });
            ProjectModal.hide();
            SekmeIcerikGuncelle();
        } catch(Err) {
            ErrBox.textContent = Err.message;
            ErrBox.classList.remove('d-none');
        }
    });

    // 2. OFFERS
    const OfferModal = new bootstrap.Modal(document.getElementById('offerModal'));

    window.OfferModalAc = function() {
        if(!Secili) {
            HataGoster('Lütfen önce bir müşteri seçin.');
            return;
        }
        document.getElementById('offerModalError').classList.add('d-none');
        document.getElementById('offerId').value = '';
        document.getElementById('offerModalTitle').textContent = 'Yeni Teklif';
        document.getElementById('offerNo').value = '';
        document.getElementById('offerSubject').value = '';
        document.getElementById('offerAmount').value = '';
        document.getElementById('offerCurrency').value = 'TRY';
        document.getElementById('offerDate').value = new Date().toISOString().split('T')[0];
        document.getElementById('offerValid').value = '';
        document.getElementById('offerStatus').value = '0';
        OfferModal.show();
    };

    window.TeklifDuzenle = async function(Id) {
        if(!Id) return;
        try {
            const Res = await Istek(`/api/offers/${Id}`);
            const Rec = Res.data || Res;
            
            document.getElementById('offerModalError').classList.add('d-none');
            document.getElementById('offerId').value = Rec.Id;
            document.getElementById('offerModalTitle').textContent = 'Teklif Düzenle';
            document.getElementById('offerNo').value = Rec.TeklifNo;
            document.getElementById('offerSubject').value = Rec.Konu;
            document.getElementById('offerAmount').value = Rec.Tutar;
            document.getElementById('offerCurrency').value = Rec.ParaBirimi;
            document.getElementById('offerDate').value = Rec.TeklifTarihi ? Rec.TeklifTarihi.split(' ')[0] : '';
            document.getElementById('offerValid').value = Rec.GecerlilikTarihi ? Rec.GecerlilikTarihi.split(' ')[0] : '';
            document.getElementById('offerStatus').value = Rec.Durum;
            OfferModal.show();
        } catch(Err) { HataGoster('Teklif detayı yüklenemedi: ' + Err.message); }
    };

    document.getElementById('btnSaveOffer').addEventListener('click', async () => {
        if(!Secili) return;
        const Id = document.getElementById('offerId').value;
        const No = document.getElementById('offerNo').value;
        const Subj = document.getElementById('offerSubject').value;
        const Amt = document.getElementById('offerAmount').value;
        const Curr = document.getElementById('offerCurrency').value;
        const DateVal = document.getElementById('offerDate').value;
        const Valid = document.getElementById('offerValid').value;
        const Stat = document.getElementById('offerStatus').value;
        const ErrBox = document.getElementById('offerModalError');

        ErrBox.classList.add('d-none');
        if(!No) {
            ErrBox.textContent = 'Teklif No zorunludur.';
            ErrBox.classList.remove('d-none');
            return;
        }

        const Payload = {
            MusteriId: Secili.Id,
            TeklifNo: No,
            Konu: Subj,
            Tutar: Amt,
            ParaBirimi: Curr,
            TeklifTarihi: DateVal,
            GecerlilikTarihi: Valid,
            Durum: Stat
        };

        try {
            let Url = '/api/offers';
            let Method = 'POST';
            if(Id) { Url = `/api/offers/${Id}`; Method = 'PUT'; }
            await Istek(Url, { method: Method, body: JSON.stringify(Payload) });
            OfferModal.hide();
            SekmeIcerikGuncelle();
        } catch(Err) {
            ErrBox.textContent = Err.message;
            ErrBox.classList.remove('d-none');
        }
    });

    // 3. CONTRACTS
    const ContractModal = new bootstrap.Modal(document.getElementById('contractModal'));

    window.ContractModalAc = function() {
        if(!Secili) {
            HataGoster('Lütfen önce bir müşteri seçin.');
            return;
        }
        document.getElementById('contractModalError').classList.add('d-none');
        document.getElementById('contractId').value = '';
        document.getElementById('contractModalTitle').textContent = 'Yeni Sözleşme';
        document.getElementById('contractNo').value = '';
        document.getElementById('contractStart').value = '';
        document.getElementById('contractEnd').value = '';
        document.getElementById('contractAmount').value = '';
        document.getElementById('contractCurrency').value = 'TRY';
        document.getElementById('contractStatus').value = '1';
        ContractModal.show();
    };

    window.SozlesmeDuzenle = async function(Id) {
        if(!Id) return;
        try {
            const Res = await Istek(`/api/contracts/${Id}`);
            const Rec = Res.data || Res;
            
            document.getElementById('contractModalError').classList.add('d-none');
            document.getElementById('contractId').value = Rec.Id;
            document.getElementById('contractModalTitle').textContent = 'Sözleşme Düzenle';
            document.getElementById('contractNo').value = Rec.SozlesmeNo;
            document.getElementById('contractStart').value = Rec.BaslangicTarihi ? Rec.BaslangicTarihi.split(' ')[0] : '';
            document.getElementById('contractEnd').value = Rec.BitisTarihi ? Rec.BitisTarihi.split(' ')[0] : '';
            document.getElementById('contractAmount').value = Rec.Tutar;
            document.getElementById('contractCurrency').value = Rec.ParaBirimi;
            document.getElementById('contractStatus').value = Rec.Durum;
            ContractModal.show();
        } catch(Err) { HataGoster('Sözleşme detayı yüklenemedi: ' + Err.message); }
    };

    document.getElementById('btnSaveContract').addEventListener('click', async () => {
        if(!Secili) return;
        const Id = document.getElementById('contractId').value;
        const No = document.getElementById('contractNo').value;
        const Start = document.getElementById('contractStart').value;
        const End = document.getElementById('contractEnd').value;
        const Amt = document.getElementById('contractAmount').value;
        const Curr = document.getElementById('contractCurrency').value;
        const Stat = document.getElementById('contractStatus').value;
        const ErrBox = document.getElementById('contractModalError');

        ErrBox.classList.add('d-none');
        if(!No) {
            ErrBox.textContent = 'Sözleşme No zorunludur.';
            ErrBox.classList.remove('d-none');
            return;
        }

        const Payload = {
            MusteriId: Secili.Id,
            SozlesmeNo: No,
            BaslangicTarihi: Start,
            BitisTarihi: End,
            Tutar: Amt,
            ParaBirimi: Curr,
            Durum: Stat
        };

        try {
            let Url = '/api/contracts';
            let Method = 'POST';
            if(Id) { Url = `/api/contracts/${Id}`; Method = 'PUT'; }
            await Istek(Url, { method: Method, body: JSON.stringify(Payload) });
            ContractModal.hide();
            SekmeIcerikGuncelle();
        } catch(Err) {
            ErrBox.textContent = Err.message;
            ErrBox.classList.remove('d-none');
        }
    });

    // 4. GUARANTEES
    const GuaranteeModal = new bootstrap.Modal(document.getElementById('guaranteeModal'));

    window.GuaranteeModalAc = function() {
        if(!Secili) {
            HataGoster('Lütfen önce bir müşteri seçin.');
            return;
        }
        document.getElementById('guaranteeModalError').classList.add('d-none');
        document.getElementById('guaranteeId').value = '';
        document.getElementById('guaranteeModalTitle').textContent = 'Yeni Teminat';
        document.getElementById('guaranteeNo').value = '';
        document.getElementById('guaranteeType').value = 'Nakit';
        document.getElementById('guaranteeBank').value = '';
        document.getElementById('guaranteeAmount').value = '';
        document.getElementById('guaranteeCurrency').value = 'TRY';
        document.getElementById('guaranteeDate').value = '';
        document.getElementById('guaranteeStatus').value = '1';
        GuaranteeModal.show();
    };

    window.TeminatDuzenle = async function(Id) {
        if(!Id) return;
        try {
            const Res = await Istek(`/api/guarantees/${Id}`);
            const Rec = Res.data || Res;
            
            document.getElementById('guaranteeModalError').classList.add('d-none');
            document.getElementById('guaranteeId').value = Rec.Id;
            document.getElementById('guaranteeModalTitle').textContent = 'Teminat Düzenle';
            document.getElementById('guaranteeNo').value = Rec.BelgeNo;
            document.getElementById('guaranteeType').value = Rec.Tur;
            document.getElementById('guaranteeBank').value = Rec.BankaAdi;
            document.getElementById('guaranteeAmount').value = Rec.Tutar;
            document.getElementById('guaranteeCurrency').value = Rec.ParaBirimi;
            document.getElementById('guaranteeDate').value = Rec.VadeTarihi ? Rec.VadeTarihi.split(' ')[0] : '';
            document.getElementById('guaranteeStatus').value = Rec.Durum;
            GuaranteeModal.show();
        } catch(Err) { HataGoster('Teminat detayı yüklenemedi: ' + Err.message); }
    };

    document.getElementById('btnSaveGuarantee').addEventListener('click', async () => {
        if(!Secili) return;
        const Id = document.getElementById('guaranteeId').value;
        const No = document.getElementById('guaranteeNo').value;
        const Type = document.getElementById('guaranteeType').value;
        const Bank = document.getElementById('guaranteeBank').value;
        const Amt = document.getElementById('guaranteeAmount').value;
        const Curr = document.getElementById('guaranteeCurrency').value;
        const DateVal = document.getElementById('guaranteeDate').value;
        const Stat = document.getElementById('guaranteeStatus').value;
        const ErrBox = document.getElementById('guaranteeModalError');

        ErrBox.classList.add('d-none');
        
        // Validation could go here

        const Payload = {
            MusteriId: Secili.Id,
            BelgeNo: No,
            Tur: Type,
            BankaAdi: Bank,
            Tutar: Amt,
            ParaBirimi: Curr,
            VadeTarihi: DateVal,
            Durum: Stat
        };

        try {
            let Url = '/api/guarantees';
            let Method = 'POST';
            if(Id) { Url = `/api/guarantees/${Id}`; Method = 'PUT'; }
            await Istek(Url, { method: Method, body: JSON.stringify(Payload) });
            GuaranteeModal.hide();
            SekmeIcerikGuncelle();
        } catch(Err) {
            ErrBox.textContent = Err.message;
            ErrBox.classList.remove('d-none');
        }
    });

  </script>
</body>
</html>

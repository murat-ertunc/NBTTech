<?php
/**
 * Header Partial - Navbar ve Head içerir
 * Server-Rendered Sayfa Mimarisi
 * 
 * RBAC: Navbar menüleri server-side permission kontrolü ile render edilir.
 * Permission yoksa element DOM'a hiç eklenmez.
 * 
 * Kullanım:
 *   $pageTitle = 'Sayfa Adı';
 *   $activeNav = 'dashboard'; // dashboard, customers, islemler, sistem
 *   require __DIR__ . '/partials/header.php';
 */

require_once __DIR__ . '/../../../app/Core/bootstrap.php';

use App\Core\Token;
use App\Services\Authorization\AuthorizationService;

// Cache önleme header'ları
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
header('Content-Type: text/html; charset=UTF-8');

$UygulamaAdi = config('app.name', 'NbtProject');
$Logo = config('app.logo', '/assets/logo.png');
$PaginationDefault = env('PAGINATION_DEFAULT', 10);

// Sayfa değişkenleri (çağıran sayfa tarafından set edilebilir)
// $pageTitle  - Sayfa başlığı (browser tab)
// $activeNav  - Navbar'da aktif menü grubu (dashboard, customers, islemler, sistem)
// $currentPage - Hangi modülün init edileceği (dashboard, customers, customer, invoices, payments, vb.)
$pageTitle = $pageTitle ?? 'Ana Sayfa';
$activeNav = $activeNav ?? 'dashboard';
$currentPage = $currentPage ?? $activeNav; // Varsayılan: activeNav ile aynı

$FrontendPermissions = [
  'roller' => [],
  'permissionlar' => [],
  'moduller' => []
];

// Kullanici yetkileri yukle
$AuthUserId = null;
try {
  $TokenStr = $_COOKIE['nbt_token'] ?? null;
  if (!empty($TokenStr)) {
    $Payload = Token::verify($TokenStr);
    if (is_array($Payload) && isset($Payload['userId'])) {
      $AuthUserId = (int) $Payload['userId'];
      $FrontendPermissions = AuthorizationService::getInstance()->frontendIcinYetkiler($AuthUserId);
    }
  }
} catch (\Throwable $E) {
  $FrontendPermissions = [
    'roller' => [],
    'permissionlar' => [],
    'moduller' => []
  ];
}

// ===== SERVER-SIDE PERMISSION HELPER FONKSIYONLARI =====
$PermissionList = $FrontendPermissions['permissionlar'] ?? [];
$PermissionSet = array_flip($PermissionList);

/**
 * Tek permission kontrolü
 * @param string $permission
 * @return bool
 */
$can = function(string $permission) use ($PermissionSet): bool {
  return isset($PermissionSet[$permission]);
};

/**
 * Herhangi birine sahip mi?
 * @param array $permissions
 * @return bool
 */
$canAny = function(array $permissions) use ($PermissionSet): bool {
  foreach ($permissions as $p) {
    if (isset($PermissionSet[$p])) return true;
  }
  return false;
};

/**
 * Tümüne sahip mi?
 * @param array $permissions
 * @return bool
 */
$canAll = function(array $permissions) use ($PermissionSet): bool {
  foreach ($permissions as $p) {
    if (!isset($PermissionSet[$p])) return false;
  }
  return true;
};
?>
<!doctype html>
<html lang="tr">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($pageTitle . ' - ' . $UygulamaAdi, ENT_QUOTES, 'UTF-8') ?></title>
  <link rel="stylesheet" href="/assets/bootstrap.min.css" />
  <link rel="stylesheet" href="/assets/vendor/bootstrap-icons/bootstrap-icons.min.css" />
  <link rel="stylesheet" href="/assets/vendor/sweetalert2/sweetalert2.min.css" />
  <script src="/assets/vendor/sweetalert2/sweetalert2.all.min.js"></script>
  <script>
    window.APP_CONFIG = {
      PAGINATION_DEFAULT: <?= (int) $PaginationDefault ?>,
      CURRENT_PAGE: '<?= htmlspecialchars($currentPage, ENT_QUOTES, 'UTF-8') ?>',
      APP_ENV: '<?= htmlspecialchars((string) env('APP_ENV', 'local'), ENT_QUOTES, 'UTF-8') ?>'
    };
  </script>
  <script>
    window.__PERMS__ = <?= json_encode($FrontendPermissions['permissionlar'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    window.__ROLES__ = <?= json_encode($FrontendPermissions['roller'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
  </script>
  <link rel="stylesheet" href="/assets/app.css" />
</head>

<body class="bg-light">

  <nav class="navbar navbar-expand-lg fixed-top shadow" id="mainNav"
    style="background: linear-gradient(135deg, #1a237e 0%, #3949ab 50%, #5c6bc0 100%);">
    <div class="container-fluid px-3 px-lg-4">
      <a class="navbar-brand d-flex align-items-center gap-2 fw-bold text-white py-1" href="/dashboard">
        <div class="bg-white rounded-2 p-1 d-flex align-items-center justify-content-center"
          style="width:38px;height:38px;">
          <img id="brandLogo" src="<?= htmlspecialchars($Logo, ENT_QUOTES, 'UTF-8') ?>" alt="Logo"
            style="height:28px;width:auto;" />
        </div>
        <span id="brandName" class="fs-5"><?= htmlspecialchars($UygulamaAdi, ENT_QUOTES, 'UTF-8') ?></span>
      </a>

      <button class="navbar-toggler border-0 text-white" type="button" data-bs-toggle="collapse"
        data-bs-target="#mainNavbar">
        <i class="bi bi-list fs-4"></i>
      </button>

      <div class="collapse navbar-collapse" id="mainNavbar">
        <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-4">

          <?php if ($can('dashboard.read')): ?>
          <li class="nav-item">
            <a class="nav-link text-white px-3 py-2 rounded-2 mx-1 nav-hover-effect <?= $activeNav === 'dashboard' ? 'active' : '' ?>"
              href="/dashboard" data-nav-group="dashboard">
              <i class="bi bi-speedometer2 me-1"></i>Ana Sayfa
            </a>
          </li>
          <?php endif; ?>

          <?php if ($canAny(['logs.read', 'parameters.read', 'users.read', 'roles.read'])): ?>
          <li class="nav-item dropdown" id="systemMenu">
            <a class="nav-link text-white px-3 py-2 rounded-2 mx-1 dropdown-toggle nav-hover-effect <?= $activeNav === 'sistem' ? 'active' : '' ?>"
              href="#" data-bs-toggle="dropdown" data-nav-group="sistem" id="navSistem">
              <i class="bi bi-gear me-1"></i>Sistem
            </a>
            <ul class="dropdown-menu dropdown-menu-dark border-0 shadow-lg mt-1" style="border-radius:10px;">
              <?php if ($can('logs.read')): ?>
              <li><a class="dropdown-item py-2" href="/logs" data-nav-group="sistem"><i
                    class="bi bi-journal-text me-2 text-info"></i>İşlem Kayıtları</a></li>
              <?php endif; ?>
              <?php if ($can('parameters.read')): ?>
              <li><a class="dropdown-item py-2" href="/parameters" data-nav-group="sistem"><i
                    class="bi bi-sliders me-2 text-success"></i>Tanımlamalar</a></li>
              <?php endif; ?>
              <?php if ($can('logs.read') || $can('parameters.read')): ?>
              <?php if ($can('users.read') || $can('roles.read')): ?>
              <li>
                <hr class="dropdown-divider border-secondary">
              </li>
              <?php endif; ?>
              <?php endif; ?>
              <?php if ($can('users.read')): ?>
              <li><a class="dropdown-item py-2" href="/users" data-nav-group="sistem"><i
                    class="bi bi-person-gear me-2 text-warning"></i>Kullanıcılar</a></li>
              <?php endif; ?>
              <?php if ($can('roles.read')): ?>
              <li><a class="dropdown-item py-2" href="/roles" data-nav-group="sistem"><i
                    class="bi bi-shield-lock me-2 text-danger"></i>Roller</a></li>
              <?php endif; ?>
            </ul>
          </li>
          <?php endif; ?>

          <?php if ($canAny(['invoices.read', 'payments.read', 'projects.read', 'offers.read', 'contracts.read', 'guarantees.read'])): ?>
          <li class="nav-item dropdown">
            <a class="nav-link text-white px-3 py-2 rounded-2 mx-1 dropdown-toggle nav-hover-effect <?= $activeNav === 'islemler' ? 'active' : '' ?>"
              href="#" data-bs-toggle="dropdown" data-nav-group="islemler" id="navIslemler">
              <i class="bi bi-folder me-1"></i>Tüm Veriler
            </a>
            <ul class="dropdown-menu dropdown-menu-dark border-0 shadow-lg mt-1" style="border-radius:10px;">
              <?php if ($can('invoices.read')): ?>
              <li><a class="dropdown-item py-2" href="/invoices" data-nav-group="islemler"><i
                    class="bi bi-receipt me-2 text-info"></i>Faturalar</a></li>
              <?php endif; ?>
              <?php if ($can('payments.read')): ?>
              <li><a class="dropdown-item py-2" href="/payments" data-nav-group="islemler"><i
                    class="bi bi-cash-stack me-2 text-success"></i>Ödemeler</a></li>
              <?php endif; ?>
              <?php if ($can('projects.read')): ?>
              <li><a class="dropdown-item py-2" href="/projects" data-nav-group="islemler"><i
                    class="bi bi-kanban me-2 text-primary"></i>Projeler</a></li>
              <?php endif; ?>
              <?php 
              $HasFirstGroup = $can('invoices.read') || $can('payments.read') || $can('projects.read');
              $HasSecondGroup = $can('offers.read') || $can('contracts.read') || $can('guarantees.read');
              if ($HasFirstGroup && $HasSecondGroup): 
              ?>
              <li>
                <hr class="dropdown-divider border-secondary">
              </li>
              <?php endif; ?>
              <?php if ($can('offers.read')): ?>
              <li><a class="dropdown-item py-2" href="/offers" data-nav-group="islemler"><i
                    class="bi bi-file-text me-2 text-warning"></i>Teklifler</a></li>
              <?php endif; ?>
              <?php if ($can('contracts.read')): ?>
              <li><a class="dropdown-item py-2" href="/contracts" data-nav-group="islemler"><i
                    class="bi bi-file-earmark-text me-2 text-secondary"></i>Sözleşmeler</a></li>
              <?php endif; ?>
              <?php if ($can('guarantees.read')): ?>
              <li><a class="dropdown-item py-2" href="/guarantees" data-nav-group="islemler"><i
                    class="bi bi-shield-check me-2 text-danger"></i>Teminatlar</a></li>
              <?php endif; ?>
            </ul>
          </li>
          <?php endif; ?>

        </ul>

        <div class="d-flex align-items-center gap-2">
          <div class="dropdown">
            <a class="btn btn-light btn-sm dropdown-toggle d-flex align-items-center gap-2 px-3 py-2 rounded-pill shadow-sm"
              href="#" data-bs-toggle="dropdown" style="font-weight:500;">
              <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center"
                style="width:28px;height:28px;font-size:14px;">
                <i class="bi bi-person-fill"></i>
              </div>
              <span id="userNameDisplay">Kullanıcı</span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg mt-2"
              style="border-radius:10px;min-width:200px;">
              <li class="px-3 py-2 border-bottom">
                <div class="d-flex align-items-center gap-2">
                  <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center"
                    style="width:40px;height:40px;">
                    <i class="bi bi-person-fill fs-5"></i>
                  </div>
                  <div>
                    <div class="fw-semibold" id="userNameDropdown">Kullanıcı</div>
                    <small class="text-muted" id="userRoleDropdown">Yönetici</small>
                  </div>
                </div>
              </li>
              <li><a class="dropdown-item py-2 mt-1" href="/my-account"><i
                    class="bi bi-person-circle me-2 text-primary"></i>Hesabım</a></li>
              <li>
                <hr class="dropdown-divider">
              </li>
              <li><a class="dropdown-item py-2 text-danger" href="#" id="logoutNav"><i
                    class="bi bi-box-arrow-right me-2"></i>Çıkış Yap</a></li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </nav>

  <style>
    .nav-hover-effect {
      transition: all 0.2s ease;
      opacity: 0.9;
    }

    .nav-hover-effect:hover {
      background: rgba(255, 255, 255, 0.15) !important;
      opacity: 1;
    }

    .nav-hover-effect.active {
      background: rgba(255, 255, 255, 0.2) !important;
      opacity: 1;
    }

    .dropdown-menu-dark .dropdown-item:hover {
      background: rgba(255, 255, 255, 0.1);
    }

    .dropdown-menu .dropdown-item {
      transition: all 0.15s ease;
    }

    .dropdown-menu .dropdown-item:hover {
      padding-left: 1.25rem;
    }

    @media (max-width: 991.98px) {
      #mainNavbar {
        background: rgba(26, 35, 126, 0.98);
        margin-top: 0.5rem;
        border-radius: 10px;
        padding: 0.5rem;
      }
    }
  </style>

  <main class="container-fluid py-3" style="margin-top:60px; margin-bottom:40px; min-height:calc(100vh - 100px);">
    <div class="row">
      <!-- Sol Sidebar: Müşteri Listesi (Global) -->
      <?php if ($can('customers.read')): ?>
      <div class="col-lg-3 mb-3 mb-lg-0" id="globalCustomerSidebar">
        <div class="card shadow-sm" id="globalCustomerPanel">
          <div class="card-header bg-primary text-white py-2 d-flex justify-content-between align-items-center">
            <span class="fw-semibold"><i class="bi bi-people-fill me-2"></i>Müşteriler</span>
            <?php if ($can('customers.create')): ?>
            <button type="button" class="btn btn-sm btn-light" data-action="add-customer" title="Yeni Müşteri">
              <i class="bi bi-plus-lg"></i>
            </button>
            <?php endif; ?>
          </div>
          <div class="bg-light border-bottom px-2 py-1">
            <input type="text" class="form-control form-control-sm" id="globalCustomerSearch" placeholder="Müşteri ara..." autocomplete="off">
          </div>
          <div class="card-body p-2 overflow-auto" id="globalCustomerList" style="max-height: calc(100vh - 220px);">
            <div class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div></div>
          </div>
        </div>
      </div>
      <?php endif; ?>
      
      <!-- Sağ İçerik: Sayfa İçeriği -->
      <div class="<?= $can('customers.read') ? 'col-lg-9' : 'col-12' ?>" id="mainContent">
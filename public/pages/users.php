<?php
/**
 * Kullanıcılar Listesi Sayfası - Server-Rendered
 * URL: /users
 */

$pageTitle = 'Kullanıcılar';
$activeNav = 'sistem';
$currentPage = 'users';

require __DIR__ . '/partials/header.php';
?>

    <!-- ===== VIEW: KULLANICILAR ===== -->
    <div id="view-users" data-can="users.read">
      <div class="card" id="panelUsers">
        <div class="card-header bg-primary text-white py-2 d-flex align-items-center justify-content-between">
          <span class="fw-semibold"><i class="bi bi-person-gear me-2"></i>Kullanıcılar</span>
          <a href="/users/new" class="btn btn-light btn-sm" id="usersAddBtn" data-can="users.create">
            <i class="bi bi-plus-lg me-1"></i><span class="d-none d-md-inline">Ekle</span>
          </a>
        </div>
        <div class="card-body p-0" id="usersTableContainer">
          <div class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div></div>
        </div>
      </div>
    </div>

<?php require __DIR__ . '/partials/footer.php'; ?>

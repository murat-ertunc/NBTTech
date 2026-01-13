<?php
/**
 * Hesabım Sayfası - Server-Rendered
 * URL: /my-account
 */

$pageTitle = 'Hesabım';
$activeNav = '';
$currentPage = 'my-account';

require __DIR__ . '/partials/header.php';
?>

    <!-- ===== VIEW: HESABIM ===== -->
    <div id="view-my-account">
      <div class="row justify-content-center">
        <div class="col-12">
          <div class="card shadow-sm">
            <div class="card-header bg-primary text-white py-3">
              <span class="fw-semibold"><i class="bi bi-person-circle me-2"></i>Kullanıcı Bilgileri</span>
            </div>
            <div class="card-body p-4">
              <!-- Kullanıcı Bilgileri Formu -->
              <form id="myAccountForm">
                <div class="mb-3">
                  <label class="form-label fw-semibold">Id</label>
                  <input type="text" class="form-control bg-light" id="accountUserId" readonly>
                </div>
                <div class="mb-3">
                  <label class="form-label fw-semibold">Kodu</label>
                  <input type="text" class="form-control bg-light" id="accountUserCode" readonly>
                </div>
                <div class="mb-3">
                  <label class="form-label fw-semibold">Adı</label>
                  <input type="text" class="form-control bg-light" id="accountUserName" readonly>
                </div>
                <div class="mb-3">
                  <label class="form-label fw-semibold">Rol</label>
                  <input type="text" class="form-control bg-light" id="accountUserRole" readonly>
                </div>

                <hr class="my-4">

                <h6 class="fw-semibold mb-3"><i class="bi bi-key me-2"></i>Şifre Değiştir</h6>
                
                <div class="mb-3">
                  <label class="form-label fw-semibold">Eski Şifre</label>
                  <input type="password" class="form-control" id="accountOldPassword" autocomplete="new-password" placeholder="Mevcut şifrenizi girin">
                </div>
                <div class="mb-3">
                  <label class="form-label fw-semibold">Yeni Şifre</label>
                  <input type="password" class="form-control" id="accountNewPassword" autocomplete="new-password" placeholder="Yeni şifrenizi girin (min 6 karakter)">
                </div>

                <div class="d-grid">
                  <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-2"></i>Şifre Değiştir
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>

<?php require __DIR__ . '/partials/footer.php'; ?>

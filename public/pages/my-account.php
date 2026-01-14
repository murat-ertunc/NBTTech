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
                <div class="row mb-3 align-items-center">
                  <label class="col-3 col-form-label fw-semibold">Id</label>
                  <div class="col-9">
                    <input type="text" class="form-control bg-light" id="accountUserId" readonly>
                  </div>
                </div>
                <div class="row mb-3 align-items-center">
                  <label class="col-3 col-form-label fw-semibold">Kodu</label>
                  <div class="col-9">
                    <input type="text" class="form-control bg-light" id="accountUserCode" readonly>
                  </div>
                </div>
                <div class="row mb-3 align-items-center">
                  <label class="col-3 col-form-label fw-semibold">Adı</label>
                  <div class="col-9">
                    <input type="text" class="form-control bg-light" id="accountUserName" readonly>
                  </div>
                </div>
                <div class="row mb-3 align-items-center">
                  <label class="col-3 col-form-label fw-semibold">Rol</label>
                  <div class="col-9">
                    <input type="text" class="form-control bg-light" id="accountUserRole" readonly>
                  </div>
                </div>

                <hr class="my-4">

                <h6 class="fw-semibold mb-3"><i class="bi bi-key me-2"></i>Şifre Değiştir</h6>
                
                <div class="row mb-3 align-items-center">
                  <label class="col-3 col-form-label fw-semibold">Eski Şifre</label>
                  <div class="col-9">
                    <input type="password" class="form-control" id="accountOldPassword" autocomplete="new-password" placeholder="Mevcut şifrenizi girin">
                  </div>
                </div>
                <div class="row mb-3 align-items-center">
                  <label class="col-3 col-form-label fw-semibold">Yeni Şifre</label>
                  <div class="col-9">
                    <input type="password" class="form-control" id="accountNewPassword" autocomplete="new-password" placeholder="Yeni şifrenizi girin (min 6 karakter)">
                  </div>
                </div>

                <div class="row">
                  <div class="col-9 offset-3">
                    <button type="submit" class="btn btn-primary">
                      <i class="bi bi-check-lg me-2"></i>Şifre Değiştir
                    </button>
                  </div>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>

<?php require __DIR__ . '/partials/footer.php'; ?>

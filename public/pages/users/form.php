<?php
/**
 * Kullanıcı Form Sayfası - Ekle/Düzenle
 * URL: /users/new veya /users/{id}/edit
 */

$KullaniciId = $KullaniciId ?? 0;
$IsEdit = $KullaniciId > 0;

$pageTitle = $IsEdit ? 'Kullanıcı Düzenle' : 'Yeni Kullanıcı';
$activeNav = 'sistem';
$currentPage = 'user-form';

require __DIR__ . '/../partials/header.php';
?>

<div class="container-fluid py-4">
  <div class="row justify-content-center">
    <div class="col-12">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="/users">Kullanıcılar</a></li>
            <li class="breadcrumb-item active"><?= $IsEdit ? 'Kullanıcı Düzenle' : 'Yeni Kullanıcı' ?></li>
          </ol>
        </nav>
        <a href="/users" class="btn btn-outline-secondary">
          <i class="bi bi-arrow-left me-1"></i>Listeye Dön
        </a>
      </div>

      <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
          <h5 class="card-title mb-0"><i class="bi bi-person-gear me-2"></i><?= $IsEdit ? 'Kullanıcı Düzenle' : 'Yeni Kullanıcı' ?></h5>
        </div>
        <div class="card-body">
          <form id="userPageForm">
            <div class="alert alert-danger d-none" id="userFormError"></div>

            <input type="hidden" id="userId" value="<?= (int)$KullaniciId ?>">

            <div class="row mb-3">
              <label class="col-4 col-form-label">Ad Soyad <span class="text-danger">*</span></label>
              <div class="col-8">
                <input type="text" class="form-control" id="userAdSoyad" required>
                <small class="form-text text-muted">Zorunlu, Min 3</small>
                <div class="invalid-feedback" id="userAdSoyadError"></div>
              </div>
            </div>

            <div class="row mb-3">
              <label class="col-4 col-form-label">Kullanıcı Adı <span class="text-danger" id="userKullaniciAdiRequired">*</span></label>
              <div class="col-8">
                <input type="text" class="form-control" id="userKullaniciAdi">
                <small class="form-text text-muted">Zorunlu, Min 3</small>
                <div class="invalid-feedback" id="userKullaniciAdiError"></div>
              </div>
            </div>

            <div class="row mb-3">
              <label class="col-4 col-form-label">Şifre <span class="text-danger" id="userSifreRequired">*</span></label>
              <div class="col-8">
                <input type="password" class="form-control" id="userSifre">
                <small class="form-text text-muted">Yeni kullanıcı için zorunlu, Min 6</small>
                <small class="text-muted d-block">Düzenlemede boş bırakırsanız şifre değişmez.</small>
                <div class="invalid-feedback" id="userSifreError"></div>
              </div>
            </div>

            <div class="row mb-3">
              <label class="col-4 col-form-label">Roller <span class="text-muted">(Atayabileceğiniz roller)</span></label>
              <div class="col-8">
                <div class="alert alert-warning d-none py-2" id="userRolesWarning"></div>
                <div id="userRolesContainer" class="border rounded p-2" style="max-height: 240px; overflow-y: auto;">
                  <div class="text-center text-muted py-2">
                    <div class="spinner-border spinner-border-sm"></div> Roller yükleniyor...
                  </div>
                </div>
                <small class="form-text text-muted">Birden fazla rol seçebilirsiniz.</small>
                <div class="invalid-feedback" id="userRolesError"></div>
              </div>
            </div>
          </form>
        </div>
        <div class="card-footer d-flex justify-content-between">
          <a href="/users" class="btn btn-secondary">
            <i class="bi bi-x-lg me-1"></i>İptal
          </a>
          <button type="button" class="btn btn-primary" id="btnSaveUserPage" data-can-any="users.create,users.update">
            <i class="bi bi-check-lg me-1"></i>Kaydet
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const KullaniciId = <?= (int)$KullaniciId ?>;
  NbtUserPageForm.init(KullaniciId);
});
</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>
